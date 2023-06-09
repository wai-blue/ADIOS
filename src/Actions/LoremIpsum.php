<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions;

/**
 * 'Login' action. Renders the login screen of the ADIOS application.
 *
 * @package UI\Actions
 */
class LoremIpsum extends \ADIOS\Core\Action {


  public function render()
  {
    return $this->adios->view->create('LoremIpsum', $this->params)->render();
  }
}
