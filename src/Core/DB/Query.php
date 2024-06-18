<?php

namespace ADIOS\Core\DB;

class Query
{
  // query types
  const select = 1;
  const insert = 2;
  const update = 3;
  const delete = 4;

  // logic operators
  const logicAnd = 'and';
  const logicOr = 'or';

  // columns enumerators
  const allColumnsWithLookups = 1;
  const allColumnsWithoutLookups = 2;

  // statement types
  const selectModifier    = 1;
  const column            = 2;
  const join              = 3;
  const where             = 4;
  const whereRaw          = 5;
  const having            = 6;

  const havingRaw         = 8;
  const order             = 9;
  const orderRaw          = 10;
  const limit             = 11;
  const set               = 12;
  const setOnDuplicateKey = 13;

  // select modifiers
  const countRows = 1;
  const distinct = 2;
  const distinctRow = 3;
  const tableAlias = 4;

  // operators (for where and having)
  const equals = 1;
  const like = 2;
  const columnFilter = 3; // special type of operator


  // private properties
  private ?\ADIOS\Core\Loader $app = NULL;
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

    $this->app = $db->app;
  }

  /**
   * @param array $statement
   * 
   * @return void
   */
  public function add(array $statement): void
  {
    $this->statements[] = $statement;
  }

  /**
   * @return int
   */
  public function getType(): int
  {
    return $this->type;
  }

  /**
   * @return \ADIOS\Core\Model
   */
  public function getModel(): \ADIOS\Core\Model
  {
    return $this->model;
  }

  /**
   * @param int $type
   * 
   * @return array
   */
  public function getStatements(int $type = 0): array
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
    string $tableAlias,
    bool $followLookups,
    int $level = 0
  ): void
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
        $lookupModelClass = str_replace('/', '\\', $modelColumnParams['model']);
        $lookupModel = new $lookupModelClass($this->app);
        $lookupTableAlias = $modelColumn . ':LOOKUP';

        $this->add([
          self::column,
          $lookupModel->lookupSqlValue($lookupTableAlias),
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
          $tableAlias,
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
  public function columns(array $columns = []): \ADIOS\Core\DB\Query
  {

    $tableAlias = $this->model->getFullTableSqlName();

    $selectModifiers = $this->getStatements(self::selectModifier);
    foreach ($selectModifiers as $modifier) {
      if ($modifier[1] == self::tableAlias) {
        $tableAlias = $modifier[2];
      }
    }

    foreach ($columns as $column) {
      if ($column === self::allColumnsWithLookups) {
        $this->addColumnsFromModel(
          $this->model,
          $tableAlias,
          TRUE
        );
      } else if ($column === self::allColumnsWithoutLookups) {
        $this->addColumnsFromModel(
          $this->model,
          $tableAlias,
          FALSE
        );
      } else if ($column === '*' || $column[0] === '*') {
        $this->add([
          self::column,
          '*',
          ''
        ]);
      } else {
        $this->add([
          self::column,
          $column[0],
          $column[1] ?? $column[0]
        ]);
      }
    }
    
    return $this;
  }

  /**
   * @param array $wheres
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function where(array $wheres = []): \ADIOS\Core\DB\Query
  {
    foreach ($wheres as $where) {
      $this->add([
        self::where,
        $where[0], // column name
        $where[1], // operator
        $where[2], // filter value
      ]);
    }

    return $this;
  }

  public function whereId(int $id): \ADIOS\Core\DB\Query
  {
    return $this->where([
      [ 'id', '=', $id ]
    ]);
  }

  /**
   * @param string $where
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function whereRaw(string $where): \ADIOS\Core\DB\Query
  {
    $this->add([self::whereRaw, $where]);
    return $this;
  }

  /**
   * @param array $havings
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function having(array $havings = []): \ADIOS\Core\DB\Query
  {
    foreach ($havings as $logic => $having) {
      array_unshift($having, self::having);
      $this->add($having);
      // [
      //   $logic, // logic (and / or)
      //   $having[0], // column name
      //   $having[1], // operator
      //   $having[2], // filter value
      // ]);
    }

    return $this;
  }

  /**
   * @param string $having
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function havingRaw(string $having): \ADIOS\Core\DB\Query
  {
    $this->add([self::havingRaw, $having]);
    return $this;
  }

  /**
   * @param array $orders
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function order(array $orders = []): \ADIOS\Core\DB\Query
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
   * @param string $order
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function orderRaw(string $order): \ADIOS\Core\DB\Query
  {
    $this->add([self::orderRaw, $order]);
    return $this;
  }

  /**
   * @param int $start
   * @param int $count
   * 
   * @return \ADIOS\Core\DB\Query
   */
  public function limit(int $start, int $count): \ADIOS\Core\DB\Query
  {
    $this->add([
      self::limit,
      $start,
      $count
    ]);

    return $this;
  }

  public function set(array $values = []): \ADIOS\Core\DB\Query
  {
    foreach ($values as $column => $value) {
      $this->add([
        self::set,
        $column,
        $value
      ]);
    }

    return $this;
  }

  public function onDuplicateKey(array $values = []): \ADIOS\Core\DB\Query
  {
    foreach ($values as $column => $value) {
      $this->add([
        self::setOnDuplicateKey,
        $column,
        $value
      ]);
    }

    return $this;
  }

  /**
   * @return string
   */
  public function buildSql(): string
  {
    return $this->db->buildSql($this);
  }

  /**
   * @return array
   */
  public function execute()
  {
    if (
      $this->type == self::select
      && empty($this->getStatements(self::column))
    ) {
      throw new \ADIOS\Core\Exceptions\DBException("Query has no columns to select. Use columns() method.");
    }
    $result = $this->db->query($this->buildSql(), $this->model);

    switch ($this->type) {
      case self::insert:
        $returnValue = $this->db->insertedId();
      break;
      default:
        $returnValue = $result;
      break;
    }

    return $returnValue;
  }

  /**
   * @return array
   */
  public function fetch(string $keyBy = "id"): array
  {
    return $this->db->fetchRaw(
      $this->buildSql(),
      $keyBy,
      $this->model
    );
  }

  /**
   * @return array
   */
  public function fetchOne(): array
  {
    $item = reset($this->fetch());
    if (!is_array($item)) $item = [];
    return $item;
  }

  /**
   * @return int
   */
  public function countRowsFromLastSelect(): int
  {
    return 0;
  }



  public static function isValidLogic(string $logic): bool
  {
    return in_array($logic, [self::logicAnd, self::logicOr]);
  }

}