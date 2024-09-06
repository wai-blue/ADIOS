<?php

namespace ADIOS\Models;

class UserHasRole extends \ADIOS\Core\Model {
  public string $eloquentClass = Eloquent\UserHasRole::class;

  public bool $isJunctionTable = FALSE;

  public function __construct(\ADIOS\Core\Loader $app)
  {
    $this->sqlName = "user_has_roles";
    parent::__construct($app);
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
