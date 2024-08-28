<?php

namespace ADIOS\Controllers\Api\Record;

use Illuminate\Support\Str;

class GetList extends \ADIOS\Core\ApiController {
  public \ADIOS\Core\Model $model;
  protected ?\Illuminate\Database\Eloquent\Builder $query = null;
  public array $data = [];
  private int $itemsPerPage = 15;

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    parent::__construct($app, $params);
    $this->permission = $this->params['model'] . ':Read';
    $this->model = $this->app->getModel($this->params['model']);
  }

  public function prepareLoadRecordQuery(): \Illuminate\Database\Eloquent\Builder
  {
    $params = $this->params;

    $search = null;
    if (isset($params['search'])) {
      $search = strtolower(Str::ascii($params['search']));
    }

    $columns = $this->model->columns();
    $relations = $this->model->relations;

    $query = $this->model->prepareLoadRecordQuery($search !== null);

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
          $query->orHaving($columnName.'_lookupText_', 'like', "%{$search}%");
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
          $data['data'][$key]['_idHash_'] = \ADIOS\Core\Helper::encrypt($value['id'], '', true);
        }
      }
    }

    return $data;
  }

  public function response(): array
  {
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
}
