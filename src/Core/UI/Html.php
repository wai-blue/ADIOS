<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\UI;

class Html extends \ADIOS\Core\UI\View
{
    public function __construct(&$adios, $params = null)
    {
        parent::__construct($adios, $params);
    }

    public function render($panel = '')
    {
        $html = "";

        if (!empty($this->params['html'])) {
            $html = $this->params['html'];
        }

        return \ADIOS\Core\HelperFunctions::minifyHtml($html);
    }
}
