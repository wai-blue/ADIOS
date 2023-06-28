<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\Models;

/**
 * Model for storing list of user roles. Stored in 'roles' SQL table.
 *
 * @package DefaultModels
 */
class UserRole extends \ADIOS\Core\Model {
  const ADMINISTRATOR = 1;

  public string $lookupSqlValue = "{%TABLE%}.name";
  
  public function __construct($adiosOrAttributes = NULL, $eloquentQuery = NULL) {
    $this->sqlName = "{$adiosOrAttributes->config['system_table_prefix']}_roles";
    parent::__construct($adiosOrAttributes);
  }

  public function columns(array $columns = []) {
    return parent::columns([
      'name' => [
        'type' => 'varchar',
        'title' => $this->translate('Role name'),
        'show_column' => true
      ],
    ]);
  }

  public function routing(array $routing = []) {
    return parent::routing([
      '/^Administrator\/Permissions\/(\d+)$/' => [
        "action" => "Administrator/Permissions",
        "params" => [
          "idUserRole" => '$1',
        ]
      ],
    ]);
  }
}