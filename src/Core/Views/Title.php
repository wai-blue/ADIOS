<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\Views;

class Title extends \ADIOS\Core\View
{
  public function __construct(&$adios, $params = null)
  {

    parent::__construct($adios, $params);

    if ($this->params['fixed']) {
      $this->addCssClass('fixed');
    }

    $this->add($this->params['left'], 'left');
    $this->add($this->params['right'], 'right');
    $this->add($this->params['center'], 'center');
  }

  public function render(string $panel = ''): string
  {
    $center = (string) parent::render('center');
    $center = trim($center);

    return "
        <div class='adios ui Title'>
          " . (empty($center) ? "" : "
            <div class='row mb-3'>
              <div class='col-lg-12 p-0'>
                <div class='h3 text-primary mb-0'>{$center}</div>
              </div>
            </div>
          ") . "
          <div class='row mb-3'>
            <div class='col-lg-6 p-0'>
              " . parent::render('left') . "
            </div>
            <div class='col-lg-6 p-0 text-right'>
              " . parent::render('right') . "
            </div>
          </div>
        </div>
      ";
  }
}
