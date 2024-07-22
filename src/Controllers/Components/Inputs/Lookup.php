<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Inputs;

/**
 * @package Components\Controllers\Lookup
 */
class Lookup extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = true;

  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    parent::__construct($app, $params);
    $this->permission = $this->params['model'] . ':Read';
  }

  public function prepareLoadRecordQuery(): \Illuminate\Database\Eloquent\Builder {
    $tmpModel = $this->app->getModel($this->params['model']);

    $lookupSqlValue = "(" .
      str_replace("{%TABLE%}.", '', $tmpModel->lookupSqlValue())
      . ") as text";

    $query = $tmpModel->prepareLoadRecordQuery()->selectRaw('id, ' . $lookupSqlValue);

    if ($this->params['search']) {
      foreach ($tmpModel->columns() as $columnName => $column) {
        $query->orWhere($columnName, 'LIKE', '%' . $this->params['search'] . '%');
      }
    }

    return $query;
  }

  public function renderJson(): ?array { 
    try {
      return [
        'data' => \ADIOS\Core\Helper::keyBy('id', $this->prepareLoadRecordQuery()->get()->toArray())
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
