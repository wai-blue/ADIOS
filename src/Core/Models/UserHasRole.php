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
class UserHasRole extends \ADIOS\Core\Model {
  public bool $isJunctionTable = FALSE;

  public ?array $tableParams = [
    "title" => "Users - Roles",
  ];
  public ?array $formParams = [
   "titleForInserting" => "New assignment of role to user",
   "titleForEditing" => "Assignemtn of role to role",
  ];

  public function __construct($adiosOrAttributes = NULL, $eloquentQuery = NULL) {
    $this->sqlName = "_user_has_roles";
    parent::__construct($adiosOrAttributes);
  }

  public function columns(array $columns = []): array
  {
    return parent::columns([
      'id_user' => [
        'type' => 'lookup',
        'title' => $this->translate('User'),
        'model' => "ADIOS/Core/Models/User",
        'input_style' => 'select',
        'show' => false
      ],
      'id_role' => [
        'type' => 'lookup',
        'title' => $this->translate('Role'),
        'model' => "ADIOS/Core/Models/UserRole",
        'input_style' => 'select',
        'show' => false
      ],
    ]);
  }
}
