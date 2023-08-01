<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\Views;

class PrintPdf extends \ADIOS\Core\View
{

  public PDF $pdf;
  public string $pdfOutput = '';
  public array $hiddenColumns = [];

  public function __construct($adios, $params = null)
  {
    $modelParams = json_decode(base64_decode($params['modelParams']));
    $tableParams = json_decode(base64_decode($params['tableParams']), TRUE);
    $orderBy = $params['orderBy'];

    $model = $adios->getModel($modelParams->model);
    $columns = $model->columns();
    $uiTable = new \ADIOS\Core\Views\Table($adios, $tableParams);
    $data = $uiTable->data;

    $this->pdf = new PDF('L', 'mm', 'A4', true, tableTitle: $modelParams->title);
    $this->pdf->SetPageOrientation('L');
    $this->pdf->setHeaderMargin(10);
    $this->pdf->setMargins(10, 20, 10);
    $this->pdf->AddPage();
    $this->pdf->SetFont('dejavusans', '', 12);

    $this->pdf->SetCreator('BladeERP');
    $this->pdf->SetTitle($modelParams->table);
    $this->pdf->SetSubject('BladeERP Export');

    $styles = '
    <style>
      table {
        border-collapse: collapse;
        width: 100%;
        border: 1px solid gray;
      }
      
      td {
        text-align: left;
        padding: 8px;
        border-top: 1px solid gray;
      }
      
      th {
        background-color: #f1f1f1;
        color: #424242;
        font-weight: bolder;
      }
      .blue {
        background-color: #536b9f;
        color: white;
      }
    </style>';

    $html = $styles;
    $html .= '<table>
      <tr>';
    $html .= $this->renderHeader($columns, $orderBy);
    $html .= '</tr>';

    $i = 0;
    $itemsPerPage = 30; # more rows don't fit
    foreach ($data as $row) {
      if ($i == $itemsPerPage) {  # Adds a new page with new table header
        $i = 0;
        $html .= '</table>';
        $this->pdf->WriteHtml($html, true, false, true, false);

        $this->pdf->AddPage();
        $html = $styles;
        $html .= '<table> <tr>';
        $html .= $this->renderHeader($columns, $orderBy);
        $html .= '</tr>';
      }
      $html .= '<tr>';
      foreach ($row as $colName => $colValue) {
        if (!in_array(explode(":", $colName)[0], $this->hiddenColumns) && isset($columns[$colName])) {
          $html .= '<td>';
          $html .= match ($columns[$colName]["type"]) {
            'lookup' => $row[$colName . ':LOOKUP'],
            'bool', 'boolean' => ($colValue ? '<span style="color: green">True</span>' : '<span style="color: red">False</span>'),
            'int' => (isset($columns[$colName]['enum_values']) ? $columns[$colName]['enum_values'][$colValue] : $colValue),
            default => $colValue,
          };
          $html .= '</td>';
        }
      }
      $html .= '</tr>';
      $i++;
    }
    $html .= '</table>';

    $this->pdf->WriteHtml($html, true, false, true, false);
    $this->pdfOutput = $this->pdf->Output($modelParams->table . '.pdf', 'S');
  }

  public function renderHeader($columns, $orderBy): string
  {
    $header = '';
    foreach ($columns as $key => $col) {
      if ($col['show_column'] || $col['showColumn']) {
        if ($orderBy != '' && explode(" ", $orderBy)[0] == $key) {
          $header .= '<th class="blue">' . (explode(" ", $orderBy)[1] == 'asc' ? '▲' : '▼') . " " . $col['title'] . '</th>';
        } else {
          $header .= '<th>' . $col['title'] . '</th>';
        }
      } else {
        $this->hiddenColumns[] = explode(":", $key)[0];
      }
    }
    return $header;
  }

  /**
   * render
   *
   * @param mixed $panel
   * @return void
   */
  public function render(string $panel = ''): string
  {
    return base64_encode($this->pdfOutput);
  }

}

class PDF extends \TCPDF
{

  public string $tableTitle;

  public function __construct($orientation = 'L', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false, $tableTitle = '')
  {
    $this->tableTitle = $tableTitle;
    parent::__construct();
  }

  public function Header()
  {
    $this->SetFont('helvetica', 'B', 20);
    $this->Cell(0, 15, 'BladeERP >> ' . $this->tableTitle, 0, false, 'L', 0, '', 0, false, 'M', 'M');
  }

  public function Footer() {
    $this->SetY(-15); // Move the pointer to the bottom of the page
    $this->SetFont('helvetica', 'I', 8); // Set font for the footer
    $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C'); // Add the page number
  }
}
