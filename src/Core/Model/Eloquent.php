<?php

namespace ADIOS\Core\Model;

class Eloquent extends \Illuminate\Database\Eloquent\Model {
  protected $primaryKey = 'id';
  protected $guarded = [];
  public $timestamps = false;
  public static $snakeAttributes = false;

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
  }
}