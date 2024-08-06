<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\DB\Providers;

class DoctrineDBAL extends \ADIOS\Core\DB
{

  private \Doctrine\DBAL\Result $statement;

  /**
   * Connects the DB object to the database.
   *
   * @throws \ADIOS\Core\Exceptions\DBException When connection string is not configured.
   * @throws \ADIOS\Core\Exceptions\DBException When connection error occured.
   *
   * @return void
   */
  public function connect(): void
  {
    $dsnParser = new \Doctrine\DBAL\Tools\DsnParser();
    $connectionParams = $dsnParser->parse($this->app->getConfig('db/dsn', ''));
    $this->connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
  }

  function escape(string $str): string
  {
    return $this->connection->quote((string) $str);
  }

  public function showTables(): array
  {
    return $this->fetchRaw("show tables", "");
  }

  public function countRowsFromLastSelect(): int
  {
    return (int) reset($this->fetchRaw('SELECT FOUND_ROWS() as FOUND_ROWS'))['FOUND_ROWS'];
  }

  public function startTransaction(): void
  {
    $this->query('start transaction');
  }

  public function commit(): void
  {
    $this->query('commit');
  }

  public function rollback(): void
  {
    $this->query('rollback');
  }

  public function fetchArray()
  {
    return $this->statement->fetchAssociative();
  }

  public function insertedId()
  {
    return $this->connection->insert_id;
  }



  /**
   * @param mixed $value
   *
   * @return string
   */
  public function typedSqlValue($value): string
  {
    if ($value instanceof string) {
      return "'" . $this->escape($value) . "'";
    } else if (is_float($value)) {
      return (float) $value;
    } else {
      return (int) $value;
    }
  }

  /**
   * @param mixed $model
   * @param mixed $columnName
   * @param mixed $filterValue
   * @param null $column
   *
   * @return [type]
   */
  private function columnSqlFilter($model, $columnName, $filterValue, $column = NULL)
  {
    if ($column === NULL) {
      $column = $model->columns()[$columnName];
    }

    $type = $column['type'];
    $s = explode(',', $filterValue);

    if (
      ($type == 'int' && _count($column['enumValues']))
      || in_array($type, ['varchar', 'text', 'color', 'file', 'image', 'enum', 'password', 'lookup'])
    ) {
      if (_count($column['enumValues'])) {
        $w = [];
        $s = [];
        foreach ($column['enumValues'] as $evk => $evv) {
          // PATO fix 07-08-2023
          // Ak intangible a tangible (ak som vybral tangible tak vyhodnotilo nespravne
          // /lebo inTANGIBLE je to iste slovo)
          /*if (stripos($evv, $filterValue) !== FALSE) {
            $w[] = (string)$evk;
            $s[] = (string)$evk;
          }*/

          if ($evv == $filterValue) {
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
      if ($type == 'int' && _count($column['enumValues'])) {
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
      || !empty($column['enumValues'])
    ) {

      if (!_count($column['enumValues'])) {
        $s = reset($s);
      }

      if ('=' == $filterValue[0]) {
        $s = substr($filterValue, 1);
      }

      if (!_count($column['enumValues']) and '!=' == substr($s, 0, 2)) {
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

      if ($type == 'int' && _count($column['enumValues'])) {
        $return = " `{$columnName}` IN (\"" . implode('","', $s) . "\")";
        //$return = " `{$columnName}_enum_value` like '%" . $this->escape(trim($s)) . "%'";
      } else if ($type == 'varchar' && _count($column['enumValues'])) {
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

      if (
        $type == 'float'
        || $type == 'decimal'
        || ('int' == $type && !_count($column['enumValues']))
      ) {
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
        $wheres[] = $this->columnSqlFilter($model, $columnName, $val, $column);
      }
      $return = implode(' or ', $wheres);
    } elseif (is_array($w) && count($w) > 1) {
      foreach ($w as $val) {
        $wheres[] = $this->columnSqlFilter($model, $columnName, $val, $column);
      }
      $return = implode(' and ', $wheres);
    }

    return $return;
  }

  /**
   * @param \ADIOS\Core\DB\Query $query
   *
   * @return string
   */
  private function buildSqlWhere(\ADIOS\Core\DB\Query $query, ?array $wheres = NULL, string $logic = ''): string
  {

    $model = $query->getModel();

    $whereRaws = [];

    if ($wheres === NULL) {
      $wheres = $query->getStatements(\ADIOS\Core\DB\Query::where);
      $whereRaws = $query->getStatements(\ADIOS\Core\DB\Query::whereRaw);
    }

    if (!\ADIOS\Core\DB\Query::isValidLogic($logic)) $logic = \ADIOS\Core\DB\Query::logicAnd;

    // wheres and whereRaws
    $wheresArray = [];
    foreach ($wheres as $where) {
      if (is_array($where['statements'])) {
        $tmp = '(' . $this->buildSqlwhere($query, $where['statements'], $where['logic']) . ')';
        $wheresArray[] = $tmp;
      } else {
        list($statementType, $column, $operator, $value) = $where;

        if ($operator === \ADIOS\Core\DB\Query::columnFilter) {
          $wheresArray[] = $this->columnSqlFilter(
            $model,
            $column,
            $value
          );
        } else if ($operator === \ADIOS\Core\DB\Query::like) {
          if (strpos($column, '`') === FALSE) {
            $wheresArray[] = '`' . $column . '` like "%' . $this->escape($value) . '%"';
          } else {
            $wheresArray[] = $column . '  like "%' . $this->escape($value) . '%"';
          }
        } else {
          if (strpos($column, '`') === FALSE) {
            $wheresArray[] = '`' . $column . '` '. $operator . ' ' . $this->typedSqlValue($value);
          } else {
            $wheresArray[] = $column . ' '. $operator . ' ' . $this->typedSqlValue($value);
          }
        }
      }
    }

    foreach ($whereRaws as $whereRaw) {
      if (!empty($whereRaw[1])) {
        $wheresArray[] = $whereRaw[1];
      }
    }

    return (count($wheresArray) == 0 ? '' : join(' ' . $logic . ' ', $wheresArray));
  }

  /**
   * @param \ADIOS\Core\DB\Query $query
   *
   * @return string
   */
  private function buildSqlHaving(\ADIOS\Core\DB\Query $query, ?array $havings = NULL, string $logic = ''): string
  {

    $model = $query->getModel();

    $havingRaws = [];

    if ($havings === NULL) {
      $havings = $query->getStatements(\ADIOS\Core\DB\Query::having);
      $havingRaws = $query->getStatements(\ADIOS\Core\DB\Query::havingRaw);
    }

    if (!\ADIOS\Core\DB\Query::isValidLogic($logic)) $logic = \ADIOS\Core\DB\Query::logicAnd;

    // havings and havingRaws
    $havingsArray = [];
    foreach ($havings as $having) {
      if (is_array($having['statements'])) {
        $tmp = '(' . $this->buildSqlHaving($query, $having['statements'], $having['logic']) . ')';
        $havingsArray[] = $tmp;
      } else {
        list($statementType, $column, $operator, $value) = $having;

        if ($operator === \ADIOS\Core\DB\Query::columnFilter) {
          $havingsArray[] = $this->columnSqlFilter(
            $model,
            $column,
            $value
          );
        } else if ($operator === \ADIOS\Core\DB\Query::like) {
          if (strpos($column, '`') === FALSE) {
            $havingsArray[] = '`' . $column . '` like "%' . $this->escape($value) . '%"';
          } else {
            $havingsArray[] = $column . '  like "%' . $this->escape($value) . '%"';
          }
        } else {
          if (strpos($column, '`') === FALSE) {
            $havingsArray[] = '`' . $column . '` '. $operator . ' ' . $this->typedSqlValue($value);
          } else {
            $havingsArray[] = $column . ' '. $operator . ' ' . $this->typedSqlValue($value);
          }
        }
      }
    }

    foreach ($havingRaws as $havingRaw) {
      if (!empty($havingRaw[1])) {
        $havingsArray[] = $havingRaw[1];
      }
    }

    return (count($havingsArray) == 0 ? '' : join(' ' . $logic . ' ', $havingsArray));
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
  public function columnSqlValue($table, $colName, $data, $defaultValue = NULL)
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
    } else if (isset($this->columnTypes[$colType])) {
      if (isset($data[$colName])) {
        $sql = $this->columnTypes[$colType]->sqlValueString(
          $table,
          $colName,
          $data[$colName],
          [
            'null_value' => !$valueExists,
            'dumping_data' => FALSE,
            'data' => $data,
          ]
        );
      } else if ($defaultValue !== NULL) {
        $sql = "`{$colName}` = ".$defaultValue;
      }
    }

    return (empty($sql) ? "" : "{$sql}, ");
  }

  /**
   * Returns SQL string representing command which would insert a row into a database.
   *
   * @param string name of a table
   * @param array array of data to be inserted
   * @param bool if this param is TRUE, the returned string is generated exclusively for the dump_data() method
   *
   * @see columnSql
   */
  private function insertRowQuery(string $table, array $data)
  {
    $sql = '';

    if (isset($this->tables[$table]['id'])) {
      if (!isset($data['id']) || $data['id'] <= 0) {
        $sql .= "`id`=null, ";
      } else {
        $sql .= "`id`='" . $this->escape($data['id']) . "', ";
        unset($data['id']);
      }
    }

    foreach ($this->tables[$table] as $colName => $colDefinition) {
      if (!$colDefinition['virtual'] && $colName != '%%table_params%%') {
        $sql .= $this->columnSqlValue($table, $colName, $data, $colDefinition['default_value']);
      }
    }

    $sql = substr($sql, 0, -2);

    return $sql;
  }

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
  private function updateRowQuery($table, $data)
  {
    global $_FILES;

    if (is_array($_FILES)) {
      foreach ($_FILES as $key => $value) {
        if (null !== $data[$key]) {
          $data[$key] = $value;
        }
      }
    }

    $sql = '';
    foreach ($this->tables[$table] as $col_name => $col_definition) {
      if (!$col_definition['virtual']) {
        $sql .= $this->columnSqlValue($table, $col_name, $data);
      }
    }

    $sql = substr($sql, 0, -2);

    return $sql;
  }

  /**
   * @param \ADIOS\Core\DB\Query $query
   *
   * @return string
   */
  public function buildSql(\ADIOS\Core\DB\Query $query) : string
  {
    $model = $query->getModel();

    switch ($query->getType()) {
      case \ADIOS\Core\DB\Query::select:

        $selectModifiers = $query->getStatements(\ADIOS\Core\DB\Query::selectModifier);
        $columns = $query->getStatements(\ADIOS\Core\DB\Query::column);
        $joins = $query->getStatements(\ADIOS\Core\DB\Query::join);
        $orders = $query->getStatements(\ADIOS\Core\DB\Query::order);
        $orderRaws = $query->getStatements(\ADIOS\Core\DB\Query::orderRaw);
        $limits = $query->getStatements(\ADIOS\Core\DB\Query::limit);

        $tableAlias = '';

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
            case \ADIOS\Core\DB\Query::tableAlias:
              $tableAlias = $modifier[2];
            break;
          }
        }

        // columns
        $columnsArray = [];
        foreach ($columns as $column) {
          if (strpos($column[1], '`') === FALSE) {
            if (strpos($column[1], '.') === FALSE) {
              $columnsArray[] = '`' . $column[1] . '` as `' . $column[2] . '`';
            } else {
              list($tmpTable, $tmpColumn) = explode(".", $column[1]);
              $columnsArray[] = '`' . $tmpTable . '`.`' . $tmpColumn . '` as `' . $column[2] . '`';
            }
          } else {
            $columnsArray[] = $column[1] . ' as `' . $column[2] . '`';
          }
        }

        // joins
        $joinsArray = [];
        foreach ($joins as $join) {
          $joinsArray[] =
            'LEFT JOIN `' . $join[2] . '` as `' . $join[3] . '`'
            . ' ON `' .  $join[3] . '`.`id` = `' . $join[1] . '`.`' . $join[4] . '`';
        }

        // where
        $where = $this->buildSqlWhere($query);

        // having
        $having = $this->buildSqlHaving($query);

        // orders and orderRaws
        $ordersArray = [];
        foreach ($orders as $order) {

          $order[1] = trim($order[1]);
          $order[1] = '`' . implode('`.`', explode(".", str_replace('`', '', $order[1]))) . '`';

          $order[2] = strtoupper($order[2] ?? '');

          if (!in_array($order[2], ['ASC', 'DESC'])) continue;

          $ordersArray[] = $order[1] . ' ' . $order[2];
        }
        foreach ($orderRaws as $orderRaw) {
          $ordersArray[] = $orderRaw[1];
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
          . (empty($tableAlias) ? '' : ' AS ' . $tableAlias)
          . ' ' . join(' ', $joinsArray)
          . (empty($where) ? '' : ' WHERE ' . $where)
          . (empty($having) ? '' : ' HAVING ' . $having)
          . (count($ordersArray) == 0 ? '' : ' ORDER BY ' . join(', ', $ordersArray))
          . $limitSql
        ;

      break;

      case \ADIOS\Core\DB\Query::insert:
        $sqlTableName = $model->getFullTableSqlName();

        $values = $query->getStatements(\ADIOS\Core\DB\Query::set);
        $valuesOnDuplicateKey = $query->getStatements(\ADIOS\Core\DB\Query::setOnDuplicateKey);

        $data = [];
        foreach ($values as $value) {
          $data[$value[1]] = $value[2];
        }

        $sql = 'INSERT INTO `' . $sqlTableName . '` SET ';
        $sql .= $this->insertRowQuery($sqlTableName, $data, TRUE);

        if (count($valuesOnDuplicateKey) > 0) {

          $data = [];
          foreach ($valuesOnDuplicateKey as $value) {
            $data[$value[1]] = $value[2];
          }

          $sql .= ' ON DUPLICATE KEY UPDATE ';
          $sql .= $this->insertRowQuery($sqlTableName, $data, TRUE);
        }

      break;

      case \ADIOS\Core\DB\Query::update:
        $sqlTableName = $model->getFullTableSqlName();

        $values = $query->getStatements(\ADIOS\Core\DB\Query::set);

        $data = [];
        foreach ($values as $value) {
          $data[$value[1]] = $value[2];
        }

        $where = $this->buildSqlWhere($query);

        $sql =
          'UPDATE `' . $sqlTableName . '` SET '
          . $this->updateRowQuery($sqlTableName, $data)
          . (empty($where) ? '' : ' WHERE ' . $where)
        ;

      break;

      case \ADIOS\Core\DB\Query::delete:
        $sqlTableName = $model->getFullTableSqlName();

        $where = $this->buildSqlWhere($query);

        $sql =
          'DELETE FROM `' . $sqlTableName . '`'
          . (empty($where) ? '' : ' WHERE ' . $where)
        ;

      break;
    }

    return $sql;
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

    $this->statement = $this->connection->query($query);

    return TRUE;
  }




















  /**
   * Creates given table in the SQL database. In other words: executes
   * SQL command returned by _sql_table_create.
   *
   * @param string name of a table
   * @param bool If this param is TRUE, it only returns the SQL command to be executed
   *
   */
  public function createSqlTable(string $table_name, bool $only_sql_command = false, bool $force_create = true)
  {
    $do_create = true;

    $log_status = $this->log_disabled;
    $this->log_disabled = 1;

    if (!$force_create) {
      try {
        $cnt = $this->query("select * from `{$table_name}`")->rowCount();
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
            ->sqlCreateString($table_name, $col_name, $col_definition);

          // REVIEW DD: Tato uprava ma ist do DataTypeBool.php a DataTypeBoolean.php
          // (pripadne nejake defaultne spravanie implementovat do DataType.php
          if (!in_array($col_type, ['bool', 'boolean'])) {
            if ($col_definition['required']) {
              $tmp .= 'NOT NULL';
            }
          }

          if (!empty($tmp)) {
            $sql .= "  {$tmp},\n";
          }
        }
      }

      // indexy
      foreach ($table_columns as $col_name => $col_definition) {
        if (
          $col_name != 'id'
          && !$col_definition['virtual']
          && in_array($col_definition['type'], ['lookup', 'int', 'bool', 'boolean', 'date', 'datetime'])
        ) {
          $sql .= " index `{$col_name}` (`{$col_name}`),\n";
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


  // public function createSqlForeignKeys($table)
  // {
  //   $sql = '';
  //   foreach ($this->tables[$table] as $column => $columnDefinition) {
  //     if (
  //       !$columnDefinition['disableForeignKey']
  //       && 'lookup' == $columnDefinition['type']
  //     ) {
  //       $lookupModel = $this->app->getModel($columnDefinition['model']);
  //       $foreignKeyColumn = $columnDefinition['foreignKeyColumn'] ?: "id";
  //       $foreignKeyOnDelete = $columnDefinition['foreignKeyOnDelete'] ?: "RESTRICT";
  //       $foreignKeyOnUpdate = $columnDefinition['foreignKeyOnUpdate'] ?: "RESTRICT";

  //       $sql .= "
  //         ALTER TABLE `{$table}`
  //         ADD CONSTRAINT `fk_" . md5($table . '_' . $column) . "`
  //         FOREIGN KEY (`{$column}`)
  //         REFERENCES `" . $lookupModel->getFullTableSqlName() . "` (`{$foreignKeyColumn}`)
  //         ON DELETE {$foreignKeyOnDelete}
  //         ON UPDATE {$foreignKeyOnUpdate};;
  //       ";
  //     }
  //   }

  //   if (!empty($sql)) {
  //     $this->multiQuery($sql);
  //   }
  // }















  // Dusan 5.6.2023: Vyzera to tak, ze tato metoda sa nikde nepouziva.

  // public function where($model, $filterValues)
  // {
  //   $having = "TRUE";

  //   if (is_array($filterValues)) {
  //     foreach ($filterValues as $columnName => $filterValue) {
  //       if (empty($filterValue)) continue;

  //       if (strpos($columnName, "LOOKUP___") === 0) {
  //         list($dummy, $srcColumnName, $lookupColumnName) = explode("___", $columnName);

  //         $srcColumn = $model->columns()[$srcColumnName];
  //         $lookupModel = $this->app->getModel($srcColumn['model']);

  //         $having .= " and (" . $this->columnSqlFilter(
  //           $lookupModel,
  //           $columnName,
  //           $filterValue,
  //           $lookupModel->columns()[$lookupColumnName]
  //         ) . ")";
  //       } else {
  //         $having .= " and (" . $this->columnSqlFilter(
  //           $model,
  //           $columnName,
  //           $filterValue
  //         ) . ")";
  //       }
  //     }
  //   }

  //   return $having;
  // }

  // Dusan 5.6.2023: Vyzera to tak, ze tato metoda sa nikde nepouziva.

  // /**
  //  * @param mixed $col_name
  //  * @param mixed $col_type
  //  * @param mixed $value
  //  * @param array $params
  //  *
  //  * @return [type]
  //  */
  // public function filter($col_name, $col_type, $value, $params = [])
  // {
  //   if (false !== strpos('.', $col_name)) {
  //     list($table_name, $col_name) = explode('.', $col_name);
  //   }
  //   if (is_object($this->columnTypes[$col_type])) {
  //     return ('' == $table_name ? '' : "{$table_name}.") . $this->columnTypes[$col_type]->filter($col_name, $value, $params);
  //   } else {
  //     return 'TRUE';
  //   }
  // }


}
