<?php

namespace ADIOS\Controllers\Api\Record;

class Lookup extends \ADIOS\Core\ApiController {
  public bool $hideDefaultDesktop = true;

  public \ADIOS\Core\Model $model;

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    parent::__construct($app, $params);
    $this->permission = $this->params['model'] . ':Read';
    $this->model = $this->app->getModel($this->params['model']);
  }

  public function prepareLoadRecordQuery(): \Illuminate\Database\Eloquent\Builder {

    $query = $this->model->prepareLoadRecordQuery();

    if ($this->params['search']) {
      $query->where(function($q) {
        foreach ($this->model->columns() as $columnName => $column) {
          $q->orWhere($this->model->table . '.' . $columnName, 'LIKE', '%' . $this->params['search'] . '%');
        }
      });
    }

    return $query;
  }

  public function response(): array
  {
    $data = $this->prepareLoadRecordQuery()->get()->toArray();

    if (is_array($data)) {
      foreach ($data as $key => $value) {
        if (isset($value['id'])) {
          $data[$key]['id'] = \ADIOS\Core\Helper::encrypt($value['id']);
        }
      }
    }

    return \ADIOS\Core\Helper::keyBy('id', $data);
  }

}
