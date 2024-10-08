<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Table\Export;

/**
 * @package Components\Controllers
 */
class CSV extends \ADIOS\Core\Controller {
  public bool $hideDefaultDesktop = TRUE;

  public function render() {
    $model = $this->app->getModel($this->params['model']);
    $columns = $model->columns();
    $tableDescription = json_decode(base64_decode($this->params['tableDescription']), TRUE);

    $uiTable = new \ADIOS\Core\ViewsWithController\Table($this->app, $tableDescription);
    $data = $uiTable->data;
    $firstRow = reset($data);

    $csv = "";

    if (count($data) == 0) {
      foreach ($columns as $colName => $colDefinition) {
        if ($colDefinition["show_column"]) {
          $csv .= iconv('UTF-8', 'CP1250', '"'.str_replace('"', '""', $colDefinition['title'] ?? "-").'";');
        }
      }
    } else {
      $firstLine = "";

      foreach (array_keys($firstRow) as $colName) {
        if (isset($columns[$colName])) {
          $firstLine .= iconv('UTF-8', 'CP1250', '"'.str_replace('"', '""', $columns[$colName]['title'] ?? "-").'";');
        }
      }

      $csv = trim($firstLine, ";")."\n";

      foreach ($data as $row) {
        $line = "";
        foreach ($row as $colName => $colValue) {
          if (isset($columns[$colName])) {
            $cellCsv = $uiTable->getCellCsv($colName, $columns[$colName], $row);
            $cellCsv = $model->onTableCellCsvFormatter($uiTable, [
              'column' => $colName,
              'row' => $row,
              'csv' => $cellCsv,
            ]);

            $line .= iconv('UTF-8', 'CP1250', '"'.str_replace('"', '""', $cellCsv).'";');
          }
        }
        $csv .= trim($line, ";")."\n";
      }
    }

    header("Expires: 0");
    header("Cache-control: private");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Description: File Transfer");
    header("Content-Type: application/csv");
    header("Content-disposition: attachment; filename=".\ADIOS\Core\Helper::str2url($model->urlBase).".csv");

    echo $csv;

  }
}
