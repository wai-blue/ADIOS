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
class OnCreate extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = true;

  function __construct(\ADIOS\Core\Loader $adios, array $params = []) {
    parent::__construct($adios, $params);
    $this->permission = $this->params['model'] . ':Create';
  }

  public function renderJson(): ?array {
    try {
      $params = $this->params;

      $tmpModel = $this->adios->getModel($params['model']);

      $emptyRequiredInputs = $tmpModel->getEmptyRequiredInputs(
        $params['inputs'], 
        $tmpModel->getRequiredColumns());
    
      if (!empty($emptyRequiredInputs)) throw new \ADIOS\Core\Exceptions\GeneralException();

      $tmpModel->recordSave($params['inputs']);

      return [
        'status' => 'success'
      ];
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      http_response_code(422);

      return [
        'status' => 'error',
        'message' => 'Fill in all required inputs',
        'emptyRequiredInputs' => $emptyRequiredInputs
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
