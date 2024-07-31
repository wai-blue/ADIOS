<?php

namespace ADIOS\Core\Model;

class Eloquent extends \Illuminate\Database\Eloquent\Model {
  protected $primaryKey = 'id';
  protected $guarded = [];
  public $timestamps = false;
  public static $snakeAttributes = false;
}