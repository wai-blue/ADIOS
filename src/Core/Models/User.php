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
  const TOKEN_TYPE_USER_FORGOT_PASSWORD = 551155;

  protected $hidden = [
    'password',
    'last_access_time',
    'last_access_ip',
    'last_login_time',
    'last_login_ip',
  ];

  public string $urlBase = "users";
  public ?string $lookupSqlValue = "{%TABLE%}.login";

  public string $tableTitle = "Users";
  public string $formTitleForInserting = "New user";
  public string $formTitleForEditing = "User";

  public function __construct($adiosOrAttributes = NULL, $eloquentQuery = NULL) {
    $this->sqlName = "_users";
    parent::__construct($adiosOrAttributes, $eloquentQuery);

    if (is_object($adiosOrAttributes)) {
      $this->tableTitle = $this->translate("Users");
      $tokenModel = $adiosOrAttributes->getModel("ADIOS/Core/Models/Token");

      if (!$tokenModel->isTokenTypeRegistered(self::TOKEN_TYPE_USER_FORGOT_PASSWORD)) {
        $tokenModel->registerTokenType(self::TOKEN_TYPE_USER_FORGOT_PASSWORD);
      }
    }
  }

  public function columns(array $columns = []): array
  {
    return parent::columns(array_merge($columns, [
      'login' => [
        'type' => 'varchar',
        'title' => $this->translate('Login'),
        'showColumn' => true,
      ],
      'password' => [
        'type' => 'password',
        'title' => $this->translate('Password'),
      ],
      'is_active' => [
        'type' => 'boolean',
        'title' => $this->translate('Active'),
      ],
      'last_login_time' => [
        'type' => 'datetime',
        'title' => $this->translate('Time of last login'),
        'readonly' => TRUE,
      ],
      'last_login_ip' => [
        'type' => 'varchar',
        'title' => $this->translate('Last login IP'),
        'readonly' => TRUE,
      ],
      'last_access_time' => [
        'type' => 'datetime',
        'title' => $this->translate('Time of last access'),
        'readonly' => TRUE,
      ],
      'last_access_ip' => [
        'type' => 'varchar',
        'title' => $this->translate('Last access IP'),
        'readonly' => TRUE,
      ],
      //'id_token_reset_password' => [
      //  'type' => 'lookup',
      //  'model' => "ADIOS/Core/Models/Token",
      //  'title' => $this->translate('Reset password token'),
      //  'readonly' => TRUE,
      //  'showColumn' => false,
      //]
    ]));
  }

  public function upgrades() : array {
    // Upgrade nebude fungovať pretože sa mení logika prihlásenia a upgrade sa vykoná až po prihlásení.
    // Upgrade je možné realizovať nanovo vytvorením tabuľky users napríklad pomocou funkcie $model->install()
    // Pri tomto riešení je potrebné manuálne zálohovať používateľov a následne ich importovať.
    return [
      0 => [], // upgrade to version 0 is the same as installation
      1 => [
        "ALTER TABLE `{$this->getFullTableSqlName()}` CHANGE  `active` `is_active` tinyint(1);",
        "
          ALTER TABLE `{$this->getFullTableSqlName()}`
          ADD column `phone_number` varchar(255) DEFAULT '' after `email`
        ",
        "
          ALTER TABLE `{$this->getFullTableSqlName()}`
          ADD column `last_login_time` varchar(255) DEFAULT '' after `is_active`
        ",
        "
          ALTER TABLE `{$this->getFullTableSqlName()}`
          ADD column `last_login_ip` varchar(255) DEFAULT '' after `last_login_time`
        ",
        "
          ALTER TABLE `{$this->getFullTableSqlName()}`
          ADD column `last_access_time` varchar(255) DEFAULT '' after `last_login_ip`
        ",
        "
          ALTER TABLE `{$this->getFullTableSqlName()}`
          ADD column `last_access_ip` varchar(255) DEFAULT '' after `last_access_time`
        ",
      ],
    ];
  }

  public function indexes(array $indexes = []) {
    return parent::indexes([
      "login" => [
        "type" => "unique",
        "columns" => [
          "login" => [
            "order" => "asc",
          ],
        ],
      ],
    ]);
  }

  public function routing(array $routing = []) {
    return parent::routing([
      '/^MyProfile$/' => [
        "controller" => "Components/Form",
        "params" => [
          "model" => "ADIOS/Core/Models/User",
          "myProfileView" => TRUE,
          "id" => $this->adios->userProfile['id'],
        ]
      ],
    ]);
  }

  // public function getById($id) {
  //   $id = (int) $id;
  //   $user = self::find($id);
  //   return ($user === NULL ? [] : $user->toArray());
  // }

  public function onFormParams(\ADIOS\Core\ViewsWithController\Form $formObject, array $params): array
  {

    if ($params["myProfileView"]) {
      $params['show_delete_button'] = FALSE;
      $params['template'] = [
        "columns" => [
          [
            "rows" => [
              "login",
              "password",
            ],
          ],
        ],
      ];
    }

    return (array) $params;
  }


  public function getClientIpAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  public function updateAccessInformation(int $idUser) {
    $clientIp = $this->getClientIpAddress();
    $this->where('id', $idUser)->update([
      'last_access_time' => date('Y-m-d H:i:s'),
      'last_access_ip' => $clientIp,
    ]);
  }

  public function updateLoginAndAccessInformation(int $idUser) {
    $clientIp = $this->getClientIpAddress();
    $this->where('id', $idUser)->update([
      'last_login_time' => date('Y-m-d H:i:s'),
      'last_login_ip' => $clientIp,
      'last_access_time' => date('Y-m-d H:i:s'),
      'last_access_ip' => $clientIp,
    ]);
  }

  public function authCookieGetLogin() {
    list($tmpHash, $tmpLogin) = explode(",", $_COOKIE[_ADIOS_ID.'-user']);
    return $tmpLogin;
  }

  public function authCookieSerialize($login, $password) {
    return md5($login.".".$password).",".$login;
  }

  public function authUser(string $login, string $password, $rememberLogin = FALSE): void {
    $authResult = FALSE;

    $login = trim($login);

    if (empty($login) && !empty($_COOKIE[_ADIOS_ID.'-user'])) {
      $login = $this->authCookieGetLogin();
    }

    if (!empty($login)) {
      $users = $this
        ->with('roles')
        ->where('login', '=', $login)
        ->where('is_active', '<>', 0)
        ->get()
        ->makeVisible(['password'])
        ->toArray()
      ;

      foreach ($users as $user) {
        $passwordMatch = FALSE;

        if (!empty($password) && password_verify($password, $user['password'] ?? "")) {
          // plain text
          $passwordMatch = TRUE;
        } else if (
          $_COOKIE[_ADIOS_ID.'-user'] == $this->authCookieSerialize($user['login'], $user['password'])) {
          $passwordMatch = TRUE;
        }

        if ($passwordMatch) {
          $authResult = $user;

          $tmpRoles = [];
          foreach ($authResult['roles'] as $role) {
            $tmpRoles[] = (int) $role['pivot']['id_role'];
          }
          $authResult['roles'] = $tmpRoles;

          if ($rememberLogin) {
            setcookie(
              _ADIOS_ID.'-user',
              $this->authCookieSerialize($user['login'], $user['password']),
              time() + (3600 * 24 * 30)
            );
          }

          break;

        }
      }
    }

    if (is_array($authResult)) {
      $this->adios->userProfile = $authResult;
      $this->adios->userLogged = TRUE;
      $_SESSION[_ADIOS_ID]['userProfile'] = $authResult;
    } else {
      $this->logoutUser();
    }
  }

  public function generateToken($idUser, $tokenSalt, $tokenType) {
    $tokenModel = $this->adios->getModel("ADIOS/Core/Models/Token");
    $token = $tokenModel->generateToken($tokenSalt, $tokenType);

    $this->updateRow([
      "id_token_reset_password" => $token['id'],
    ], $idUser);

    return $token['token'];
  }

  public function generatePasswordResetToken($idUser, $tokenSalt) {
    return $this->generateToken(
      $idUser,
      $tokenSalt,
      self::TOKEN_TYPE_USER_FORGOT_PASSWORD
    );
  }

  public function validateToken($token, $deleteAfterValidation = TRUE) {
    $tokenModel = $this->adios->getModel("ADIOS/Core/Models/Token");
    $tokenData = $tokenModel->validateToken($token);

    $userData = $this->where(
      'id_token_reset_password', $tokenData['id']
      )->first()
    ;

    if (!empty($userData)) {
      $userData = $userData->toArray();
    }

    if ($deleteAfterValidation) {
      $this->updateRow([
        "id_token_reset_password" => NULL,
      ], $userData["id"]);

      $tokenModel->deleteToken($tokenData['id']);
    }

    return $userData;
  }

  public function logoutUser() {
    unset($_SESSION[_ADIOS_ID]['userProfile']);
    $this->adios->userProfile = [];
    $this->adios->userLogged = FALSE;
  }

  public function loadUserFromSession() {
    $this->adios->userProfile = $_SESSION[_ADIOS_ID]['userProfile'];
    $this->adios->userLogged = TRUE;
  }

  public function getByEmail(string $email) {
    $user = self::where("email", $email)->first();

    return !empty($user) ? $user->toArray() : [];
  }

  public function updatePassword(int $idUser, string $password) {
    return
      self::where('id', $idUser)
      ->update(
        ["password" => password_hash($password, PASSWORD_DEFAULT)]
      )
    ;
  }



  // Eloquent relations

  public function relationships(): array {
    $relationships = parent::relationships();
    $relationships[] = 'roles';

    return $relationships;
  }

  public function roles() {
    return $this->belongsToMany(
      \ADIOS\Core\Models\UserRole::class,
      '_user_has_roles',
      'id_user',
      'id_role'
    );
  }

  // public function id_role(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
  //   return $this->BelongsTo(\ADIOS\Core\Models\UserRole::class, 'id_role');
  // }

  public function id_token_reset_password(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
    return $this->BelongsTo(\ADIOS\Core\Models\Token::class, 'id_token_reset_password');
  }

}
