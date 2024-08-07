<?php

namespace ADIOS\Core;

/**
 * Core implementation of ADIOS Action
 *
 * 'Action' is fundamendal class for generating HTML content of each ADIOS call. Actions can
 * be rendered using Twig template or using custom render() method.
 *
 */
class Permissions {
  /**
   * Reference to ADIOS object
   */
  protected \ADIOS\Core\Loader $app;

  protected array $permissions = [];

  function __construct(\ADIOS\Core\Loader $app)
  {
    $this->app = $app;

    $this->permissions = $this->loadPermissions();
    $this->expandPermissionGroups();

  }

  function loadPermissions(): array {
    $permissions = [];
    if (is_array($this->app->config['permissions'] ?? [])) {
      foreach ($this->app->config['permissions'] ?? [] as $idUserRole => $permissionsByRole) {
        $permissions[$idUserRole] = [];
        foreach ($permissionsByRole as $permissionPath => $isEnabled) {
          if ((bool) $isEnabled) {
            $permissions[$idUserRole][] = str_replace(":", "/", $permissionPath);
          }
        }
        $permissions[$idUserRole] = array_unique($permissions[$idUserRole]);
      }

    }

    return $permissions;
  }

  public function expandPermissionGroups() {
    foreach ($this->permissions as $idUserRole => $permissionsByRole) {
      foreach ($permissionsByRole as $permission) {
        if (strpos($permission, ':') !== FALSE) {
          list($pGroup, $pGroupItems) = explode(':', $permission);
          if (strpos($pGroupItems, ',') !== FALSE) {
            $pGroupItemsArr = explode(',', $pGroupItems);
            if (count($pGroupItemsArr) > 1) {
              foreach ($pGroupItemsArr as $item) {
                $this->permissions[$idUserRole][] = $pGroup . ':' . $item;
              }
            }
          }
        }
      }
    }
  }

  public function set(string $permission, int $idUserRole, bool $isEnabled)
  {
    $this->app->saveConfigByPath(
      "permissions/{$idUserRole}/".str_replace("/", ":", $permission),
      $isEnabled ? "1" : "0"
    );
  }

  public function hasRole(int|string $role) {
    if (is_string($role)) {
      $userRoleModel = $this->app->getCoreClass('Models\\UserRole');
      $idUserRoleByRoleName = array_flip($userRoleModel::USER_ROLES);
      $idRole = (int) $idUserRoleByRoleName[$role];
    } else {
      $idRole = (int) $role;
    }

    return 
      in_array($idRole, $this->app->userProfile['roles'] ?? [])
      || in_array($idRole, $this->app->userProfile['ROLES'] ?? [])
    ;
  }

  public function grantedForRole(string $permission, int $idUserRole) : bool
  {
    if (empty($permission)) return TRUE;

    $granted = (bool) in_array($permission, (array) ($this->permissions[$idUserRole] ?? []));

    if (!$granted) {
    }

    return $granted;
  }

  public function granted(string $permission, array $idUserRoles = []) : bool
  {
    if (empty($permission)) return TRUE;
    if (count($idUserRoles) == 0) $idUserRoles = $this->app->userProfile['roles'] ?? [];
    if (count($idUserRoles) == 0) $idUserRoles = $this->app->userProfile['ROLES'] ?? [];

    $granted = FALSE;

    if (in_array(\ADIOS\Models\UserRole::ADMINISTRATOR, $idUserRoles)) $granted = TRUE;

    // check if the premission is granted for one of the roles of the user
    if (!$granted) {
      foreach ($idUserRoles as $idUserRole) {
        $granted = $this->grantedForRole($permission, $idUserRole);
        if ($granted) break;
      }
    }

    // check if the premission is granted "globally" (for each role)
    if (!$granted) {
      $granted = $this->grantedForRole($permission, 0);
    }

    return $granted;
  }

}