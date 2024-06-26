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
class Tree extends \ADIOS\Core\Controller {
  function render($params = []) {
    return $this->app->view->Tree($params)->render();
  }
}