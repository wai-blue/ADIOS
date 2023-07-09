<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\UI\Table;

/**
 * @package UI\Actions\Table
 */
class Search extends \ADIOS\Core\Action
{
  public function render()
  {
    $tableSearch = new \ADIOS\Core\Views\TableSearch($this->adios, $this->params);
    return $tableSearch->render();
  }
}
