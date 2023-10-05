<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\UI\Table;

/**
 * @package UI\Controllers\Table
 */
class PrintPdf extends \ADIOS\Core\Controller {
  public function render($params = []) {
    return $this->adios->view->PrintPdf($this->params)->render();
  }
}
