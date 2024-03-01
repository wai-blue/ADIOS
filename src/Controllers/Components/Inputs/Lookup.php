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

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    $this->permission = $this->params['model'] . ':Read';
  }

  public function renderJson() { 
    try {
      $tmpModel = $this->adios->getModel($this->params['model']);

      $lookupSqlValue = "(" .
        str_replace("{%TABLE%}.", '', $tmpModel->lookupSqlValue())
        . ") as text";

      $tmpData = $tmpModel->selectRaw('id, ' . $lookupSqlValue);

      if ($this->params['search']) {
        foreach ($tmpModel->columns() as $columnName => $column) {
          $tmpData->orWhere($columnName, 'LIKE', '%' . $this->params['search'] . '%');
        }
      }

      $tmpData = $tmpData->get();

      $data = [];
      foreach ($tmpData as $item) {
        $data[$item['id']] = $item;
      }

      return [
        'data' => $data
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
