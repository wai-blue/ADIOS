<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\UI\FileBrowser;

/**
 * @package UI\Controllers\FileBrowser
 */
class DeleteFolder extends \ADIOS\Core\Controller {
  public function render() {
    $folder = $this->params['folder'];

    foreach (explode("/", $folder) as $tmp) {
      if ($tmp == "..") return "Invalid folder path.";
    }

    $dir = $this->adios->config['files_dir'];

    if (!empty($dir) && rmdir("{$dir}/{$folder}")) {
      return "1";
    } else {
      return "Failed to delete folder: {$folder}.";
    }
  }
}