<?php

namespace ADIOS\Models\Eloquent;

use \Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHasRole extends \ADIOS\Core\Model\Eloquent {
  public static $snakeAttributes = false;
  public $table = '_user_has_roles';

}
