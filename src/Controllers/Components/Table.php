<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components;

/**
 * @package Components\Controllers\Table
 */
class Table extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  protected ?\Illuminate\Database\Eloquent\Builder $query = null;

  public \ADIOS\Core\Model $model;
  public array $data = [];
  private int $pageLength = 15;

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    $this->permissionName = $this->params['model'] . ':Read';
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

  public function getParams(array $customParams = []) {
    try {
      $model = $this->adios->getModel($this->params['model']);
      $columns = $model->getColumnsToShowInView('Table');

      $customParams = \ADIOS\Core\HelperFunctions::arrayMergeRecursively(
        $customParams,
        $model->defaultTableParams ?? []
      );
// _var_dump($customParams);
      if (is_array($customParams['columns'])) {
        foreach ($columns as $colName => $colDef) {
          if (
            isset($customParams['columns'][$colName]['show'])
            && !$customParams['columns'][$colName]['show']
          ) {
            unset($columns[$colName]);
          } else {
            $columns[$colName]['viewParams']['Table'] = $customParams['columns'][$colName];
          }
        }

        unset($customParams['columns']);
      }

      $canRead = $this->adios->permissions->has($this->params['model'] . ':Read');
      $canCreate = $this->adios->permissions->has($this->params['model'] . ':Create');
      $canUpdate = $this->adios->permissions->has($this->params['model'] . ':Update');
      $canDelete = $this->adios->permissions->has($this->params['model'] . ':Delete');

      return \ADIOS\Core\HelperFunctions::arrayMergeRecursively(
        $customParams,
        [
          'columns' => $columns,
          // 'title' => $model->defaultTableParams['title'],
          'folderUrl' => $model->getFolderUrl(),
          // 'addButtonText' => $model->defaultTableParams['addButtonText'] ?? "Add new record",
          'canRead' => $canRead,
          'canCreate' => $canCreate,
          'canUpdate' => $canUpdate,
          'canDelete' => $canDelete,
        ]
      );
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
    $this->pageLength = (int) $params['pageLength'] ?? 15;

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
      }
      else if (isset($column['relationship'])) {
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
    if (isset($params['search'])) {
        foreach ($tmpColumns as $columnName => $column) {
          if ($column['type'] == 'lookup') {
            $query->orHaving($columnName.':LOOKUP', 'like', "%{$params['search']}%");
          } else {
            $query->orHaving($columnName, 'like', "%{$params['search']}%");
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

    // ORDER BY
    if (isset($params['orderBy'])) {
      $query->orderBy(
        $params['orderBy']['field'],
        $params['orderBy']['sort']);
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
      $this->pageLength,
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
