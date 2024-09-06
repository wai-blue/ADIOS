<?php

namespace ADIOS\Models;

class Config extends \ADIOS\Core\Model {

  public string $eloquentClass = Eloquent\Config::class;

  public function __construct(\ADIOS\Core\Loader $app) {
    $this->sqlName = "config";
    parent::__construct($app);
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
