<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Administrator\Permissions;

class Set extends \ADIOS\Core\Controller {
  public function render() {
    $isEnabled = (bool) $this->params['isEnabled'];
    $idUserRole = (int) $this->params['idUserRole'];
    $permission = $this->params['permission'];

    $this->app->permissions->set($permission, $idUserRole, $isEnabled ? "1" : "0");
  }
}
