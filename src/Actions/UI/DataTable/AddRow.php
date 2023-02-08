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
      $tmpModel = $this->adios->getModel($this->params['model']);

      return $tmpModel->insertGetId(json_decode($this->params['default_values'], TRUE));
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      return $e->getMessage();
    }
  }
}