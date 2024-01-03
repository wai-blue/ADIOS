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
class OnLoadData extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  public function renderJson() { 
    try {
      $tmpModel = $this->adios->getModel($this->params['model']);

      $inputs = [];
      if (isset($this->params['id']) && (int) $this->params['id'] > 0) {
        $inputs = $tmpModel->find($this->params['id']);
      }

      return [
        'inputs' => $inputs
      ];
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      // TODO: Error
    }
  }

}
