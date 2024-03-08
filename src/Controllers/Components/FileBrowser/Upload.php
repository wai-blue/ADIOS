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
class Upload extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = TRUE;

  public function render() {
    if (!empty($_FILES['upload'])) {

      $folderPath = $_REQUEST['folderPath'] ?? "";

      if (strpos($folderPath, "..") !== FALSE) {
        $folderPath = "";
      }

      $uploadedFilename = $_FILES['upload']['name'];
      if ($_REQUEST['renamePattern'] != "") {
        $tmpParts = pathinfo($uploadedFilename);

        $uploadedFilename = $_REQUEST['renamePattern'];
        $uploadedFilename = str_replace("{%Y%}", date("Y"), $uploadedFilename);
        $uploadedFilename = str_replace("{%M%}", date("m"), $uploadedFilename);
        $uploadedFilename = str_replace("{%D%}", date("d"), $uploadedFilename);
        $uploadedFilename = str_replace("{%H%}", date("H"), $uploadedFilename);
        $uploadedFilename = str_replace("{%I%}", date("i"), $uploadedFilename);
        $uploadedFilename = str_replace("{%S%}", date("s"), $uploadedFilename);
        $uploadedFilename = str_replace("{%TS%}", strtotime("now"), $uploadedFilename);
        $uploadedFilename = str_replace("{%RAND%}", rand(1000, 9999), $uploadedFilename);
        $uploadedFilename = str_replace("{%BASENAME%}", $tmpParts['basename'], $uploadedFilename);
        $uploadedFilename = str_replace("{%BASENAME_ASCII%}", \ADIOS\Core\Helper::str2url($tmpParts['basename']), $uploadedFilename);
        $uploadedFilename = str_replace("{%FILENAME%}", $tmpParts['filename'], $uploadedFilename);
        $uploadedFilename = str_replace("{%FILENAME_ASCII%}", \ADIOS\Core\Helper::str2url($tmpParts['filename']), $uploadedFilename);
        $uploadedFilename = str_replace("{%EXT%}", $tmpParts['extension'], $uploadedFilename);
      }

      if (empty($folderPath)) $folderPath = ".";

      if (!is_dir("{$this->adios->config['uploadDir']}/{$folderPath}")) {
        mkdir("{$this->adios->config['uploadDir']}/{$folderPath}", 0775, TRUE);
      }

      $sourceFile = $_FILES['upload']['tmp_name'];
      $destinationFile = "{$this->adios->config['uploadDir']}/{$folderPath}/{$uploadedFilename}";

      $uploadedFileExtension = strtolower(pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION));

      $error = "";
      if (in_array($uploadedFileExtension, ['php', 'sh', 'exe', 'bat', 'htm', 'html', 'htaccess'])) {
        $error = "This file type cannot be uploaded";
      } elseif (!empty($_FILES['upload']['error'])) {
        $error = "File is too large. Maximum size of file to upload is ".round(ini_get('upload_max_filesize'), 2)." MB.";
      } elseif (empty($_FILES['upload']['tmp_name']) || 'none' == $_FILES['upload']['tmp_name']) {
        $error = "Failed to upload the file for an unknown error. Try again in few minutes.";
      // } elseif (file_exists($destinationFile)) {
      //   $error = "File with this name is already uploaded.";
      }

      if (empty($error)) {
        move_uploaded_file($sourceFile, $destinationFile);

        echo json_encode([
          'uploaded' => 1,
          'folderPath' => $folderPath,
          'fileName' => $uploadedFilename,
          'fileSize' => filesize($destinationFile),
          'url' => "{$this->adios->config['uploadUrl']}/{$folderPath}/{$uploadedFilename}",
        ]);
      } else {
        echo json_encode([
          'uploaded' => 0,
          'error' => $error,
        ]);
      }

    }
  }
}