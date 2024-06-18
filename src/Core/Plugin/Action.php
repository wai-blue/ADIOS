<?php

namespace ADIOS\Core\Plugin;

class Action extends \ADIOS\Core\Controller {
  function __construct(\ADIOS\Core\Loader $app, array $params = []) {
    $this->myRootFolder = str_replace("\\", "/", dirname((new \ReflectionClass(get_class($this)))->getFileName()));

    // preg_match('/^(.*?)\/Actions\/?(.*?)$/', $this->myRootFolder, $m);

    // if (empty($m[2])) {
    //   $subFolderLevel = 0;
    // } else {
    //   $subFolderLevel = (int) substr_count($m[2], "/") + 1;
    // }

    // $this->dictionaryFolder = $this->myRootFolder.str_repeat("/..", $subFolderLevel + 1)."/Lang";
    $this->dictionaryFolder = $this->myRootFolder."/Lang";

    parent::__construct($app, $params);

  }
}

