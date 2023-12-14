<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers;

/**
 * 'Login' action. Renders the login screen of the ADIOS application.
 *
 * @package Components\Controllers
 */
class LoremIpsum extends \ADIOS\Core\Controller {


  public function render()
  {
    return $this->adios->view->create('\\ADIOS\\Core\\ViewsWithController\\LoremIpsum', $this->params)->render();
  }
}
