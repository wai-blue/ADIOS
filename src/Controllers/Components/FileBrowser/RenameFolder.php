<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\FileBrowser;

/**
 * @package Components\Controllers\FileBrowser
 */
class RenameFolder extends \ADIOS\Core\Controller {
  public function render() {
    $folder = $this->params['folder'];
    $newFolderName = $this->params['newFolderName'];

    foreach (explode("/", $folder) as $tmp) {
      if ($tmp == "..") return "Invalid folder path. {$folder}";
    }

    $dir = realpath($this->app->config['uploadDir']);

    if (!empty($dir) && rename("{$dir}/{$folder}", "{$dir}/".dirname($folder)."/{$newFolderName}")) {
      return "1"; // "1 = {$dir}/{$folder}, {$dir}/".dirname($folder)."/{$newFolderName}";
    } else {
      return "Failed to rename folder {$dir}/{$folder} to {$newFolderName}.";
    }
  }
}