<?php

namespace ADIOS\Controllers\Api\Record;

class Get extends \ADIOS\Core\ApiController {
  protected ?\Illuminate\Database\Eloquent\Builder $query = null;

  public \ADIOS\Core\Model $model;

  function __construct(\ADIOS\Core\Loader $app, array $params = [])
  {
    parent::__construct($app, $params);
    $this->permission = $this->params['model'] . ':Read';
    $this->model = $this->app->getModel($this->params['model']);
  }

  public function response(): array
  {
    if ($this->params['id'] <= 0) {
      $record = $this->model->recordDefaultValues();
    } else {
      $record = $this->model->recordGet(function($q) { $q->where('id', $this->params['id']); });
    }

    return $record;
  }

}
