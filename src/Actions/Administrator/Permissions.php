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

    // permissions podla routingu (najma pre modely)
    $permissions = [];
    foreach ($this->adios->routing as $routeParams) {
      if (!empty($routeParams['permission'])) {
        $permissions[] = $routeParams['permission'];
      }
    }

    // permissions podla Actions adresarov
    foreach ($this->adios->widgets as $widget) {

      // TODO: recursive scandir
      $widgetActions = @scandir("{$widget->myRootFolder}/Actions");

      if (is_array($widgetActions)) {
        foreach ($widgetActions as $action) {
          if (substr($action, -4) == ".php") {
            $permissions[] = "Widgets/{$widget->fullName}/Actions/".substr($action, 0, -4);
          }
        }
      }
    }

    // sort
    sort($permissions);

    return [
      "userRoles" => $userRoles,
      "permissions" => $permissions,
    ];
  }
}
