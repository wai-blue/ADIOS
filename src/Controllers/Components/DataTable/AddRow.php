<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\DataTable;

/**
 * @package Components\Controllers\DataTable
 */
class AddRow extends \ADIOS\Core\Controller {
  public function render($params = []) {
    try {
      $sessionParams = (array) $_SESSION[_ADIOS_ID]['views'][$this->params['uid']];

      $tmpModel = $this->app->getModel($sessionParams['model']);

      return $tmpModel->insertGetId($sessionParams['defaultValues']);
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      return $e->getMessage();
    }
  }
}