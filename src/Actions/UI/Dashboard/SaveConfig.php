<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\UI\Dashboard;

use ADIOS\Core\Views\Input;
use ADIOS\Core\Views\Inputs\CheckboxField;

/**
 * @package UI\Actions
 */
class SaveConfig extends \ADIOS\Core\Action {

  function render(): false|string
  {
    $this->adios->saveConfig([$_POST['configuration']], 'dashboard-' . $this->adios->userProfile['id'] . '-' . $_POST['preset']);
    return $this->adios->renderReturn('success');
  }
}
