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
class Upload extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = TRUE;

  public function renderJson(): ?array {
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

  private function uploadFile(string $fileName, string $sourceFile): array {
    if ($this->params['renamePattern'] != null) {
      $uploadedFileExtension = strtolower($fileName, PATHINFO_EXTENSION);
      $tmpParts = pathinfo($fileName);

      $fileName = $this->params['renamePattern'];
      $fileName = str_replace("{%Y%}", date("Y"), $fileName);
      $fileName = str_replace("{%M%}", date("m"), $fileName);
      $fileName = str_replace("{%D%}", date("d"), $fileName);
      $fileName = str_replace("{%H%}", date("H"), $fileName);
      $fileName = str_replace("{%I%}", date("i"), $fileName);
      $fileName = str_replace("{%S%}", date("s"), $fileName);
      $fileName = str_replace("{%TS%}", strtotime("now"), $fileName);
      $fileName = str_replace("{%RAND%}", rand(1000, 9999), $fileName);
      $fileName = str_replace("{%BASENAME%}", $tmpParts['basename'], $fileName);
      $fileName = str_replace("{%BASENAME_ASCII%}", \ADIOS\Core\Helper::str2url($tmpParts['basename']), $fileName);
      $fileName = str_replace("{%FILENAME%}", $tmpParts['filename'], $fileName);
      $fileName = str_replace("{%FILENAME_ASCII%}", \ADIOS\Core\Helper::str2url($tmpParts['filename']), $fileName);
      $fileName = str_replace("{%EXT%}", $tmpParts['extension'], $fileName);
    }

    $folderPath = $this->params['folderPath'] ?? "";

    if (strpos($folderPath, "..") !== FALSE) {
      $folderPath = "";
    }

    if (empty($folderPath)) $folderPath = ".";

    if (!is_dir("{$this->app->config['uploadDir']}/{$folderPath}")) {
      mkdir("{$this->app->config['uploadDir']}/{$folderPath}", 0775, TRUE);
    }

    $destinationFile = "{$this->app->config['uploadDir']}/{$folderPath}/{$fileName}";

    if (in_array($uploadedFileExtension, ['php', 'sh', 'exe', 'bat', 'htm', 'html', 'htaccess'])) {
      throw new \Exception('This file type cannot be uploaded');
    }
    // elseif (!empty($_FILES['upload']['error'])) {
    //
    //   $error = "File is too large. Maximum size of file to upload is ".round(ini_get('upload_max_filesize'), 2)." MB.";
    // } elseif (empty($_FILES['upload']['tmp_name']) || 'none' == $_FILES['upload']['tmp_name']) {
    //   $error = "Failed to upload the file for an unknown error. Try again in few minutes.";
    //   // } elseif (file_exists($destinationFile)) {
    //   //   $error = "File with this name is already uploaded.";
    // }
    //
    //

    if (is_file($destinationFile)) {
      throw new \Exception("The file already exists");
    }

    if (!move_uploaded_file($sourceFile, $destinationFile)) {
      throw new \Exception("An error occurred during the file upload");
    }

    return [
      'fullPath' => "{$folderPath}/{$fileName}",
      //'folderPath' => $folderPath,
      //'fileName' => $fileName,
      //'fileSize' => filesize($destinationFile),
      //'url' => "{$this->app->config['uploadUrl']}/{$folderPath}/{$fileName}",
    ];
  }
}
