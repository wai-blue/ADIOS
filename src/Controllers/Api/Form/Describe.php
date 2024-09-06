<?php

namespace ADIOS\Controllers\Api\Form;

class Describe extends \ADIOS\Core\ApiController {
  public \ADIOS\Core\Model $model;

  function __construct(\ADIOS\Core\Loader $app, array $params = [])
  {
    parent::__construct($app, $params);
    $this->permission = $this->params['model'] . ':Read';
    $this->model = $this->app->getModel($this->params['model']);
  }

  public function response(): array
  {
    return $this->model->formDescribe($this->params);
  }
}
