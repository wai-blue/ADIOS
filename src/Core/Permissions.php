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

  protected array $enabledPermissions = [];
    
  function __construct($adios)
  {
    $this->adios = $adios;

    if (is_array($this->adios->config['permissions'])) {
      foreach ($this->adios->config['permissions'] as $idUserRole => $permissionsByRole) {
        $this->enabledPermissions[$idUserRole] = [];
        foreach ($permissionsByRole as $permissionPath => $isEnabled) {
          if ((bool) $isEnabled) {
            $this->enabledPermissions[$idUserRole][] = str_replace(":", "/", $permissionPath);
          }
        }
        $this->enabledPermissions[$idUserRole] = array_unique($this->enabledPermissions[$idUserRole]);
      }

    }
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
    if ($idUserRole <= 0) $idUserRole = (int) $this->adios->userProfile['id_role'];

    return (bool) in_array($permission, $this->enabledPermissions[$idUserRole]);
  }
  
  public function has(string $permission, int $idUserRole = 0) : bool
  {
    if ($idUserRole <= 0) $idUserRole = (int) $this->adios->userProfile['id_role'];

    // TODO: Docasne. Ked bude fungovat, vymazat.
    if (strpos($permission, "Administrator/Permission") === 0) return TRUE;

    if (
      FALSE // TODO: Docasne. Ked bude fungovat, vymazat.
      && $idUserRole == \ADIOS\Core\Models\UserRole::ADMINISTRATOR
    ) {
      return TRUE;
    } else {
      return (bool) in_array($permission, $this->enabledPermissions[$idUserRole]);
    }
  }
  
}