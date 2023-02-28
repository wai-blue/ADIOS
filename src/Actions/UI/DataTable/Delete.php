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
class Delete extends \ADIOS\Core\Action {

  public function render() {
    try {
      $sessionParams = (array) $_SESSION[_ADIOS_ID]['views'][$this->params['uid']];

      $tmpModel = $this->adios->getModel($sessionParams['model']);

      if (is_numeric($this->params['id'])) {
        return $tmpModel->formDelete($this->params['id']);
      } else {
        throw new \ADIOS\Core\Exceptions\GeneralException("Nothing to delete.");
      }
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      return $e->getMessage();
    }
  }
}