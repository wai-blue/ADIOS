<?php

namespace ADIOS\Core\DB;

class Query
{
  const select = 1;
  const insert = 2;
  const update = 3;
  const delete = 4;

  const allColumnsWithLookups = 1;

  private const statementColumn = 1;
  private const statementJoin = 2;

  private ?\ADIOS\Core\Loader $adios = NULL;
  private ?\ADIOS\Core\DB $db = NULL;
  private ?\ADIOS\Core\Model $model = NULL;
  private int $type = 0;
  private array $statements = [];

  public function __construct(\ADIOS\Core\DB $db, \ADIOS\Core\Model $model, int $type)
  {
    $this->db = $db;
    $this->model = $model;
    $this->type = $type;

    $this->adios = $db->adios;
  }

  private function add(array $statement) : void
  {
    $this->statements[] = $statement;
  }

  public function getType() : int
  {
    return $this->type;
  }

  public function getStatements() : array
  {
    return $this->statements;
  }

  private function addColumnsFromModel(
    \ADIOS\Core\Model $model,
    int $level = 0,
    string $tableAlias = ''
  ) : void
  {
    foreach ($model->columns() as $modelColumn => $modelColumnParams) {
      $this->add([
        self::statementColumn,
        (empty($tableAlias) ? '' : $tableAlias . '.') . $modelColumn,
        (empty($tableAlias) ? '' : $tableAlias . '_') . $modelColumn
      ]);

      if ($level < 1 && isset($modelColumnParams['model'])) {
        $lookupModelClass = '\\ADIOS\\' . str_replace('/', '\\', $modelColumnParams['model']);
        $lookupModel = new $lookupModelClass($this->adios);
        $lookupTableAlias = $lookupModel->getFullTableSqlName() . '___' . $modelColumn;

        $this->addColumnsFromModel(
          $lookupModel,
          $level + 1,
          $lookupTableAlias
        );

        $this->add([
          self::statementJoin,
          $lookupTableAlias,
          $modelColumn
        ]);
      }
    }
  }

  // left join table on table.id

  public function columns(array $columns = []) : \ADIOS\Core\DB\Query
  {
    foreach ($columns as $column) {
      if ($column === self::allColumnsWithLookups) {
        $this->addColumnsFromModel($this->model);
      }
    }
    return $this;
  }

  public function build() : string
  {
    return '';
  }

  public function fetch()
  {
    $sql = $this->db->buildSql($this);
    return $this->db->getRowsRaw($sql);
  }
}