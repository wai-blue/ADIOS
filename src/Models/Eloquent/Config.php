<?php

namespace ADIOS\Models\Eloquent;

use \Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

class Config extends \ADIOS\Core\Model\Eloquent {
  public static $snakeAttributes = false;
  public $table = 'config';

}
