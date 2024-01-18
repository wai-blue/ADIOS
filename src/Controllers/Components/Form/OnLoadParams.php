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
class OnLoadParams extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  public function renderJson() { 
    try {
      $tmpModel = $this->adios->getModel($this->params['model']);

      return array_merge(
        [
          'columns' => $tmpModel->getColumnsToShowInView('Form'),
          'folderUrl' => $tmpModel->getFolderUrl(),
        ],
        $tmpModel->defaultFormParams ?? []
      );
    } catch (\ADIOS\Core\Exceptions\GeneralException $e) {
      // TODO: Error
    }
  }

}
