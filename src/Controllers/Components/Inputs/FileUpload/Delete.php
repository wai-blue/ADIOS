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

  public function renderJson() {
    try {
      $filesToUpload = $_FILES['upload'];

      $uploadedFiles = [];
      for ($i = 0; $i < count($filesToUpload['tmp_name']);$i++) {
        $uploadedFiles[] = $this->uploadFile($filesToUpload['name'][$i], $filesToUpload['tmp_name'][$i]);
      }

      return [
        'status' => 'success',
        'message' => 'The file has been successfully uploaded',
        'uploadedFiles' => $uploadedFiles
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
