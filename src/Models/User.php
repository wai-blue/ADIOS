<?php

namespace ADIOS\Models;

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
  public string $eloquentClass = \ADIOS\Models\Eloquent\User::class;
  

  public ?array $junctions = [
    'roles' => [
      'junctionModel' => \ADIOS\Models\UserHasRole::class,
      'masterKeyColumn' => 'id_user',
      'optionKeyColumn' => 'id_role',
    ],
  ];


  public function __construct(\ADIOS\Core\Loader $app)
  {
    $this->sqlName = "users";
    parent::__construct($app);

    $tokenModel = $app->getModel("ADIOS/Models/Token");

    if (!$tokenModel->isTokenTypeRegistered(self::TOKEN_TYPE_USER_FORGOT_PASSWORD)) {
      $tokenModel->registerTokenType(self::TOKEN_TYPE_USER_FORGOT_PASSWORD);
    }
  }

  public function columns(array $columns = []): array
  {
    return parent::columns(array_merge($columns, [
      'login' => [
        'type' => 'varchar',
        'title' => $this->translate('Login'),
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
      ],
      'last_login_ip' => [
        'type' => 'varchar',
        'title' => $this->translate('Last login IP'),
      ],
      'last_access_time' => [
        'type' => 'datetime',
        'title' => $this->translate('Time of last access'),
      ],
      'last_access_ip' => [
        'type' => 'varchar',
        'title' => $this->translate('Last access IP'),
      ],
      //'id_token_reset_password' => [
      //  'type' => 'lookup',
      //  'model' => "ADIOS/Models/Token",
      //  'title' => $this->translate('Reset password token'),
      //  'readonly' => TRUE,
      //  'show' => false,
      //]
    ]));
  }

  public function tableDescribe(array $description = []): array
  {
    $description = parent::tableDescribe($description);
    unset($description['columns']['password']);
    return $description;
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
    $this->eloquent->where('id', $idUser)->update([
      'last_access_time' => date('Y-m-d H:i:s'),
      'last_access_ip' => $clientIp,
    ]);
  }

  public function updateLoginAndAccessInformation(int $idUser) {
    $clientIp = $this->getClientIpAddress();
    $this->eloquent->where('id', $idUser)->update([
      'last_login_time' => date('Y-m-d H:i:s'),
      'last_login_ip' => $clientIp,
      'last_access_time' => date('Y-m-d H:i:s'),
      'last_access_ip' => $clientIp,
    ]);
  }

  public function isUserActive($user): bool {
    return $user['is_active'] == 1;
  }

  public function authCookieGetLogin() {
    list($tmpHash, $tmpLogin) = explode(",", $_COOKIE[_ADIOS_ID.'-user']);
    return $tmpLogin;
  }

  public function authCookieSerialize($login, $password) {
    return md5($login.".".$password).",".$login;
  }

  public function authUser(string $login, string $password, $rememberLogin = FALSE, $authColumns = ['login']): ?array {
    $authResult = FALSE;

    $login = trim($login);

    if (empty($login) && !empty($_COOKIE[_ADIOS_ID.'-user'])) {
      $login = $this->authCookieGetLogin();
    }

    if (!empty($login)) {
      $users = $this->eloquent
        ->where(function($q) use ($authColumns, $login) {
          foreach ($authColumns as $column) {
            $q->orWhere($column, '=', $login);
          }
        })
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
        } else {
          foreach ($authColumns as $column) {
            if (
              isset($_COOKIE[_ADIOS_ID.'-user'])
              && $_COOKIE[_ADIOS_ID.'-user'] == $this->authCookieSerialize($user[$column], $user['password'])
            ) {
              $passwordMatch = TRUE;
              break;
            }
          }
        }

        if ($passwordMatch) {
          $authResult = $this->loadUser($user['id']);

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

    return is_array($authResult) ? $authResult : null;
  }

  public function generateToken($idUser, $tokenSalt, $tokenType) {
    $tokenModel = $this->app->getModel("ADIOS/Models/Token");
    $token = $tokenModel->generateToken($tokenSalt, $tokenType);

    $this->eloquent->updateRow([
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
    $tokenModel = $this->app->getModel("ADIOS/Models/Token");
    $tokenData = $tokenModel->validateToken($token);

    $userData = $this->eloquent->where(
      'id_token_reset_password', $tokenData['id']
      )->first()
    ;

    if (!empty($userData)) {
      $userData = $userData->toArray();
    }

    if ($deleteAfterValidation) {
      $this->eloquent->updateRow([
        "id_token_reset_password" => NULL,
      ], $userData["id"]);

      $tokenModel->deleteToken($tokenData['id']);
    }

    return $userData;
  }

  public function persistUser(array $user) {
    $this->app->userProfile = $user;
    $this->app->userLogged = TRUE;
    $_SESSION[_ADIOS_ID]['userProfile'] = $user;
  }

  public function signOut() {
    unset($_SESSION[_ADIOS_ID]);
    $this->app->userProfile = [];
    $this->app->userLogged = FALSE;
  }

  public function getQueryForUser(int $idUser) {
    return $this->eloquent
      ->with('roles')
      ->where('id', $idUser)
      ->where('is_active', '<>', 0)
    ;
  }

  public function loadUser(int $idUser) {
    $user = $this->getQueryForUser($idUser)->first()?->toArray();

    $tmpRoles = [];
    foreach ($user['roles'] ?? [] as $role) {
      $tmpRoles[] = (int) $role['pivot']['id_role'];
    }
    $user['roles'] = $tmpRoles;

    return $user;
  }

  public function loadUserFromSession() {
    return $this->loadUser((int) ($_SESSION[_ADIOS_ID]['userProfile']['id'] ?? 0));
  }

  public function getByEmail(string $email) {
    $user = $this->eloquent->where("email", $email)->first();

    return !empty($user) ? $user->toArray() : [];
  }

  public function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
  }

  public function updatePassword(int $idUser, string $password) {
    return $this->eloquent
      ->where('id', $idUser)
      ->update(
        ["password" => $this->hasPassword($password)]
      )
    ;
  }

}
