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

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    parent::__construct($app, $params);
    $this->permission = $this->params['model'] . ':Read';
    $this->model = $this->app->getModel($this->params['model']);
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
      return $this->model->tableDescribe($this->params);
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage()
      ];
    }
  }

  public function getColumnsForDataQuery(): array {
    return $this->model->columns();
  }

  public function prepareLoadRecordQuery(): \Illuminate\Database\Eloquent\Builder {
    $params = $this->params;

    $search = null;
    if (isset($params['search'])) {
      $search = strtolower(Str::ascii($params['search']));
    }

    $columns = $this->model->columns();

    $query = $this->model->prepareLoadRecordQuery(true);

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
      foreach ($columns as $columnName => $column) {
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
    }

    if (isset($params['orderBy'])) {
      $query->orderBy(
        $params['orderBy']['field'],
        $params['orderBy']['direction']);
    }

    return $query;
  }

  public function postprocessData(array $data): array {
    if (is_array($data['data'])) {
      foreach ($data['data'] as $key => $value) {
        if (isset($value['id'])) {
          $data['data'][$key]['id'] = \ADIOS\Core\Helper::encrypt($value['id']);
        }
      }
    }

    return $data;
  }

  public function loadData(): array {
    $this->query = $this->prepareLoadRecordQuery();

    $this->itemsPerPage = (int) $this->params['itemsPerPage'] ?? 15;

    // Laravel pagination
    $data = $this->query->paginate(
      $this->itemsPerPage,
      ['*'],
      'page',
      $this->params['page'])->toArray()
    ;

    $data = $this->postprocessData($data);

    if (!is_array($data)) $data = [];

    return $data;
  }

  public function recordDelete() {
    return $this->model->recordDelete((int) $this->params['id']);
  }




  public function renderJson(): ?array {
    try {
      $return = [];

      switch ($this->params['action']) {
        case 'getParams': $return = $this->getParams(); break;
        case 'loadData': $return = $this->loadData(); break;
        case 'deleteRecord': $return = $this->recordDelete(); break;
      }

      if (!is_array($return)) {
        return [];
      } else {
        return $return;
      }
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
