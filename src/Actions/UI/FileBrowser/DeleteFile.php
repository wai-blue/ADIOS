<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\UI\FileBrowser;

/**
 * @package UI\Actions\FileBrowser
 */
class DeleteFile extends \ADIOS\Core\Action {
  public static $hideDefaultDesktop = TRUE;

  public function render() {
    unlink(realpath($this->adios->config['files_dir'])."/".$this->params['folderPath']."/".trim($this->params['fileName']));
  }
}