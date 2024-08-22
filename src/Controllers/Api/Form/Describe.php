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
    $description = [
      'columns' => $this->model->columns(),
      'defaultValues' => $this->model->recordDefaultValues(),
      // 'relations' => $this->model->recordRelations(),
    ];

    $description['permissions']['canRead'] = $this->app->permissions->granted($this->params['model'] . ':Read');
    $description['permissions']['canCreate'] = $this->app->permissions->granted($this->params['model'] . ':Create');
    $description['permissions']['canUpdate'] = $this->app->permissions->granted($this->params['model'] . ':Update');
    $description['permissions']['canDelete'] = $this->app->permissions->granted($this->params['model'] . ':Delete');

    return $description;

  }
}
