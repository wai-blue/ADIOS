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

  private \ADIOS\Core\View $left;
  private \ADIOS\Core\View $center;
  private \ADIOS\Core\View $right;

  public function __construct($adios, $params = NULL)
  {

    parent::__construct($adios, $params);

    if ($this->params['fixed']) {
      $this->addCssClass('fixed');
    }

    $this->left = $this->addView();
    $this->center = $this->addView();
    $this->right = $this->addView();

  }

  public function setLeftButtons(array $buttons = []): \ADIOS\Core\View
  {
    $this->left->removeAllViews();
    foreach ($buttons as $button) {
      if ($button instanceof \ADIOS\Core\Views\Button) {
        $this->left->addViewAsObject($button);
      }
    }

    return $this;
  }

  public function setTitle(string $title): \ADIOS\Core\View
  {
    $this->center
      ->removeAllViews()
      ->addView('Html', ['html' => $title])
    ;

    return $this;
  }

  public function render(string $panel = ''): string
  {

    $leftHtml = $this->left->render();
    $centerHtml = $this->center->render();
    $rightHtml = $this->right->render();

    return "
      <div class='adios ui Title'>
        " . (empty($centerHtml) ? "" : "
          <div class='row mb-3'>
            <div class='col-lg-12 p-0'>
              <div class='h3 text-primary mb-0'>
                {$centerHtml}
              </div>
            </div>
          </div>
        ") . "
        <div class='row mb-3'>
          <div class='col-lg-6 p-0'>
            {$leftHtml}
          </div>
          <div class='col-lg-6 p-0 text-right'>
            {$rightHtml}
          </div>
        </div>
      </div>
    ";
  }
}
