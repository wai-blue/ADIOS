<?php

namespace ADIOS\Core\Plugin;

class Model extends \ADIOS\Core\Model {
  public function __construct($appOrAttributes = NULL, $eloquentQuery = NULL) {
    $this->myRootFolder = str_replace("\\", "/", dirname((new \ReflectionClass(get_class($this)))->getFileName()));
    $this->dictionaryFolder = $this->myRootFolder."/../Lang";

    parent::__construct($appOrAttributes, $eloquentQuery);
  }
}

