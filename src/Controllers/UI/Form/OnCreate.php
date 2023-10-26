<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\UI\Form;

/**
 * @package UI\Controllers\Table
 */
class OnCreate extends \ADIOS\Core\Controller {
  public static bool $hideDefaultDesktop = true;

  public function renderJson() { 
    http_response_code(422);

    return [
      'emptyRequiredInputs' => [
        'ratio' => true 
      ]
    ];
  }

}
