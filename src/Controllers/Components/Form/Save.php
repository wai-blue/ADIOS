<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Form;

/**
 * @package Components\Controllers\Form
 */
class Save extends \ADIOS\Core\Controller {
  public function render($params = []) {
    $saveParams = $params['values'] ?? [];
    $saveParams['id'] = $params['id'];

    return $this->adios
      ->getModel($params['model'])
      ->recordSave($saveParams)
    ;

  }
}
