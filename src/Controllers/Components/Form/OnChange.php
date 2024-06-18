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
class OnChange extends \ADIOS\Core\Controller {

  public function render() {
    try {
      $tmpModel = $this->app->getModel($this->params['model']);

      return $tmpModel->onFormChange(
        $this->params['column'],
        $this->params['formUid'],
        $this->params['formData']
      );

    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      return $e->getMessage();
    }
  }
}