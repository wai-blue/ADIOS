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
class SettingsPanel extends \ADIOS\Core\Controller {
  public function render() {
    $this->params['uid'] = $this->uid;
    return $this->app->view->SettingsPanel($this->params)->render();
  }
}