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
class Delete extends \ADIOS\Core\Controller {

  public function render() {
    try {
      $tmpModel = $this->app->getModel($this->params['model']);

      if (is_numeric($this->params['id'])) {
        $tmpModel->recordDelete($this->params['id']);
      } else {
        throw new \ADIOS\Core\Exceptions\GeneralException("Nothing to delete.");
      }

      return "1";
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      return $e->getMessage();
    }
  }
}