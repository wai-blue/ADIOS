<?php

namespace ADIOS\Models\Eloquent;

use \Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends \ADIOS\Core\Model\Eloquent {
  public static $snakeAttributes = false;
  public $table = 'users';

  protected $hidden = [
    'password',
    'last_access_time',
    'last_access_ip',
    'last_login_time',
    'last_login_ip',
  ];

  public function roles() {
    return $this->belongsToMany(
      \ADIOS\Models\Eloquent\UserRole::class,
      'user_has_roles',
      'id_user',
      'id_role'
    );
  }

  public function id_token_reset_password(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
    return $this->BelongsTo(\ADIOS\Models\Eloquent\Token::class, 'id_token_reset_password');
  }

}
