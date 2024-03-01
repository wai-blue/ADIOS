<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Inputs;

use Illuminate\Database\QueryException;

/**
 * @package Components\Controllers\Tags
 */
class Tags extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = true;

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    //$this->permission = $this->params['model'] . ':Read';
  }

  public function renderJson() { 
    try {
      if ($this->params['model'] == null) throw new \Exception("Unknown model");
      if ($this->params['junction'] == null) throw new \Exception("Unknown junction model");

      $tmpModel = $this->adios->getModel($this->params['model']);

      $junctionData = $tmpModel->junctions[$this->params['junction']] ?? null;
      if ($junctionData == null) {
        throw new \Exception("Junction {$this->params['junction']} in {$this->params['model']} not found");
      }

      $junctionModel = $this->adios->getModel($junctionData['junctionModel']);
      $junctionOptionKeyColumn = $junctionModel->columns()[$junctionData['optionKeyColumn']];

      $junctionOptionKeyModel = $this->adios->getModel($junctionOptionKeyColumn['model']);
      $data = $junctionOptionKeyModel->all();

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
