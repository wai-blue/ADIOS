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
    $pdf = new \TCPDF();
    $pdf->AddPage();
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
    $result = "<iframe width='100%' height='100%' src='data:application/pdf;base64," . base64_encode($this->pdf) . "'></iframe>";
    return $result;
  }
}