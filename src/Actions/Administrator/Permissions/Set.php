<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\Administrator\Permissions;

class Set extends \ADIOS\Core\Action {
  public function render() {
    $isEnabled = (bool) $this->params['isEnabled'];
    $idUserRole = (int) $this->params['idUserRole'];
    $permission = $this->params['permission'];

    $this->adios->permissions->set($permission, $idUserRole, $isEnabled ? "1" : "0");
  }
}
