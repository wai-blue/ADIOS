<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\UI\DataTable;

/**
 * @package UI\Actions\DataTable
 */
class AddRow extends \ADIOS\Core\Action {
  public function render($params = []) {
    try {
      $sessionParams = (array) $_SESSION[_ADIOS_ID]['views'][$this->params['uid']];

      $tmpModel = $this->adios->getModel($sessionParams['model']);

      return $tmpModel->insertGetId($sessionParams['defaultValues']);
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      return $e->getMessage();
    }
  }
}