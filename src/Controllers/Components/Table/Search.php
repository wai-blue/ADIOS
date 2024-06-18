<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Table;

/**
 * @package Components\Controllers\Table
 */
class Search extends \ADIOS\Core\Controller
{
  public function render()
  {
    $tableSearch = new \ADIOS\Core\ViewsWithController\TableSearch($this->app, $this->params);
    return $tableSearch->render();
  }
}
