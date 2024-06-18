<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components;

/**
 * @package Components\Controllers
 */
class Html extends \ADIOS\Core\Controller
{
  function render()
  {
    return $this->app->view->Html($this->params)->render();
  }
}
