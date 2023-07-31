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
    $pdf = new PDF('P', 'mm', 'A4', true, 'UTF-8', tableTitle: 'BladeERP');
    $pdf->setHeaderMargin(10);
    $pdf->setMargins(10, 20, 10);
    $pdf->AddPage();

    $pdf->setHeaderData($ln='', $lw=0, $ht='', $hs='<table cellspacing="0" cellpadding="1" border="1"><tr><td rowspan="3">test</td><td>test</td></tr></table>', $tc=array(0,0,0), $lc=array(0,0,0));

    $pdf->SetCreator('BladeERP');
    $pdf->SetTitle('TCPDF Example 003');
    $pdf->SetSubject('TCPDF Tutorial');

    $pdf->WriteHtml(base64_decode($params['params']), true, false, true, false);
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
    $result = "<iframe width='100%' id='iframe' height='100%' src='data:application/pdf;base64," . base64_encode($this->pdf) . "'></iframe>";
    /*$result .= "<script>";
    $result .= "const pdfFrame = document.getElementById('iframe');";
    $result .= "pdfFrame.contentWindow.print()";
    $result .= "</script>";*/
    return $result;
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
    $this->Cell(0, 15, '<< ' . $this->tableTitle . ' >>', 0, false, 'L', 0, '', 0, false, 'M', 'M');
  }
}
