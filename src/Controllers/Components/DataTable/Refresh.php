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
class Refresh extends \ADIOS\Core\Controller {
  public function render() {
    $tmpParams = (array) $_SESSION[_ADIOS_ID]['views'][$this->params['uid']];

    $tmpParams['refresh'] = true;

    return $this->adios->view->DataTable($tmpParams)->render();
  }
}