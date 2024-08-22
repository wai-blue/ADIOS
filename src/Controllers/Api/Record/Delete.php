<?php

namespace ADIOS\Controllers\Api\Record;

class Delete extends \ADIOS\Core\ApiController {
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
    $hash = $this->params['hash'] ?? '';
    $id = (int) $this->params['id'] ?? '';
    if ($hash == \ADIOS\Core\Helper::encrypt($id, '', true)) {

      $error = '';
      try {
        $status = $this->model->recordDelete($id);
      } catch (\Throwable $e) {
        $error = $e->getMessage();
      }

      $return = [
        'id' => $id,
        'status' => $status,
      ];

      if ($error) $return['error'] = $error;

      return $return;
    } else {
      return [
        'id' => $id,
        'status' => false,
      ];
    }
  }

}
