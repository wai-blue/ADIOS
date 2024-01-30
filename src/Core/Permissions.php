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
  protected $adios;

  protected array $permissions = [];
    
  function __construct($adios)
  {
    $this->adios = $adios;

    $this->permissions = $this->loadPermissions();

  }

  function loadPermissions(): array {
    $permissions = [];
    if (is_array($this->adios->config['permissions'] ?? [])) {
      foreach ($this->adios->config['permissions'] ?? [] as $idUserRole => $permissionsByRole) {
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

  public function set(string $permission, int $idUserRole, bool $isEnabled)
  {
    $this->adios->saveConfigByPath(
      "permissions/{$idUserRole}/".str_replace("/", ":", $permission),
      $isEnabled ? "1" : "0"
    );
  }

  public function isEnabled(string $permission, int $idUserRole = 0) : bool
  {
    if ($idUserRole <= 0) $idUserRole = (int) reset($this->adios->userProfile['roles']);

    return (bool) in_array($permission, $this->permissions[$idUserRole]);
  }
  
  public function has(string $permission, array $idUserRoles = []) : bool
  {
    if (count($idUserRoles) == 0) $idUserRoles = $this->adios->userProfile['roles'];

    // TODO: Docasne. Ked bude fungovat, vymazat.
    if (strpos($permission, "Desktop") === 0) return TRUE;
    if (strpos($permission, "Administrator/Permission") === 0) return TRUE;
    if (strpos($permission, "Core/Models") === 0) return TRUE;

    $permissionGranted = FALSE;
    foreach ($idUserRoles as $idUserRole) {
      if ($idUserRole == \ADIOS\Core\Models\UserRole::ADMINISTRATOR) {
        $permissionGranted = TRUE;
      } else {
        $permissionGranted = (bool) in_array($permission, (array) $this->permissions[$idUserRole]);
      }

      if ($permissionGranted) break;
    }

    return $permissionGranted;
  }
  
}