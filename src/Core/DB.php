<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

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

  public ?\ADIOS\Core\Loader $adios = null;

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
  public function __construct($adios, $params)
  {
    $this->adios = $adios;

    $this->db_host = $params['db_host'];
    $this->db_port = $params['db_port'];
    $this->db_user = $params['db_user'];
    $this->db_password = $params['db_password'];
    $this->db_name = $params['db_name'];
    $this->db_codepage = $params['db_codepage'];

    $this->tables = [];

    if (!empty($this->db_host)) {
      $this->connect();

      $tmp = $this->showTables();
      foreach ($tmp as $value) {
        $this->existingSqlTables[] = reset($value);
      }
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
      $tmp = strtolower($tmp);
      $this->columnTypes[$tmp] = new $class($this->adios);
    }
  }

  public function isRegisteredColumnType($column_type)
  {
    return isset($this->columnTypes[$column_type]);
  }

  public function addTable($tableName, $columns, $isCrossTable = FALSE)
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
  public function fetchRaw($query, $keyBy = "id") : array
  {
    $this->query($query);

    $rows = [];

    while ($row = $this->fetchArray()) {
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
    array $modifiers = []
  ) : \ADIOS\Core\DB\Query
  {
    $query = new \ADIOS\Core\DB\Query(
      $this,
      $model,
      \ADIOS\Core\DB\Query::select
    );
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



  public function getRandomColumnValues(
    \ADIOS\Core\Model $model,
    $data = [],
    $dictionary = []
  )
  {
    $table = $model->getFullTableSqlName();

    if (is_array($this->tables[$table])) {
      foreach ($this->tables[$table] as $col_name => $col_definition) {
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

    return $data;
  }


}
