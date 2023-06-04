<?php

namespace ADIOS\Core\DB;

class Query
{
  // query types
  const select = 1;
  const insert = 2;
  const update = 3;
  const delete = 4;

  // columns enumerators
  const allColumnsWithLookups = 1;
  const allColumnsWithoutLookups = 2;

  // statement type
  const selectModifier = 1;
  const column = 2;
  const join = 3;
  const where = 4;
  const whereRaw = 5;
  const having = 6;
  const havingRaw = 7;
  const order = 8;
  const limit = 9;

  // select modifiers
  const countRows = 1;
  const distinct = 2;
  const distinctRow = 3;

  // operators (for where and having)
  const equals = 1;
  const columnFilter = 2; // special type of operator


  // private properties
  private ?\ADIOS\Core\Loader $adios = NULL;
  private ?\ADIOS\Core\DB $db = NULL;
  private ?\ADIOS\Core\Model $model = NULL;
  private int $type = 0;
  private array $statements = [];

  /**
   * @param \ADIOS\Core\DB $db
   * @param \ADIOS\Core\Model $model
   * @param int $type
   */
  public function __construct(\ADIOS\Core\DB $db, \ADIOS\Core\Model $model, int $type)
  {
    $this->db = $db;
    $this->model = $model;
    $this->type = $type;

    $this->adios = $db->adios;
  }

  /**
   * @param array $statement
   * 
   * @return void
   */
  public function add(array $statement) : void
  {
    $this->statements[] = $statement;
  }

  /**
   * @return int
   */
  public function getType() : int
  {
    return $this->type;
  }

  /**
   * @return \ADIOS\Core\Model
   */
  public function getModel() : \ADIOS\Core\Model
  {
    return $this->model;
  }

  /**
   * @param int $type
   * 
   * @return array
   */
  public function getStatements(int $type = 0) : array
  {
    $statements = [];
    foreach ($this->statements as $statement) {
      if ($type == 0 || $type == $statement[0]) {
        $statements[] = $statement;
      }
    }

    return $statements;
  }

  /**
   * @param \ADIOS\Core\Model $model
   * @param string $tableAlias
   * @param int $level
   * 
   * @return void
   */
  private function addColumnsFromModel(
    \ADIOS\Core\Model $model,
    string $tableAlias = '',
    bool $followLookups,
    int $level = 0
  ) : void
  {
    foreach ($model->columns() as $modelColumn => $modelColumnParams) {

      if ($level == 0) {
        $this->add([
          self::column,
          (empty($tableAlias) ? '' : $tableAlias . '.') . $modelColumn,
          $modelColumn
        ]);
      } else {
        $this->add([
          self::column,
          (empty($tableAlias) ? '' : $tableAlias . '.') . $modelColumn,
          (empty($tableAlias) ? '' : $tableAlias . ':') . $modelColumn
        ]);
      }

      if (
        $followLookups
        && isset($modelColumnParams['model'])
      ) {
        $lookupModelClass = '\\ADIOS\\' . str_replace('/', '\\', $modelColumnParams['model']);
        $lookupModel = new $lookupModelClass($this->adios);
        $lookupTableAlias = $modelColumn . ':LOOKUP';

        $this->add([
          self::column,
          str_replace("{%TABLE%}", $lookupTableAlias, $lookupModel->lookupSqlValue()),
          $modelColumn . ':LOOKUP'
        ]);

        $this->addColumnsFromModel(
          $lookupModel,
          $lookupTableAlias,
          FALSE,
          $level + 1
        );

        $this->add([
          self::join,
          $model->getFullTableSqlName(),
          $lookupModel->getFullTableSqlName(),
          $lookupTableAlias,
          $modelColumn
        ]);
      }
    }
  }

  /**
   * @param array $columns
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function columns(array $columns = []) : \ADIOS\Core\DB\Query
  {
    foreach ($columns as $column) {
      if ($column === self::allColumnsWithLookups) {
        $this->addColumnsFromModel(
          $this->model,
          $this->model->getFullTableSqlName(),
          TRUE
        );
      }
      if ($column === self::allColumnsWithoutLookups) {
        $this->addColumnsFromModel(
          $this->model,
          $this->model->getFullTableSqlName(),
          FALSE
        );
      }
    }
    return $this;
  }

  /**
   * @param array $wheres
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function where(array $wheres = []) : \ADIOS\Core\DB\Query
  {
    foreach ($wheres as $where) {
      $this->add([
        self::where,
        $where[0], // column name
        $where[1], // filter value
        $where[2], // operator
      ]);
    }

    return $this;
  }

  /**
   * @param array $wheres
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function whereRaw(array $wheres = []) : \ADIOS\Core\DB\Query
  {
    foreach ($wheres as $where) {
      $this->add([
        self::whereRaw,
        $where
      ]);
    }

    return $this;
  }

  /**
   * @param array $havings
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function having(array $havings = []) : \ADIOS\Core\DB\Query
  {
    foreach ($havings as $having) {
      $this->add([
        self::having,
        $having[0], // column name
        $having[1], // filter value
        $having[2], // operator
      ]);
    }

    return $this;
  }

  /**
   * @param array $orders
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function order(array $orders = []) : \ADIOS\Core\DB\Query
  {
    foreach ($orders as $order) {
      $this->add([
        self::order,
        $order[0],
        $order[1]
      ]);
    }

    return $this;
  }

  /**
   * @param int $start
   * @param int $count
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function limit(int $start, int $count) : \ADIOS\Core\DB\Query
  {
    $this->add([
      self::limit,
      $start,
      $count
    ]);
    return $this;
  }

  /**
   * @return array
   */
  public function fetch() : array
  {
    $sql = $this->db->buildSql($this);
    return $this->db->fetchRaw($sql);
  }

  /**
   * @return int
   */
  public function countRowsFromLastSelect() : int
  {
    return 0;
  }

}