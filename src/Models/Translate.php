<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Models;

/**
 * Model for storing language translations. Stored in 'translate' SQL table.
 *
 * @package DefaultModels
 */
class Translate extends \ADIOS\Core\Model {

  public string $eloquentClass = \ADIOS\Models\Eloquent\Translate::class;

  public function __construct(\ADIOS\Core\Loader $app) {
    $this->sqlName = "_translate";
    parent::__construct($app);
  }

  public function columns(array $columns = []): array
  {
    return parent::columns([
      'hash' => ['type' => 'varchar', 'byte_size' => '32', 'title' => 'Hash'],
      'value' => ['type' => 'varchar', 'byte_size' => '255', 'title' => 'Text', 'show_column' => true],
      'context' => ['type' => 'varchar', 'byte_size' => '120', 'title' => 'Kontext', 'show_column' => true],
      'lang' => ['type' => 'varchar', 'byte_size' => '2', 'title' => 'Mutácia'],
      'category' => ['type' => 'varchar', 'byte_size' => '50', 'title' => 'Kategória', 'show_column' => true],
    ]);
  }
}
