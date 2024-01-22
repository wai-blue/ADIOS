<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\Models;

/**
 * Model for storing application configuration. Stored in 'config' SQL table.
 *
 * @package DefaultModels
 */
class Config extends \ADIOS\Core\Model {

  public string $urlBase = "core/config";
  public string $tableTitle = "Configuration";
  public string $formTitleForInserting = "New configuration parameter";
  public string $formTitleForEditing = "Configuration parameter";

  public function __construct($adios) {
    $this->sqlName = "_config";
    parent::__construct($adios);
  }

  public function columns(array $columns = []): array
  {
    return parent::columns([
      'path' => [
        'type' => 'varchar',
        'byte_size' => '250',
        'title' => 'Path',
        'show_column' => true
      ],
      'value' => [
        'type' => 'text',
        'interface' => 'plain_text',
        'title' => 'Value',
        'show_column' => true
      ],
    ]);
  }

  public function indexes(array $indexes = []) {
    return parent::indexes([
      "path" => [
        "type" => "unique",
        "columns" => [
          "path" => [
            "order" => "asc",
          ],
        ],
      ],
    ]);
  }

}
