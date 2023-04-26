<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\Administrator;

class Permissions extends \ADIOS\Core\Action {
  public function preRender() {
    $idUserRole = (int) $this->params['idUserRole'];

    $userRoleModel = (new \ADIOS\Core\Models\UserRole($this->adios));

    if ($idUserRole > 0) {
      $userRoles = [ $userRoleModel->getById($idUserRole) ];
    } else {
      $userRoles = $userRoleModel->getAll();
    }

    $permissions = [];
    foreach ($this->adios->routing as $routeParams) {
      if (!empty($routeParams['permission'])) {
        $permissions[] = $routeParams['permission'];
      }
    }
    sort($permissions);

    return [
      "userRoles" => $userRoles,
      "permissions" => $permissions,
    ];
  }
}
