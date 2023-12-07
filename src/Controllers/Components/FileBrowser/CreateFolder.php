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
class CreateFolder extends \ADIOS\Core\Controller {
  public function render() {
    $folder = $this->params['folder'];

    foreach (explode("/", $folder) as $tmp) {
      if ($tmp == "..") return "Invalid folder path.";
    }

    $dir = $this->adios->config['files_dir'];

    if (!empty($dir) && mkdir("{$dir}/{$folder}", 0775)) {
      return "1";
    } else {
      return "Failed to create folder: {$folder}.";
    }
  }
}