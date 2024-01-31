<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Form;

/**
 * @package Components\Controllers\Table
 */
class OnSave extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    $this->permissionName = $this->params['model'] . ':Update';
  }

  public function renderJson() {
    try {
      $params = $this->params;

      $tmpModel = $this->adios->getModel($params['model']);

      $tmpModel->recordSave($params['inputs']);

      return [
        'status' => 'success',
        'message' => isset($params['inputs']['id']) ? 'Záznam uložený' : 'Pridaný nový záznam'
      ];
    } catch (\ADIOS\Core\Exceptions\RecordSaveException $e) {
      http_response_code(422);

      $invalidInputs = json_decode($e->getMessage());

      return [
        'status' => 'error',
        'message' => 'Neboli vyplnené všetky povinné polia',
        'invalidInputs' => $invalidInputs
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
