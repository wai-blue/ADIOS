<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Table\Import\CSV;

/**
 * @package Components\Controllers
 */
class DownloadTemplate extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = TRUE;

  public function render() {
    $model = $this->params['model'];
    $modelObject = $this->adios->getModel($model);
    $columns = $modelObject->columns();

    $csv = "";

    foreach ($columns as $colName => $colDefinition) {
      if (empty($colDefinition['title'])) continue;
      $csv .= '"'.str_replace('"', '""', $colDefinition['title']).'";';
    }
    $csv .= "\n";

    header("Expires: 0");
    header("Cache-control: private");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Description: File Transfer");
    header("Content-Type: application/csv");
    header("Content-disposition: attachment; filename=".\ADIOS\Core\Helper::str2url($modelObject->urlBase)."-template.csv");

    echo $csv;

  }
}
