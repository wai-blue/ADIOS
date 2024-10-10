<?php

namespace ADIOS\Controllers\Api\Config;

class Set extends \ADIOS\Core\ApiController {
  protected ?\Illuminate\Database\Eloquent\Builder $query = null;

  public \ADIOS\Core\Model $model;

  function __construct(\ADIOS\Core\Loader $app, array $params = [])
  {
    parent::__construct($app, $params);

    $modelClass = \ADIOS\Core\Factory::create('Models/Config', [$this->app]);
    $this->permission = $modelClass . ':Update';
    $this->model = $this->app->getModel($modelClass);
  }

  public function response(): array
  {
    $path = $this->app->params['path'] ?? '';
    $value = $this->app->params['value'] ?? '';

    $idCfg = null;

    if (!empty($path)) {
      $cfg = $this->model->eloquent->where('path', $path)->first()?->toArray();
      if ($cfg['id'] > 0) {
        $this->model->eloquent->where('id', $cfg['id'])->update(['value' => $value]);
        $idCfg = $cfg['id'];
      } else {
        $idCfg = $this->model->eloquent->create(['path' => $path, 'value' => $value])->id;
      }
    }

    return [
      'status' => (int) $idCfg > 0
    ];
  }

}
