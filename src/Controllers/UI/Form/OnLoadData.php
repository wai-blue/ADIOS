<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\UI\Form;

/**
 * @package UI\Controllers\Table
 */
class OnLoadData extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  public function renderJson() { 
    $tmpModel = $this->adios->getModel($this->params['model']);

    return [
      'columns' => $tmpModel->columns()
    ];
  }

}
