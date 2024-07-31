<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

use ADIOS\Core\DB\Query;
use ADIOS\Core\Exceptions\DBDuplicateEntryException;
use ADIOS\Core\Exceptions\DBException;

class DB
{
  protected float $lastQueryDurationSec;

  protected int $log_disabled = 0;

  protected string $db_host = "";
  protected string $db_port = "";
  protected string $db_user = "";
  protected string $db_password = "";
  protected string $db_name = "";
  protected string $db_codepage = "";

  public ?\ADIOS\Core\Loader $app = null;

  public array $tables = [];

  public array $columnTypes = [];
  public $connection = NULL;

  public $existingSqlTables = [];

  public bool $logQueries = FALSE;

  public string $ob = "";

  /**
   * Constructor.
   *
   * @param string Name of this element
   * @param array Array of parameters for this module
   */
  public function __construct(\ADIOS\Core\Loader $app)
  {
    $this->app = $app;
    $this->tables = [];

    $this->connect();

    $tmp = $this->showTables();
    foreach ($tmp as $value) {
      $this->existingSqlTables[] = reset($value);
    }

    $h = opendir(dirname(__FILE__) . '/DB/DataTypes');
    while (false !== ($file = readdir($h))) {
      if ('.' != $file && '..' != $file) {
        $col_type = substr($file, 0, -4);
        $this->registerColumnType($col_type);
      }
    }
  }


  public function showTables() : array
  {
    return [];
  }


  /////////////////////////////////////////////////////////////////////////////////////////////////////////
  // functions for manipulating with table definitions in the database
  /////////////////////////////////////////////////////////////////////////////////////////////////////////

  public function registerColumnType($column_type)
  {
    $class = "\\ADIOS\\Core\\DB\\DataTypes\\{$column_type}";

    if (class_exists($class)) {
      $tmp = str_replace("DataType", "", $column_type);
      
      // Type to lower if is not custom
      // REVIEW: Treba vymysliet univerzalne - neviazat na konkretne nazvy datovych typov.
      if (!in_array($tmp, ['MapPoint'])) $tmp = strtolower($tmp);
      $this->columnTypes[$tmp] = new $class($this->app);
    }
  }

  public function isRegisteredColumnType($column_type)
  {
    return isset($this->columnTypes[$column_type]);
  }

  public function addTable($tableName, $columns, $isJunctionTable = FALSE)
  {
    $this->tables[$tableName] = $columns;
  }












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
  }

  function escape(string $str) : string
  {
    return $str;
  }


  public function buildSql(\ADIOS\Core\DB\Query $query) : string
  {
    return "";
  }


  /**
   * Runs a single SQL query. Result of a query is stored in a property $db_result.
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
  }

  /**
   * Uses query() method to run multiply SQL queries. Queries are separated
   * by a given separator, which is by default ";;\n".
   *
   * @param string multiple SQL query string to run
   *
   * @see query
   * @see fetch_array
   */
  public function multiQuery(
    string $query,
    string $separator = ";;\n",
    object $initiatingModel = NULL
  )
  {
    $query = str_replace("\r\n", "\n", $query);
    foreach (explode($separator, $query) as $value) {
      $this->query(trim($value) . ';', $initiatingModel);
    }
  }

  /**
   * Uses query() method to run multiply SQL queries. Queries are separated
   * by a given separator, which is by default ";;\n".
   *
   * @param string multiple SQL query string to run
   *
   * @see query
   * @see fetch_array
   */
  public function multiQueryArray(
    array $queries,
    object $initiatingModel = NULL
  )
  {
    foreach ($queries as $query) {
      $this->query($query, $initiatingModel);
    }
  }

  public function fetchArray()
  {
    return [];
  }

  /**
   * Returns array of rows and their column values returned by SQL after
   * executing given query.
   * Uses cached tables to retrieve data for lookups. This
   * drstically decreases number of accesses to the DB.
   *
   * @param string SQL SELECT query to be executed
   *
   * @see get_row
   * @see get_column_data
   */
  public function fetchRaw(
    string $query,
    string $keyBy = "id",
    ?\ADIOS\Core\Model $model = NULL) : array
  {
    $this->query($query);

    $rows = [];

    while ($row = $this->fetchArray()) {
      if ($model !== NULL) {
        $row = $model->normalizeRowData($row);
      }

      if (empty($keyBy)) {
        $rows[] = $row;
      } else {
        $rows[$row[$keyBy]] = $row;
      }
    }

    return $rows;
  }


  public function select(
    \ADIOS\Core\Model $model,
    array $modifiers = [],
    string $tableAlias = ''
  ) : \ADIOS\Core\DB\Query
  {
    $query = new \ADIOS\Core\DB\Query(
      $this,
      $model,
      \ADIOS\Core\DB\Query::select
    );

    if (!empty($tableAlias)) {
      $query->add([
        \ADIOS\Core\DB\Query::selectModifier,
        \ADIOS\Core\DB\Query::tableAlias,
        $tableAlias
      ]);
    }

    foreach ($modifiers as $modifier) {
      $query->add([
        \ADIOS\Core\DB\Query::selectModifier,
        $modifier
      ]);
    }

    return $query;
  }

  public function insert(\ADIOS\Core\Model $model) : \ADIOS\Core\DB\Query
  {
    return new \ADIOS\Core\DB\Query($this, $model, \ADIOS\Core\DB\Query::insert);
  }

  public function update(\ADIOS\Core\Model $model) : \ADIOS\Core\DB\Query
  {
    return new \ADIOS\Core\DB\Query($this, $model, \ADIOS\Core\DB\Query::update);
  }

  public function delete(\ADIOS\Core\Model $model) : \ADIOS\Core\DB\Query
  {
    return new \ADIOS\Core\DB\Query($this, $model, \ADIOS\Core\DB\Query::delete);
  }

  public function insertedId()
  {
    return 0;
  }




  public function startTransaction(): void
  {
    // To be overriden by the provider.
  }

  public function commit(): void
  {
    // To be overriden by the provider.
  }

  public function rollback(): void
  {
    // To be overriden by the provider.
  }

  public function countRowsFromLastSelect(): int
  {
    return 0; // To be overriden by the provider.
  }


  public function getRandomColumnValues(
    \ADIOS\Core\Model $model,
    $data = [],
    $dictionary = []
  )
  {
    $table = $model->getFullTableSqlName();

    $unique = []; # Array of unique cols

    if (is_array($this->tables[$table])) {

      # Determines which columns are supposed to be unique
      foreach ($model->indexes() as $index) {
        if (
          $index['type'] == 'unique'
          && count((array) $index['columns']) == 1
        ) {
          foreach ($index['columns'] as $col) {
            $unique[] = $col;
          }
        }
      }

      # Generates a nice looking value for the column
      foreach ($this->tables[$table] as $col_name => $col_definition) {
        if ($col_name != "id" && !isset($data[$col_name])) {
          $random_val = NULL;
          if (is_array($dictionary[$col_name])) {
            $random_val = $dictionary[$col_name][rand(0, count($dictionary[$col_name]) - 1)];
          } else {
            switch ($col_definition['type']) {
              case "int":
                if (is_array($col_definition['enumValues'])) {
                  $keys = array_keys($col_definition['enumValues']);
                  $random_val = $keys[rand(0, count($keys) - 1)];
                } else {
                  $minValue = (float) ($col_definition['minValue'] ?? 0);
                  $maxValue = (float) ($col_definition['maxValue'] ?? 1000);
                  $random_val = rand($minValue, $maxValue);
                }
              break;
              case "float":
                $minValue = (float) ($col_definition['minValue'] ?? 0);
                $maxValue = (float) ($col_definition['maxValue'] ?? 1000);
                $decimals = $col_definition['decimals'] ?? 2;
                $random_val = rand($minValue * $decimals, $maxValue * $decimals) / $decimals;
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
                $randomTextValues = [
                  "Nunc ac sollicitudin ipsum. Vestibulum condimentum vitae justo quis bibendum. Fusce et scelerisque dui, eu placerat nisl. Proin ut efficitur velit, nec rutrum massa.",
                  "Integer ullamcorper lacus at nisi posuere posuere. Maecenas malesuada magna id fringilla sagittis. Nam sed turpis feugiat, placerat nisi et, gravida lacus. Curabitur porta elementum suscipit.",
                  "Praesent libero diam, vulputate sed varius eget, luctus a risus. Praesent sit amet neque commodo, varius nisl dignissim, tincidunt magna. Nunc tincidunt dignissim ligula, sit amet facilisis felis mollis vel.",
                  "Sed ut ligula luctus, ullamcorper felis nec, tristique lorem. Maecenas sit amet tincidunt enim.",
                  "Mauris blandit ligula massa, sit amet auctor risus viverra at. Cras rhoncus molestie malesuada. Sed facilisis blandit augue, eu suscipit lectus vehicula quis. Mauris efficitur elementum feugiat.",
                  "Nulla posuere dui sit amet elit efficitur iaculis. Cras elit ligula, feugiat vitae maximus quis, volutpat sit amet sapien. Vivamus varius magna fermentum dolor varius, vel scelerisque ante mollis."
                ];

                $random_val = $randomTextValues[rand(0, count($randomTextValues) - 1)];
              break;
              case "json":
                if (isset($col_definition['required']) && $col_definition['required'] == true) {
                  $random_val = '{}';
                  break;
                }

                if ($col_name == 'record_info') {
                  $random_val = json_encode($model->getNewRecordInfo());
                }
              break;
              case "varchar":
              case "password":
                $randomPasswordValues = [
                  "Nunc",
                  "Efficitur",
                  "Vulputate",
                  "Ligula luctus",
                  "Mauris",
                  "Massa",
                  "Auctor",
                  "Molestie",
                  "Malesuada",
                  "Facilisis",
                  "Augue"
                ];

                $random_val = $randomPasswordValues[rand(0, count($randomPasswordValues) - 1)];
              break;
              case 'enum':
                # TODO
                //var_dump($col_definition['enumValues']); exit;
                //$random_val = $col_definition['enumValues'];
              break;
              case 'lookup':
                if (!isset($col_definition['required']) || $col_definition['required'] == false) {
                  $random_val = null;
                  break;
                }

                if (!isset($col_definition['model'])) {
                  throw new \Exception("Model for lookup: {$col_name} is empty");
                }

                $lookupModel = $this->app->getModel($col_definition['model']);
                if ($lookupModel == NULL) throw new \Exception("Model: {$col_definition['model']} not found");

                $modelAllData = $lookupModel->eloquent->select('id')
                  ->get()
                  ->toArray()
                ;

                if (empty($modelAllData) || in_array($col_name, $unique)) {
                  if ($model->fullName == $lookupModel->fullName) break;

                  try {
                    $lookupModel->insertRandomRow();
                  } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                  }

                  $modelAllData = $lookupModel->select('id')->get()->toArray();
                }

                $modelAllDataCount = count($modelAllData);
                if ($modelAllDataCount == 0) break;

                $rand = !in_array($col_name, $unique)
                  ? rand(0, count($modelAllData) - 1)
                  : $modelAllDataCount - 1
                ;

                $random_val = $modelAllData[$rand]['id'];
              break;
            }
          }

          # Adds the count of all rows in the table in front of the value
          # if the column is supposed to be unique
          if (
            in_array($col_name, $unique)
            && $col_definition['type'] != 'lookup'
            && $col_definition['type'] != 'datetime'
            && $col_definition['type'] != 'date'
            && $col_definition['type'] != 'time'
          ) {
            $random_val = count($model->getAll()) . $random_val;
          } else if (in_array($col_name, $unique) && $col_definition['type'] == 'datetime') {
            $random_val = new \DateTime();
            $random_val->setTimestamp(strtotime('-1 year') + rand(0, 3600 * 24 * 365));

            # Runs out after 86400 rows
            $random_val->setTime(
              floor(count($model->getAll()) / 3600) % 24,
              floor(count($model->getAll()) / 60) % 60,
              count($model->getAll()) % 60
            );
            $random_val = $random_val->format("Y-m-d H:i:s");
          } else if (in_array($col_name, $unique) && $col_definition['type'] == 'date') {
            $random_val = new \DateTime();
            $random_val->modify((rand(1, 2) == 2 ? '+' : '-') . count($model->getAll()) . ' days');
            $random_val = $random_val->format('Y-m-d');
          } else if (in_array($col_name, $unique) && $col_definition['type'] == 'time') {
            # Runs out after 86400 rows
            $random_val->setTime(
              (count($model->getAll()) / 60 / 60) % 24,
              (count($model->getAll()) / 60) % 60,
              count($model->getAll()) % 60
            );
          }

          if ($random_val !== NULL) {
            if (
              $col_definition['byte_size'] != NULL
              && in_array($col_definition['type'], ['varchar', 'text'])
            ) {
              # Trims the size of the value to match the byte_size
              while (strlen(mb_convert_encoding($random_val, 'UTF-8')) > $col_definition['byte_size']) {
                $random_val = mb_substr($random_val, 0, -1);
              }
            }

            $data[$col_name] = $random_val;
          }
        }
      }
    }

    return $data;
  }


}
