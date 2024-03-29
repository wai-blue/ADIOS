<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components;

use Illuminate\Support\Str;

/**
 * @package Components\Controllers\Table
 */
class Table extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = true;

  protected ?\Illuminate\Database\Eloquent\Builder $query = null;

  public \ADIOS\Core\Model $model;
  public array $data = [];
  private int $itemsPerPage = 15;

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    $this->permission = $this->params['model'] . ':Read';
  }

  /**
  * React component take this argument type for displaying in table
  * so in some case replace type for own custom type
  */
  private function getColumnType($columnType): string {
    switch ($columnType) {
      case 'datetime':
      case 'date':
      case 'time': return 'string';
      default: return $columnType;
    }
  }

  public function getParams() {
    try {
      $params = $this->params;
      $model = $this->adios->getModel($this->params['model']);

      $params = \ADIOS\Core\Helper::arrayMergeRecursively($params, $model->tableParams ?? []);

      $params['columns'] = \ADIOS\Core\Helper::arrayMergeRecursively($params['columns'] ?? [], $model->columns());
      $params['columns'] = array_filter($params['columns'], function($column) {
        return ($column['show'] ?? FALSE);
      });

      $params['canRead'] = $this->adios->permissions->granted($this->params['model'] . ':Read');
      $params['canCreate'] = $this->adios->permissions->granted($this->params['model'] . ':Create');
      $params['canUpdate'] = $this->adios->permissions->granted($this->params['model'] . ':Update');
      $params['canDelete'] = $this->adios->permissions->granted($this->params['model'] . ':Delete');
      $params['readonly'] = !($params['canUpdate'] || $params['canCreate']);

      $params['folderUrl'] = $model->getFolderUrl();

      return $params;
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }


  public function prepareDataQuery(): \Illuminate\Database\Eloquent\Builder {
    $params = $this->params;
    $this->itemsPerPage = (int) $params['itemsPerPage'] ?? 15;

    $search = null;
    if (isset($params['search'])) {
      $search = strtolower(Str::ascii($params['search']));
    }

    $this->model = $this->adios->getModel($this->params['model']);

    $tmpColumns = $this->model->columns();

    $selectRaw = [];
    $withs = [];
    $joins = [];

    foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
      if (!isset($tmpColumnDefinition['relationship'])) {
        $selectRaw[] = $this->model->getFullTableSqlName().'.'.$tmpColumnName;
      }
    }

    // LOOKUPS and RELATIONSHIPS
    foreach ($tmpColumns as $columnName => $column) {
      if ($column['type'] == 'lookup') {
        $lookupModel = $this->adios->getModel($column['model']);
        $lookupTableName = $lookupModel->getFullTableSqlName();
        $joinAlias = 'join_' . $columnName;
        $lookupSqlValue = "(" .
          str_replace("{%TABLE%}.", '', $lookupModel->lookupSqlValue())
          . ") as lookupSqlValue";

        $selectRaw[] = "(" .
          str_replace("{%TABLE%}", $joinAlias, $lookupModel->lookupSqlValue())
          . ") as `{$columnName}:LOOKUP`"
        ;

        $joins[] = [
          $lookupTableName . ' as ' . $joinAlias,
          $joinAlias.'.id',
          '=',
          $this->model->getFullTableSqlName().'.'.$columnName
        ];

        $withs[$columnName] = function ($query) use ($lookupSqlValue) {
          $query->selectRaw('id, ' . $lookupSqlValue);
        };
      } else if (isset($column['relationship'])) {
        $withs[$columnName] = function ($query) {
          $query->pluck('name');
        };
      }
    }

    $query = $this->model;
    // TODO: Toto je pravdepodobne potencialna SQL injection diera. Opravit.
    $query = $query->selectRaw(implode(",", $selectRaw))->with($withs);

    foreach ($joins as $join) {
      $query->leftJoin($join[0], $join[1], $join[2], $join[3]);
    };

    // FILTER BY
    if (isset($params['filterBy'])) {
      // TODO
    }

    // WHERE
    if (isset($params['where']) && is_array($params['where'])) {
      foreach ($params['where'] as $where) {
        $query->where($where[0], $where[1], $where[2]);
      }
    }

    // Search
    if ($search !== null) {
        foreach ($tmpColumns as $columnName => $column) {
          if (isset($column['enumValues'])) {
            foreach ($column['enumValues'] as $enumValueKey => $enumValue) {
              if (str_contains(strtolower(Str::ascii($enumValue)), $search)) {
                $query->orHaving($columnName, $enumValueKey);
              }
            }
          }

          if ($column['type'] == 'lookup') {
            $query->orHaving($columnName.':LOOKUP', 'like', "%{$search}%");
          } else {
            $query->orHaving($columnName, 'like', "%{$search}%");
          }
        }
      // $query->where(function ($query) use ($params, $tmpColumns) {
      //   foreach ($tmpColumns as $columnName => $column) {
      //     if ($column['type'] == 'lookup') {
      //       $query->orWhere($this->model->getFullTableSqlName().'.'.$columnName, 'like', "%{$params['search']}%");
      //     } else {
      //       $query->orWhere($this->model->getFullTableSqlName().'.'.$columnName, 'like', "%{$params['search']}%");
      //     }
      //   }
      // });

      // $query->having(function ($query) use ($params, $tmpColumns) {
      //   foreach ($tmpColumns as $columnName => $column) {
      //     if ($column['type'] == 'lookup') {
      //       $query->orHaving($columnName.':LOOKUP', 'like', "%{$params['search']}%");
      //     }
      //   }
      // });
    }
    // _var_dump($query->toSql());

    if (isset($params['sortBy'])) {
      $query->orderBy(
        $params['sortBy']['field'],
        $params['sortBy']['sort']);
    } else {
      $query->orderBy('id', 'DESC');
    }

    return $query;
  }

  public function postprocessData(array $data): array {
    return $data;
  }

  public function loadData() {
    $this->query = $this->prepareDataQuery();

    // Laravel pagination
    $data = $this->query->paginate(
      $this->itemsPerPage,
      ['*'],
      'page',
      $this->params['page'])->toArray()
    ;
// _var_dump($data);
    $data = $this->postprocessData($data);

    return $data;
  }




  public function renderJson() {
    try {
      return [
        'params' => ($this->params['returnParams'] ? $this->getParams() : []),
        'data' => ($this->params['returnData'] ? $this->loadData() : []),
      ];
    } catch (QueryException $e) {
      http_response_code(500);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }

}
