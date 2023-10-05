<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\UI\Dashboard;

use ADIOS\Core\Views\Input;
use ADIOS\Core\Views\Inputs\CheckboxField;

/**
 * @package UI\Controllers
 */
class SaveConfig extends \ADIOS\Core\Controller {

  function render(): bool|string
  {
    return $this->adios->view->Dashboard($this->params)
      ->saveConfiguration(json_encode($_POST['configuration']), $_POST['preset'])
    ;
  }
}
