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
class UserHasRole extends \ADIOS\Core\Model {
  public string $eloquentClass = \ADIOS\Models\Eloquent\UserHasRole::class;

  public bool $isJunctionTable = FALSE;

  public ?array $tableParams = [
    "title" => "Users - Roles",
  ];
  public ?array $formParams = [
   "titleForInserting" => "New assignment of role to user",
   "titleForEditing" => "Assignment of role to role",
  ];

  public function __construct($appOrAttributes = NULL, $eloquentQuery = NULL) {
    $this->sqlName = "_user_has_roles";
    parent::__construct($appOrAttributes);
  }

  public function columns(array $columns = []): array
  {
    return parent::columns([
      'id_user' => [
        'type' => 'lookup',
        'title' => $this->translate('User'),
        'model' => "ADIOS/Models/User",
        'foreignKeyOnUpdate' => 'CASCADE',
        'foreignKeyOnDelete' => 'CASCADE',
      ],
      'id_role' => [
        'type' => 'lookup',
        'title' => $this->translate('Role'),
        'model' => "ADIOS/Models/UserRole",
        'foreignKeyOnUpdate' => 'CASCADE',
        'foreignKeyOnDelete' => 'CASCADE',
      ],
    ]);
  }
}
