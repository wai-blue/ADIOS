<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Inputs\FileUpload;

/**
 * @package Components\Controllers\FileUpload
 */
class Delete extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = TRUE;

  public function renderJson(): ?array {
    try {
      $fileFullPath = $this->adios->config['uploadDir'] . '/' . (string) $this->params['fileFullPath'];

      if (is_file($fileFullPath)) {
        if (!unlink($fileFullPath)) throw new \Exception("The deletion of the file encountered an error");
      } else {
        throw new \Exception("File not found");
      }

      return [
        'status' => 'success',
        'message' => 'The file has been successfully deleted'
      ];
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }
}
