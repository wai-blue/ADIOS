<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\Models;

/**
 * Model for storing user profiles. Stored in 'users' SQL table.
 *
 * @package DefaultModels
 */
class User extends \ADIOS\Core\Model {
  var $sqlName = "";
  var $urlBase = "Users";
  var $lookupSqlValue = "concat({%TABLE%}.name, ' ', {%TABLE%}.surname)";

  public function __construct($adiosOrAttributes = NULL, $eloquentQuery = NULL) {
    $this->sqlName = "{$adiosOrAttributes->config['system_table_prefix']}_users";
    parent::__construct($adiosOrAttributes, $eloquentQuery);

    if (is_object($adiosOrAttributes)) {
      $this->tableTitle = $this->translate("Users");
    }
  }

  public function columns(array $columns = []) {
    return parent::columns([
      'name' => [
        'type' => 'varchar',
        'title' => $this->translate('Given name'),
        'show_column' => true
      ],
      'surname' => [
        'type' => 'varchar',
        'title' => $this->translate('Family name'),
        'show_column' => true
      ],
      'login' => [
        'type' => 'varchar',
        'title' => $this->translate('Login')
      ],
      'password' => [
        'type' => 'password',
        'title' => $this->translate('Password')
      ],
      'email' => [
        'type' => 'varchar',
        'title' => $this->translate('Email')
      ],
      'phone_number' => [
        'type' => 'varchar',
        'title' => $this->translate('Phone number')
      ],
      'id_role' => [
        'type' => 'lookup',
        'title' => $this->translate('Role'),
        'model' => "Core/Models/UserRole",
        'show_column' => true,
        'input_style' => 'select'
      ],
      'photo' => [
        'type' => 'image',
        'title' => $this->translate('Photo'),
        'only_upload' => 'yes',
        'subdir' => 'users/',
        "description" => $this->translate("Supported image extensions: jpg, gif, png, jpeg"),
      ],
      'is_active' => [
        'type' => 'boolean',
        'title' => $this->translate('Active'),
        'show_column' => true
      ],
      'last_login_time' => [
        'type' => 'datetime',
        'title' => $this->translate('Time of last login'),
        'show_column' => FALSE,
        'readonly' => TRUE,
      ],
      'last_login_ip' => [
        'type' => 'varchar',
        'title' => $this->translate('Last login IP'),
        'show_column' => FALSE,
        'readonly' => TRUE,
      ],
      'last_access_time' => [
        'type' => 'datetime',
        'title' => $this->translate('Time of last access'),
        'show_column' => FALSE,
        'readonly' => TRUE,
      ],
      'last_access_ip' => [
        'type' => 'varchar',
        'title' => $this->translate('Last access IP'),
        'show_column' => FALSE,
        'readonly' => TRUE,
      ],
    ]);
  }

  public function upgrades() : array {
    return [
      0 => [], // upgrade to version 0 is the same as installation
      1 => [
        "ALTER TABLE {$this->gtp}_adios_users RENAME COLUMN active TO is_active;",
        "ALTER TABLE {$this->gtp}_adios_users RENAME COLUMN active TO is_active;",
        "
          alter table `{$this->gtp}_adios_users`
          add column `last_login_time` varchar(255) DEFAULT '' after `is_active`
        ",
        "
          alter table `{$this->gtp}_adios_users`
          add column `last_login_time` varchar(255) DEFAULT '' after `is_active`
        ",
        "
          alter table `{$this->gtp}_adios_users`
          add column `last_login_ip` varchar(255) DEFAULT '' after `last_login_time`
        ",
        "
          alter table `{$this->gtp}_adios_users`
          add column `last_access_time` varchar(255) DEFAULT '' after `last_login_ip`
        ",
        "
          alter table `{$this->gtp}_adios_users`
          add column `last_access_ip` varchar(255) DEFAULT '' after `last_access_time`
        ",
      ],
    ];
  }

  public function routing(array $routing = []) {
    return parent::routing([
      '/^MyProfile$/' => [
        "action" => "UI/Form",
        "params" => [
          "model" => "Core/Models/User",
          "myProfileView" => TRUE,
          "id" => $this->adios->userProfile['id'],
        ]
      ],
    ]);
  }

  public function getById($id) {
    $id = (int) $id;
    $user = self::find($id);
    return ($user === NULL ? [] : $user->toArray());
  }

  public function formParams($data, $params) {
    if ($params["myProfileView"]) {
      $params['show_delete_button'] = FALSE;
      $params['template'] = [
        "columns" => [
          [
            "rows" => [
              "name",
              "surname",
              "password",
              "email",
            ],
          ],
        ],
      ];
    }
    
    return $params;
  }

}