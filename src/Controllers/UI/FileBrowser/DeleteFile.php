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
class DeleteFile extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = TRUE;

  public function render() {
    unlink(realpath($this->adios->config['files_dir'])."/".$this->params['folderPath']."/".trim((string) $this->params['fileName']));
  }
}