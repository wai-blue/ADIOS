<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\DB;

class MySQLi extends \ADIOS\Core\DB
{

  private $queryResult;

  /**
   * Connects the DB object to the database.
   *
   * @throws \ADIOS\Core\Exceptions\DBException When connection string is not configured.
   * @throws \ADIOS\Core\Exceptions\DBException When connection error occured.
   *
   * @return void
   */
  public function connect() : void
  {
    if (empty($this->db_host)) {
      throw new \ADIOS\Core\Exceptions\DBException("Database connection string is not configured.");
    }

    if (!empty($this->db_port) && is_numeric($this->db_port)) {
      $this->connection = new \mysqli($this->db_host, $this->db_name, $this->db_password, $this->db_name, $this->db_port);
    } else {
      $this->connection = new \mysqli($this->db_host, $this->db_user, $this->db_password);
    }

    if (!empty($this->connection->connect_error)) {
      throw new \ADIOS\Core\Exceptions\DBException($this->connection->connect_error);
    }

    $this->connection->select_db($this->db_name);

    if ($this->connection->errno == 1049) {
      // unknown database
      $this->query("
        create database if not exists `{$this->db_name}`
        default charset = utf8mb4
        default collate = utf8mb4_unicode_ci
      ");

      $this->adios->console->info("Created database `{$this->db_name}`");

      $this->connection->select_db($this->db_name);
    } else if ($this->connection->errno > 0) {
      throw new \ADIOS\Core\Exceptions\DBException($this->connection->errno);
    }

    if (!empty($this->db_codepage)) {
      $this->connection->set_charset($this->db_codepage);
    }
  }

  function escape(string $str) : string
  {
    return $this->connection->real_escape_string((string) $str);
  }

  public function buildSql(\ADIOS\Core\DB\Query $query) : string
  {
    $model = $query->getModel();

    $selectModifiers = $query->getStatements(\ADIOS\Core\DB\Query::selectModifier);
    $columns = $query->getStatements(\ADIOS\Core\DB\Query::column);
    $joins = $query->getStatements(\ADIOS\Core\DB\Query::join);
    $wheres = $query->getStatements(\ADIOS\Core\DB\Query::where);
    $whereRaws = $query->getStatements(\ADIOS\Core\DB\Query::whereRaw);
    $havings = $query->getStatements(\ADIOS\Core\DB\Query::having);
    $havingRaws = $query->getStatements(\ADIOS\Core\DB\Query::havingRaw);
    $orders = $query->getStatements(\ADIOS\Core\DB\Query::order);
    $limits = $query->getStatements(\ADIOS\Core\DB\Query::limit);

    switch ($query->getType()) {
      case \ADIOS\Core\DB\Query::select:

        // select modifiers
        $selectModifiersArray = [];
        foreach ($selectModifiers as $modifier) {
          switch ($modifier[1]) {
            case \ADIOS\Core\DB\Query::countRows:
              $selectModifiersArray[] = 'SQL_CALC_FOUND_ROWS';
            break;
            case \ADIOS\Core\DB\Query::distinct:
              $selectModifiersArray[] = 'DISTINCT';
            break;
            case \ADIOS\Core\DB\Query::distinctRow:
              $selectModifiersArray[] = 'DISTINCTROW';
            break;
          }
        }

        // columns
        $columnsArray = [];
        foreach ($columns as $column) {
          list($tmpTable, $tmpColumn) = explode(".", $column[1]);
          $columnsArray[] = '`' . $tmpTable . '`.`' . $tmpColumn . '` as `' . $column[2] . '`';
        }

        // joins
        $joinsArray = [];
        foreach ($joins as $join) {
          $joinsArray[] = 
            'LEFT JOIN `' . $join[2] . '` as `' . $join[3] . '`'
            . ' ON `' .  $join[3] . '`.`id` = `' . $join[1] . '`.`' . $join[4] . '`';
        }

        // wheres and whereRaws
        $wheresArray = [];
        foreach ($wheres as $where) {
          $wheresArray[] = $where[1];
          if ($where[3] === \ADIOS\Core\DB\Query::columnFilter) {
            $wheresArray[] = $this->columnFilter(
              $model,
              $where[1],
              $where[2]
            );
          }
        }
        foreach ($whereRaws as $whereRaw) {
          $wheresArray[] = $whereRaw[1];
        }

        // havings and havingRaws
        $havingsArray = [];
        foreach ($havings as $having) {
          if ($having[3] === \ADIOS\Core\DB\Query::columnFilter) {
            $havingsArray[] = $this->columnFilter(
              $model,
              $having[1],
              $having[2]
            );
          }
        }
        foreach ($havingRaws as $havingRaw) {
          $havingsArray[] = $havingRaw[1];
        }
        // var_dump($havingRaws);

        // orders
        $ordersArray = [];
        foreach ($orders as $order) {

          $order[1] = trim($order[1]);
          $order[1] = '`' . implode('`.`', explode(".", str_replace('`', '', $order[1]))) . '`';

          $order[2] = strtoupper($order[2]);

          if (!in_array($order[2], ['ASC', 'DESC'])) continue;

          $ordersArray[] = $order[1] . ' ' . $order[2];
        }

        // limit
        if (count($limits) > 0) {
          $limitSql = ' LIMIT ' . $limits[0][1] . ', ' . $limits[0][2];
        } else {
          $limitSql = '';
        }

        // sql
        $sql = 
          'SELECT ' . join(' ', $selectModifiersArray) . ' '
            . join(', ', $columnsArray)
          . ' FROM `' . $model->getFullTableSqlName() . '`'
          . ' ' . join(' ', $joinsArray)
          . (count($wheresArray) == 0 ? '' : ' WHERE ' . join(' AND ', $wheresArray))
          . (count($havingsArray) == 0 ? '' : ' HAVING ' . join(' AND ', $havingsArray))
          . (count($ordersArray) == 0 ? '' : ' ORDER BY ' . join(' AND ', $ordersArray))
          . $limitSql
        ;

      break;
    }
// echo $sql;
    return $sql;
  }

  public function countRowsFromLastSelect() : int
  {
    return (int) reset($this->fetchRaw('SELECT FOUND_ROWS() as FOUND_ROWS'))['FOUND_ROWS'];
  }


  /**
   * Runs a single SQL query.
   * Sets the $db_error property, if an error occurs.
   *
   * @param string SQL query to run
   *
   * @throws \ADIOS\Core\Exceptions\DBDuplicateEntryException When foreign key constrain block the query execution.
   * @throws \ADIOS\Core\Exceptions\DBException In case of any other error.
   *
   * @return object DB result object.
   */
  public function query(string $query, object $initiatingModel = NULL)
  {

    if ($this->connection === NULL) {
      throw new \ADIOS\Core\Exceptions\DBNotConnectedException();
    }

    $query = trim($query, " ;");
    if (empty($query)) return FALSE;

    $ts1 = _getmicrotime();

    $this->queryResult = $this->connection->query($query);
    $this->lastQueryDurationSec = _getmicrotime() - $ts1;

    if (!empty($this->connection->error)) {
      $foreginKeyErrorCodes = [1062, 1216, 1217, 1451, 1452];
      $errorNo = $this->connection->errno;

      if (in_array($errorNo, $foreginKeyErrorCodes)) {
        throw new \ADIOS\Core\Exceptions\DBDuplicateEntryException(
          json_encode([$this->connection->error, $query, $initiatingModel->fullName, $errorNo])
        );
      } else {
        throw new \ADIOS\Core\Exceptions\DBException(
          "ERROR #: {$errorNo}, "
          . $this->connection->error
          . ", QUERY: {$query}"
        );
      }
    } else if ($this->logQueries) {
      $this->adios->logger->info("Query OK [" . ($this->lastQueryDurationSec * 1000) . "]:\n{$query}", [], "db");
    }

    return TRUE;
  }

  public function fetchArray()
  {
    return $this->queryResult->fetch_array(MYSQLI_ASSOC);
  }

  public function insertedId()
  {
    return $this->connection->insert_id;
  }





















  /////////////////////////////////////////////////////////////////////////////////////////////////////////
  // functions for manipulating data in the database
  /////////////////////////////////////////////////////////////////////////////////////////////////////////

  /**
   * Creates given table in the SQL database. In other words: executes
   * SQL command returned by _sql_table_create.
   *
   * @param string name of a table
   * @param bool If this param is TRUE, it only returns the SQL command to be executed
   *
   */
  public function createSqlTable($table_name, $only_sql_command = false, $force_create = false)
  {
    $do_create = true;

    $log_status = $this->log_disabled;
    $this->log_disabled = 1;

    if (!$force_create) {
      try {
        $this->query("select * from `{$table_name}`");
        $cnt = mysqli_num_rows($this->queryResult);
      } catch (\ADIOS\Core\Exceptions\DBException $e) {
        $cnt = 0;
      }

      if ($cnt > 0) {
        $do_create = false;
      }
    }

    if ($do_create) {


      if (!isset($this->tables[$table_name])) {
        throw new \ADIOS\Core\Exceptions\DBException(
          "Cannot create SQL table `{$table_name}`. It is not defined."
        );
      }

      $table_columns = $this->tables[$table_name];
      $table_params = $table_columns['%%table_params%%'];

      $sql = "drop table if exists `{$table_name}`;;\n";
      $sql .= "create table `{$table_name}` (\n";

      foreach ($table_columns as $col_name => $col_definition) {
        $col_type = trim($col_definition['type']);

        if (isset($this->columnTypes[$col_type]) && !$col_definition['virtual']) {
          $tmp = $this->columnTypes[$col_type]
            ->get_sql_create_string($table_name, $col_name, $col_definition);
          if (!empty($tmp)) {
            $sql .= "  {$tmp},\n";
          }
        }
      }

      // indexy
      foreach ($table_columns as $col_name => $col_definition) {
        if (
          !$col_definition['virtual']
          && in_array($col_definition['type'], ['lookup', 'int', 'bool', 'boolean', 'date'])
        ) {
          $sql .= "  index `{$col_name}` (`{$col_name}`),\n";
        }
      }

      $sql = substr($sql, 0, -2) . ")";

      $sql .= " ENGINE = " . ($table_params['engine'] ?? "InnoDB") . ";;\n";



      if ($only_sql_command) {
        return $sql;
      } else {

        $this->query("SET foreign_key_checks = 0");
        $this->query("drop table if exists `{$table_name}`");

        $this->multiQuery($sql);

      $this->query("SET foreign_key_checks = 1");
      }
    }
    $this->log_disabled = $log_status;
  }


  public function createSqlForeignKeys($table)
  {
    $sql = '';
    foreach ($this->tables[$table] as $column => $columnDefinition) {
      if (
        !$columnDefinition['disable_foreign_key']
        && 'lookup' == $columnDefinition['type']
      ) {
        $lookupModel = $this->adios->getModel($columnDefinition['model']);
        $foreignKeyColumn = $columnDefinition['foreign_key_column'] ?: "id";

        $sql .= "
            ALTER TABLE `{$table}`
            ADD CONSTRAINT `fk_" . md5($table . '_' . $column) . "`
            FOREIGN KEY (`{$column}`)
            REFERENCES `" . $lookupModel->getFullTableSqlName() . "` (`{$foreignKeyColumn}`);;
          ";
      }
    }

    if (!empty($sql)) {
      $this->multiQuery($sql);
    }
  }



  /**
   * Returns part of SQL command representing the value of specified column to
   * be inserted or updated. Used in insertRow, updateRow
   * methods.
   *
   * @param string name of a table
   * @param string  name of a column
   * @param array Array of values. One of the keys HAS TO BE the name of the column!
   * @param bool if this param is TRUE, the returned string is generated exclusively for the dump_data() method
   *
   * @see insert_row
   * @see updateRow
   */
  public function columnSqlValue($table, $colName, $data, $dumpQuery = false)
  {
    $colType = $this->tables[$table][$colName]['type'];
    $value = $data[$colName];
    $valueExists = array_key_exists($colName, $data);

    $sql = '';

    // ak je hodnota stlpca definovana ako pole, tak moze mat rozne parametre
    if (is_array($value) && isset($value['sql']) && !empty(trim($value['sql']))) {
      $sql = "`{$colName}` = ({$value['sql']})";
    } else if (strpos((string) $value, "SQL:") === 0) {
      $sql = "`{$colName}` = (" . substr($value, 4) . ")";
    } else if (
      isset($this->columnTypes[$colType])
      && isset($data[$colName])
    ) {
      $sql = $this->columnTypes[$colType]->get_sql_column_data_string(
        $table,
        $colName,
        $data[$colName],
        [
          'null_value' => !$valueExists,
          'dumping_data' => $dumpQuery,
          'data' => $data,
        ]
      );
    }

    return (empty($sql) ? "" : "{$sql}, ");
  }

  //
  ////////////////////////////////////////////////////////////////////////////////////////////////


  ////////////////////////////////////////////////////////////////////////////////////////////////
  // insert_row_query, insert_row

  /**
   * Returns SQL string representing command which would insert a row into a database.
   *
   * @param string name of a table
   * @param array array of data to be inserted
   * @param bool if this param is TRUE, the returned string is generated exclusively for the dump_data() method
   *
   * @see columnSql
   */
  private function insertRowQuery(string $table, array $data, bool $dumpQuery = false)
  {
    $SQL = "";

    $addIdColumn = TRUE;
    if ($dumpQuery) $addIdColumn = FALSE;
    if (!isset($this->tables[$table]['id'])) $addIdColumn = FALSE;

    if ($addIdColumn) {
      if (!isset($data['id']) || $data['id'] <= 0) {
        $SQL .= "`id`=null, ";
      } else {
        $SQL .= "`id`='" . $this->escape($data['id']) . "', ";
        unset($data['id']);
      }
    }

    foreach ($this->tables[$table] as $colName => $colDefinition) {
      if (!$colDefinition['virtual'] && $colName != '%%table_params%%') {
        if ($data[$colName] !== NULL) {
          $tmp_sql = $this->columnSqlValue($table, $colName, $data, $dumpQuery);

          $SQL .= $tmp_sql;
        } else if (!empty($colDefinition['default_value'])) {
          $SQL .= $colDefinition['default_value'];
        }
      }
    }

    $SQL = substr($SQL, 0, -2);

    return $SQL;
  }

  /**
   * Executes SQL command generated by a insertRowQuery() method.
   *
   * @param string name of a table
   * @param array array of data to be inserted
   * @param bool if this param is TRUE, the SQL command is not executed, only returned as a string
   * @param bool if this param is TRUE, the returned string is generated exclusively for the dump_data() method
   */
  public function insertRow($table, $data, $only_sql_command = false, $dumping_data = false, $initiatingModel = NULL)
  {
    if ($data['id'] <= 0) {
      unset($data['id']);
    }

    $sql = "insert into `{$table}` set ";
    $sql .= $this->insertRowQuery($table, $data, $dumping_data);

    if ($only_sql_command) {
      return $sql . "\n";
    } else {
      $this->multiQuery($sql, ";;\n", $initiatingModel);
      $inserted_id = $this->insertedId();

      return $inserted_id;
    }
  }

  public function insertOrUpdateRow($table, $data, $only_sql_command = false, $dumping_data = false, $initiatingModel = NULL)
  {
    if ($data['id'] <= 0) {
      unset($data['id']);
    }

    $dataWithoutId = $data;
    unset($dataWithoutId['id']);

    $sql = "insert into `{$table}` set ";
    $sql .= $this->insertRowQuery($table, $data, $dumping_data);
    $sql .= " on duplicate key update ";
    $sql .= $this->insertRowQuery($table, $dataWithoutId, TRUE);

    if ($only_sql_command) {
      return $sql . "\n";
    } else {
      $this->multiQuery($sql, ";;\n", $initiatingModel);
      $inserted_id = $this->insertedId();

      return $inserted_id;
    }
  }

  public function insertRandomRow($table_name, $data = [], $dictionary = [], $initiatingModel = NULL)
  {
    if (is_array($this->tables[$table_name])) {
      foreach ($this->tables[$table_name] as $col_name => $col_definition) {
        if ($col_name != "id" && !isset($data[$col_name])) {
          $random_val = NULL;
          if (is_array($dictionary[$col_name])) {
            $random_val = $dictionary[$col_name][rand(0, count($dictionary[$col_name]) - 1)];
          } else {
            switch ($col_definition['type']) {
              case "int":
                if (is_array($col_definition['enum_values'])) {
                  $keys = array_keys($col_definition['enum_values']);
                  $random_val = $keys[rand(0, count($keys) - 1)];
                } else {
                  $random_val = rand(0, 1000);
                }
                break;
              case "float":
                $random_val = rand(0, 1000) / ($col_definition['decimals'] ?? 2);
                break;
              case "time":
                $random_val = rand(10, 20) . ":" . rand(10, 59);
                break;
              case "date":
                $random_val = date("Y-m-d", time() - (3600 * 24 * 365) + rand(0, 3600 * 24 * 365));
                break;
              case "datetime":
                $random_val = date("Y-m-d H:i:s", time() - (3600 * 24 * 365) + rand(0, 3600 * 24 * 365));
                break;
              case "boolean":
                $random_val = (rand(0, 1) ? 1 : 0);
                break;
              case "text":
                switch (rand(0, 5)) {
                  case 0:
                    $random_val = "Nunc ac sollicitudin ipsum. Vestibulum condimentum vitae justo quis bibendum. Fusce et scelerisque dui, eu placerat nisl. Proin ut efficitur velit, nec rutrum massa.";
                    break;
                  case 1:
                    $random_val = "Integer ullamcorper lacus at nisi posuere posuere. Maecenas malesuada magna id fringilla sagittis. Nam sed turpis feugiat, placerat nisi et, gravida lacus. Curabitur porta elementum suscipit.";
                    break;
                  case 2:
                    $random_val = "Praesent libero diam, vulputate sed varius eget, luctus a risus. Praesent sit amet neque commodo, varius nisl dignissim, tincidunt magna. Nunc tincidunt dignissim ligula, sit amet facilisis felis mollis vel.";
                    break;
                  case 3:
                    $random_val = "Sed ut ligula luctus, ullamcorper felis nec, tristique lorem. Maecenas sit amet tincidunt enim.";
                    break;
                  case 4:
                    $random_val = "Mauris blandit ligula massa, sit amet auctor risus viverra at. Cras rhoncus molestie malesuada. Sed facilisis blandit augue, eu suscipit lectus vehicula quis. Mauris efficitur elementum feugiat.";
                    break;
                  default:
                    $random_val = "Nulla posuere dui sit amet elit efficitur iaculis. Cras elit ligula, feugiat vitae maximus quis, volutpat sit amet sapien. Vivamus varius magna fermentum dolor varius, vel scelerisque ante mollis.";
                    break;
                }
              case "varchar":
              case "password":
                switch (rand(0, 5)) {
                  case 0:
                    $random_val = rand(0, 9) . " Nunc";
                    break;
                  case 1:
                    $random_val = rand(0, 9) . " Efficitur";
                    break;
                  case 2:
                    $random_val = rand(0, 9) . " Vulputate";
                    break;
                  case 3:
                    $random_val = rand(0, 9) . " Ligula luctus";
                    break;
                  case 4:
                    $random_val = rand(0, 9) . " Mauris";
                    break;
                  case 5:
                    $random_val = rand(0, 9) . " Massa";
                    break;
                  case 6:
                    $random_val = rand(0, 9) . " Auctor";
                    break;
                  case 7:
                    $random_val = rand(0, 9) . " Molestie";
                    break;
                  case 8:
                    $random_val = rand(0, 9) . " Malesuada";
                    break;
                  case 9:
                    $random_val = rand(0, 9) . " Facilisis";
                    break;
                  case 10:
                    $random_val = rand(0, 9) . " Augue";
                    break;
                }
                break;
            }
          }

          if ($random_val !== NULL) {
            $data[$col_name] = $random_val;
          }
        }
      }
    }

    return $this->insertRow($table_name, $data, FALSE, FALSE, $initiatingModel);
  }

  //
  ////////////////////////////////////////////////////////////////////////////////////////////////

  /**
   * Returns SQL string representing command which would update a row into a database.
   *
   * @param string name of a table
   * @param array array of data to be inserted
   * @param int ID of a row to be inserted
   * @param bool if this param is TRUE, the values not present in $data array are left untouched
   *
   * @see _sql_column_data
   */
  public function updateRowQuery($table, $data, $id)
  {
    global $_FILES;

    if (is_array($_FILES)) {
      foreach ($_FILES as $key => $value) {
        if (null !== $data[$key]) {
          $data[$key] = $value;
        }
      }
    }

    $sql = "update `{$table}` set ";
    foreach ($this->tables[$table] as $col_name => $col_definition) {
      if (!$col_definition['virtual']) {
        $sql .= $this->columnSqlValue($table, $col_name, $data);
      }
    }

    $sql = substr($sql, 0, -2) . " where `id` = ".(int) $id;

    return $sql;
  }

  /**
   * Executes SQL command generated by a updateRowQuery() method. Similar to update_row() method, with one
   * difference: values which are not present in $data parameter are left unchanged.
   *
   * @param string name of a table
   * @param array array of data to be inserted
   * @param int ID of a row to be updated
   * @param bool if this param is TRUE, the SQL command is not executed, only returned as a string
   *
   */
  public function updateRow($table_name, $data, $id, $only_sql_command = FALSE, $initiatingModel = NULL)
  {
    $sql = $this->updateRowQuery($table_name, $data, $id);

    if ($only_sql_command) {
      return $sql;
    } else {
      $this->query($sql, $initiatingModel);
      return $id;
    }
  }

  /**
   * Deletes row with given ID. For "image" or "file" columns deletes the relevant image (file).
   *
   * @param string name of a table
   * @param int ID of a row to delete
   */
  public function deleteRow($table, $id)
  {
    return $this->query("
      delete from `{$table}`
      where id = " . (int) $id . "
      limit 1
    ");
  }

  //
  ////////////////////////////////////////////////////////////////////////////////////////////////

  ////////////////////////////////////////////////////////////////////////////////////////////////
  // copy

  /**
   * Copies the row with given ID to a new row and returns ID of an inserted item.
   *
   * @param string name of a table
   * @param int ID of a row to copy
   */
  public function copy($table, $id)
  {
    $data = $this->getRow($table, "`id` = ".(int) $id);
    unset($data['id']);

    return $this->insertRow($table, $data);
  }

  //
  ////////////////////////////////////////////////////////////////////////////////////////////////

  /////////////////////////////////////////////////////////////////////////////////////////////////////////
  // functions for retrieving data from database
  /////////////////////////////////////////////////////////////////////////////////////////////////////////

  public function filter($col_name, $col_type, $value, $params = [])
  {
    if (false !== strpos('.', $col_name)) {
      list($table_name, $col_name) = explode('.', $col_name);
    }
    if (is_object($this->columnTypes[$col_type])) {
      return ('' == $table_name ? '' : "{$table_name}.") . $this->columnTypes[$col_type]->filter($col_name, $value, $params);
    } else {
      return 'TRUE';
    }
  }

  private function aggregate($input_column, $output_column, $aggregate_function)
  {
    if ('count' == $aggregate_function) {
      return "$aggregate_function($input_column) as $output_column";
    } elseif ('min' == $aggregate_function || 'max' == $aggregate_function || 'sum' == $aggregate_function || 'avg' == $aggregate_function || 'count' == $aggregate_function) {
      return "$aggregate_function(ifnull($input_column, 0)) as $output_column";
    } elseif ('group_concat' == $aggregate_function) {
      return "group_concat($input_column separator ', ') as $output_column";
    } elseif ('count_distinct' == $aggregate_function) {
      return "count(distinct $input_column) as $output_column";
    } elseif ('null' == $aggregate_function) {
      return "null as $output_column";
    } else {
      return "$input_column as $output_column";
    }
  }

  public function startTransaction()
  {
    $this->query('start transaction');
  }

  public function commit()
  {
    $this->query('commit');
  }

  public function rollback()
  {
    $this->query('rollback');
  }

  /**
   * Returns the array of column values of the first row which meet the criteria.
   *
   * @param string name of a table
   * @param string SQL condition to fetch the row
   */
  public function getRow($table, $where = '')
  {
    if (!empty($where)) {
      $where = "where $where";
    }
    $this->query("select * from `{$table}` {$where}");
    $row = $this->fetchArray();

    return $row;
  }










  public function columnFilter($model, $columnName, $filterValue, $column = NULL)
  {
    if ($column === NULL) {
      $column = $model->columns()[$columnName];
    }

    $type = $column['type'];
    $s = explode(',', $filterValue);
    if (
      ($type == 'int' && _count($column['enum_values']))
      || in_array($type, ['varchar', 'text', 'color', 'file', 'image', 'enum', 'password', 'lookup'])
    ) {
      if (_count($column['enum_values'])) {
        $w = [];
        $s = [];
        foreach ($column['enum_values'] as $evk => $evv) {
          if (stripos($evv, $filterValue) !== FALSE) {
            $w[] = (string)$evk;
            $s[] = (string)$evk;
          }
        }
      } else {
        $w = explode(' ', $filterValue);
      }
    } else {
      $w = $filterValue;
    }

    if ($column['virtual']) {
      if ($type == 'int' && _count($column['enum_values'])) {
        //
      } else {
        $columnName = '(' . $column['sql'] . ')';
      }
    }

    $return = 'false';

    // trochu komplikovanejsia kontrola, ale znamena, ze vyhladavany retazec sa pouzije len ak uz nie je delitelny podla ciarok, alebo medzier
    // pripadne tato kontrola neplati ak je na zaciatku =

    if (
      '=' == $filterValue[0]
      || (is_array($s) && 1 == count($s) && is_array($w) && 1 == count($w))
      || (is_array($s) && 1 == count($s) && !is_array($w) && '' != $w)
      || !empty($column['enum_values'])
    ) {

      if (!_count($column['enum_values'])) {
        $s = reset($s);
      }

      if ('=' == $filterValue[0]) {
        $s = substr($filterValue, 1);
      }

      if (!_count($column['enum_values']) and '!=' == substr($s, 0, 2)) {
        $not = true;
        $s = substr($s, 2);
      }

      // queryies pre typy

      if ('bool' == $type) {
        if ('Y' == $s) {
          $return = "`{$columnName}` = '" . $this->escape(trim($s)) . "' ";
        } else {
          $return = "(`{$columnName}` != 'Y' OR `{$columnName}` is null) ";
        }
      }

      if ('boolean' == $type) {
        if ('0' == $s) {
          $return = "(`{$columnName}` = '" . $this->escape(trim($s)) . "' or `{$columnName}` is null) ";
        } else {
          $return = "`{$columnName}` != '0'";
        }
      }

      if ($type == 'int' && _count($column['enum_values'])) {
        $return = " `{$columnName}_enum_value` like '%" . $this->escape(trim($s)) . "%'";
      } else if ($type == 'varchar' && _count($column['enum_values'])) {
        $return = " `{$columnName}` IN (\"" . implode('","', $w) . "\")";
      } else if (in_array($type, ['varchar', 'text', 'color', 'file', 'image', 'enum', 'password'])) {
        $return = " `{$columnName}` like '%" . $this->escape(trim($s)) . "%'";
      } else if ($type == 'lookup') {
        if (is_numeric($s)) {
          $return = " `{$columnName}` = " . $this->escape($s) . "";
        } else {
          $return = " `{$columnName}:LOOKUP` like '%" . $this->escape(trim($s)) . "%'";
        }
      }

      if ('float' == $type || ('int' == $type && !_count($column['enum_values']))) {
        $s = trim(str_replace(',', '.', $s));
        $s = str_replace(' ', '', $s);

        if (is_numeric($s)) {
          $return = "({$columnName}=$s)";
        } elseif ('-' != $s[0] && strpos($s, '-')) {
          list($from, $to) = explode('-', $s);
          $return = "({$columnName}>=" . (trim($from) + 0) . " and {$columnName}<=" . (trim($to) + 0) . ')';
        } elseif (preg_match('/^([\>\<=\!]{1,2})?([0-9\.\-]+)$/', $s, $m)) {
          $operator = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? trim($m[1]) : '=');
          $operand = trim($m[2]) + 0;
          $return = "{$columnName} {$operator} {$operand}";
        } else {
          $return = 'FALSE';
        }
      }

      if ('date' == $type) {
        $s = str_replace(' ', '', $s);
        $s = str_replace(',', '.', $s);

        $return = 'false';

        // ak je do filtru zadany znak '-', vyfiltruje nezadane datumy
        if ($s === '-') {
          # V novej verzii MySQL nie je pripustna hodnota '' alebo '0000-00-00'
          # $return = "({$columnName} IS NULL OR {$columnName} = '0000-00-00' OR {$columnName} = '')";
          $return = "{$columnName} IS NULL";
        }

        # Den alebo mesiac
        if (preg_match('/^([\>\<=\!]{1,2})?([0-9]{1,2})$/', $s, $m)) {
          $operator = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? $m[1] : '=');
          if (strtotime($m[2]) > 0) {
            $to = date('Y-m-d', strtotime($m[2]));
            $return = "{$columnName} {$operator} '{$to}'";
          } else {
            $to = (int)$m[2];
            $return = "(MONTH(`{$columnName}`) {$operator} '{$to}' OR DAY(`{$columnName}`) {$operator} '{$to}')";
          }
        }

        if (preg_match('/^([\>\<=\!]{1,2})([0-9\.\-]+)([\>\<=\!]{1,2})([0-9\.\-]+)$/', $s, $m)) {
          $operator_1 = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? $m[1] : '=');
          $date_1 = date('Y-m-d', strtotime($m[2]));
          $operator_2 = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? $m[3] : '=');
          $date_2 = date('Y-m-d', strtotime($m[4]));
          if (strtotime($m[2]) > 0 && strtotime($m[4]) > 0) {
            $return = "({$columnName} {$operator_1} '{$date_1}') and ({$columnName} {$operator_2} '{$date_2}')";
          } else {
            //
          }
        }

        if (preg_match('/^([0-9\.\-]+)-([0-9\.\-]+)$/', $s, $m)) {
          $date_1 = date('Y-m-d', strtotime($m[1]));
          $date_2 = date('Y-m-d', strtotime($m[2]));
          if (strtotime($m[1]) > 0 && strtotime($m[2]) > 0) {
            $return = "({$columnName} >= '{$date_1}') and ({$columnName} <= '{$date_2}')";
          } else {
            //
          }
        }

        # Den a mesiac
        if (preg_match('/^([0-9]{1,2})\.([0-9]{1,2})$/', $s, $m)) {
          $day = $m[1];
          $month = $m[2];
          $return = "(DAY({$columnName}) = '{$day}') and (MONTH({$columnName}) = '{$month}')";
        }

        # Mesiac a rok
        if (preg_match('/^([0-9]{1,2})\.([0-9]{4})$/', $s, $m)) {
          $month = $m[1];
          $year = $m[2];
          $return = "(month({$columnName}) = '{$month}') and (year({$columnName}) = '{$year}')";
        }

        # Rok
        if (preg_match('/^([\>\<=\!]{1,2})?([0-9]{4})$/', $s, $m)) {
          $operator = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? $m[1] : '=');
          $year = $m[2];
          $return = "(year({$columnName}) {$operator} '{$year}')";
        }

        # Presny datum
        if (preg_match('/^([\>\<=\!]{1,2})?([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})$/', $s, $m)) {
          $operator = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? $m[1] : '=');
          $day = $m[2];
          $month = $m[3];
          $year = $m[4];

          $return = "`{$columnName}` {$operator} '{$year}-{$month}-{$day}'";
        }
      }

      if ('datetime' == $type || 'timestamp' == $type) {
        $s = str_replace(' ', '', $s);
        $s = str_replace(',', '.', $s);

        $return = 'false';

        // ak je do filtru zadany znak '-', vyfiltruje nezadane datumy
        if ($s === '-') {
          $return = "({$columnName} IS NULL OR {$columnName} = '0000-00-00 00:00:00' OR {$columnName} = '')";
        }

        if (preg_match('/^([\>\<=\!]{1,2})?([0-9\.\-]+)$/', $s, $m)) {
          $operator = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? $m[1] : '=');
          if (strtotime($m[2]) > 0) {
            $to = date('Y-m-d', strtotime($m[2]));
            $return = "date({$columnName}) {$operator} '{$to}'";
          } else {
            //
          }
        }
        if (preg_match('/^([\>\<=\!]{1,2})([0-9\.\-]+)([\>\<=\!]{1,2})([0-9\.\-]+)$/', $s, $m)) {
          $operator_1 = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? $m[1] : '=');
          $date_1 = date('Y-m-d', strtotime($m[2]));
          $operator_2 = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? $m[3] : '=');
          $date_2 = date('Y-m-d', strtotime($m[4]));
          if (strtotime($m[2]) > 0 && strtotime($m[4]) > 0) {
            $return = "(date({$columnName}) {$operator_1} '{$date_1}') and (date({$columnName}) {$operator_2} '{$date_2}')";
          } else {
            //
          }
        }
        if (preg_match('/^([0-9\.\-]+)-([0-9\.\-]+)$/', $s, $m)) {
          $date_1 = date('Y-m-d', strtotime($m[1]));
          $date_2 = date('Y-m-d', strtotime($m[2]));
          if (strtotime($m[1]) > 0 && strtotime($m[2]) > 0) {
            $return = "(date({$columnName}) >= '{$date_1}') and (date({$columnName}) <= '{$date_2}')";
          } else {
            //
          }
        }
        if (preg_match('/^([0-9]+)\.([0-9]+)$/', $s, $m)) {
          $month = $m[1];
          $year = $m[2];
          $return = "(month({$columnName}) = '{$month}') and (year({$columnName}) = '{$year}')";
        }
        if (preg_match('/^([\>\<=\!]{1,2})?([0-9]+)$/', $s, $m)) {
          $operator = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? $m[1] : '=');
          $year = $m[2];
          $return = "(year({$columnName}) {$operator} '{$year}')";
        }
      }

      if ('time' == $type) {
        $return = 'false';
        $s = str_replace(' ', '', $s);

        // ak je do filtru zadany znak '-', vyfiltruje nezadane datumy
        if ($s === '-') {
          $return = "({$columnName} IS NULL OR {$columnName} = '00:00:00' OR {$columnName} = '')";
        }

        if (preg_match('/^([\>\<=\!]{1,2})?([0-9\.\:]+)$/', $s, $m)) {
          $operator = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? $m[1] : '=');
          if (strtotime('01.01.2000 ' . $m[2]) > 0) {
            $to = date('H:i:s', strtotime('01.01.2000 ' . $m[2]));
            $return = "{$columnName} {$operator} '{$to}'";
          } else {
            //
          }
        }
        if (preg_match('/^([0-9\:]+)-([0-9\:]+)$/', $s, $m)) {
          $date_1 = date('H:i:s', strtotime('01.01.2000 ' . $m[1]));
          $date_2 = date('H:i:s', strtotime('01.01.2000 ' . $m[2]));
          if (strtotime('01.01.2000 ' . $m[1]) > 0 && strtotime('01.01.2000 ' . $m[2]) > 0) {
            $return = "({$columnName} >= '{$date_1}') and ({$columnName} <= '{$date_2}')";
          } else {
            //
          }
        }
        if (preg_match('/^([0-9]+)$/', $s, $m)) {
          $hour = $m[1];
          $return = "(hour({$columnName}) = '{$hour}')";
        }
      }

      if ('year' == $type) {
        $return = 'false';

        if (preg_match('/^([\>\<=\!]{1,2})?([0-9]+)$/', $s, $m)) {
          $operator = (in_array($m[1], ['=', '!=', '<>', '>', '<', '>=', '<=']) ? $m[1] : '=');
          if (is_numeric($m[2])) {
            $return = "{$columnName} {$operator} '$m[2]'";
          } else {
            //
          }
        }
        if (preg_match('/^([0-9\:]+)-([0-9\:]+)$/', $s, $m)) {
          if (is_numeric($m[1]) && is_numeric($m[2])) {
            $return = "({$columnName} >= '{$m[1]}') and ({$columnName} <= '{$m[2]}')";
          } else {
            //
          }
        }
        if (preg_match('/^([0-9]+)$/', $s, $m)) {
          $return = "({$columnName} = '{$m[1]}')";
        }
      }

      if ($not) {
        $return = " not( {$return} ) ";
      }
    } elseif (is_array($s) && count($s) > 1) {
      foreach ($s as $val) {
        $wheres[] = $this->columnFilter($model, $columnName, $val, $column);
      }
      $return = implode(' or ', $wheres);
    } elseif (is_array($w) && count($w) > 1) {
      foreach ($w as $val) {
        $wheres[] = $this->columnFilter($model, $columnName, $val, $column);
      }
      $return = implode(' and ', $wheres);
    }

    return $return;
  }





  public function where($model, $filterValues)
  {
    $having = "TRUE";

    if (is_array($filterValues)) {
      foreach ($filterValues as $columnName => $filterValue) {
        if (empty($filterValue)) continue;

        if (strpos($columnName, "LOOKUP___") === 0) {
          list($dummy, $srcColumnName, $lookupColumnName) = explode("___", $columnName);

          $srcColumn = $model->columns()[$srcColumnName];
          $lookupModel = $this->adios->getModel($srcColumn['model']);

          $having .= " and (" . $this->columnFilter(
            $lookupModel,
            $columnName,
            $filterValue,
            $lookupModel->columns()[$lookupColumnName]
          ) . ")";
        } else {
          $having .= " and (" . $this->columnFilter(
            $model,
            $columnName,
            $filterValue
          ) . ")";
        }
      }
    }

    return $having;
  }



}
