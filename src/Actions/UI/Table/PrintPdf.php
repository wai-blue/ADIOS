<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\UI\Table;

/**
 * @package UI\Actions\Table
 */
class PrintPdf extends \ADIOS\Core\Action {
  public function render($params = []) {
    var_dump(json_decode(base64_decode($params['params'])));

    $pdf = new \TCPDF();
    $pdf->AddPage();
    $pdf->Write(1, 'Hello world');

    $pdf->Output('hello_world.pdf');
  }
}
