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

  public string $pdf = '';

  public function __construct($adios, $params = null)
  {

    $options = json_decode(base64_decode($params['modelParams']));
    $tableParams = json_decode(base64_decode($params['tableParams']));
    $model = $adios->getModel($options->model);
    $columns = $model->columns();

    if ($tableParams->orderBy != '') {
      $order = explode(" ", $tableParams->orderBy);
      $model = $model->orderBy($order[0], $order[1]);
    }

    $data = $model->get()->toArray();
    $hiddenColumns = [];

    $pdf = new PDF('L', 'mm', 'A4', true, 'UTF-8', tableTitle: $options->title);
    $pdf->SetPageOrientation('L');
    $pdf->setHeaderMargin(10);
    $pdf->setMargins(10, 20, 10);
    $pdf->AddPage();

    $pdf->setHeaderData($ln='', $lw=0, $ht='', $hs='<table cellspacing="0" cellpadding="1" border="1"><tr><td rowspan="3">test</td><td>test</td></tr></table>', $tc=array(0,0,0), $lc=array(0,0,0));

    $pdf->SetCreator('BladeERP');
    $pdf->SetTitle($options->table);
    $pdf->SetSubject('BladeERP Export');

    $html = '
    <style>
      table {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
        border: 1px solid gray;
      }
      
      td {
        text-align: left;
        padding: 8px;
      }
      
      th {
        background-color: #f0f0f0;
        color: #424242;
        font-weight: bolder;
      }
      .blue {
        background-color: #536b9f;
        color: white;
      }
    </style>
    <table>
      <tr>';
    foreach ($columns as $key => $col) {
      if ($col['show_column'] || $col['showColumn']) {
        if ($tableParams->orderBy != '' && explode(" ", $tableParams->orderBy)[0] == $key) {
          $html .= '<th class="blue">' . $col['title'] . ' ' . (explode(" ", $tableParams->orderBy)[1] == 'asc' ? 'ASC' : 'DESC') .'</th>';
        }
        $html .= '<th>' . $col['title'] .'</th>';
      } else {
        $hiddenColumns[] = $key;
      }
    }
    $html .= '</tr>';
    foreach($data as $row) {
      $html .= '<tr>';
      foreach ($row as $key => $col) {
        if (in_array($key, $hiddenColumns)) continue;
        $html .= '<td>';
        $html .= $col;
        $html .= '</td>';
      }
      $html .= '</tr>';
    }
    $html .= '</table>';

    $pdf->WriteHtml($html, true, false, true, false);
    $this->pdf = $pdf->Output('hello_world.pdf', 'S');
  }
  /**
   * render
   *
   * @param  mixed $panel
   * @return void
   */
  public function render(string $panel = ''): string
  {
    // width='800px' height='500px' id='iframe' src='data:application/octet-stream;headers=filename%3Dprint.pdf;base64," . base64_encode($this->pdf) . "'
    return base64_encode($this->pdf);
  }

}

class PDF extends \TCPDF {

  public string $tableTitle;

  public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false, $tableTitle='') {
    $this->tableTitle = $tableTitle;
    parent::__construct();
  }

  public function Header() {
    $this->SetFont('helvetica', 'B', 20);
    $this->Cell(0, 15, 'BladeERP >> ' . $this->tableTitle, 0, false, 'L', 0, '', 0, false, 'M', 'M');
  }
}
