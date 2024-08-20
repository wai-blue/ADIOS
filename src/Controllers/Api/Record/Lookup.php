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

    $lookupSqlValue = "(" .
      str_replace("{%TABLE%}.", '', $this->model->lookupSqlValue())
      . ") as text";

    $query = $this->model->prepareLoadRecordQuery()->selectRaw('id, ' . $lookupSqlValue);

    if ($this->params['search']) {
      $query->where(function($q) {
        foreach ($this->model->columns() as $columnName => $column) {
          $q->orWhere($columnName, 'LIKE', '%' . $this->params['search'] . '%');
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
          $data[$key]['id'] = base64_encode(openssl_encrypt($value['id'], 'AES-256-CBC', _ADIOS_ID, 0, _ADIOS_ID));
        }
      }
    }

    return \ADIOS\Core\Helper::keyBy('id', $data);
  }

}
