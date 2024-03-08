<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Administrator;

class Permissions extends \ADIOS\Core\Controller {
  public function getViewParams() {
    $idUserRole = (int) $this->params['idUserRole'];

    $userRoleModel = (new ($this->adios->getCoreClass('Core\\Models\\UserRole'))($this->adios));

    if ($idUserRole > 0) {
      $userRoles = [ $userRoleModel->getById($idUserRole) ];
    } else {
      $userRoles = $userRoleModel->getAll();
    }

    // permissions podla routingu (najma pre modely)
    $permissions = [];
    foreach ($this->adios->routing as $routeParams) {
      if (!empty($routeParams['permission'])) {
        $tmpPath = $routeParams['permission'];
        foreach ($userRoles as $role) {
          $permissions[$tmpPath][$role['id']] = $this->adios->permissions->granted($tmpPath, $role['id']);
        }
      }
    }

    // permissions podla Actions adresarov
    foreach ($this->adios->widgets as $widget) {

      // TODO: recursive scandir
      $widgetActions = @scandir("{$widget->myRootFolder}/Actions");

      if (is_array($widgetActions)) {
        foreach ($widgetActions as $action) {
          if (substr($action, -4) == ".php") {
            $tmpPath = "Widgets/{$widget->fullName}/Actions/".substr($action, 0, -4);
            foreach ($userRoles as $role) {
              $permissions[$tmpPath][$role['id']] = $this->adios->permissions->granted($tmpPath, $role['id']);
            }
          }
        }
      }
    }

    // sort
    ksort($permissions);

    return [
      "userRoles" => $userRoles,
      "permissions" => $permissions,
    ];
  }
}
