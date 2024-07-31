<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Models;

/**
 * Model for storing list of user roles. Stored in 'roles' SQL table.
 *
 * @package DefaultModels
 */
class UserRole extends \ADIOS\Core\Model {
  const ADMINISTRATOR = 1;

  const USER_ROLES = [
    self::ADMINISTRATOR => 'ADMINISTRATOR',
  ];

  public string $eloquentClass = \ADIOS\Models\Eloquent\UserRole::class;

  public string $urlBase = "user-roles";
  public ?string $lookupSqlValue = "{%TABLE%}.name";

  public ?array $tableParams = [
    "title" => "Users -Roles",
  ];
  public ?array $formParams = [
   "titleForInserting" => "New user role",
   "titleForEditing" => "User role",
  ];

  public function __construct(\ADIOS\Core\Loader $app)
  {
    $this->sqlName = "_user_roles";
    parent::__construct($app);
  }

  public function columns(array $columns = []): array
  {
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
        "controller" => "Administrator/Permissions",
        "params" => [
          "idUserRole" => '$1',
        ]
      ],
    ]);
  }
}
