<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController;

class Title extends \ADIOS\Core\ViewWithController
{

  private \ADIOS\Core\ViewWithController $left;
  private \ADIOS\Core\ViewWithController $center;
  private \ADIOS\Core\ViewWithController $right;

  public function __construct($adios, $params = NULL)
  {

    $params = array_replace_recursive([
      'title' => '',
    ], $params);

    parent::__construct($adios, $params);

    if ($this->params['fixed']) {
      $this->addCssClass('fixed');
    }

    $this->left = $this->addView();
    $this->center = $this->addView();
    $this->right = $this->addView();

    if (!empty($this->params['title'])) {
      $this->setTitle($this->params['title']);
    }

  }

  public function setLeftContent(array $views = []): \ADIOS\Core\ViewWithController
  {
    $this->left->removeAllViews();
    foreach ($views as $view) {
      if ($view instanceof \ADIOS\Core\ViewWithController) {
        $this->left->addViewAsObject($view);
      }
    }

    return $this;
  }

  public function setRightContent(array $views = []): \ADIOS\Core\ViewWithController
  {
    $this->right->removeAllViews();
    foreach ($views as $view) {
      if ($view instanceof \ADIOS\Core\ViewWithController) {
        $this->right->addViewAsObject($view);
      }
    }

    return $this;
  }

  public function setTitle(string $title): \ADIOS\Core\ViewWithController
  {
    $this->center
      ->removeAllViews()
      ->addView('\\ADIOS\\Core\\ViewsWithController\\Html', ['html' => $title])
    ;

    return $this;
  }

  public function render(string $panel = ''): string
  {

    $leftHtml = $this->left->render();
    $centerHtml = $this->center->render();
    $rightHtml = $this->right->render();

    return "
      <div class='".$this->getCssClassesString()."'>
        " . (empty($centerHtml) ? "" : "
          <div class='row'>
            <div class='col-lg-12 p-0'>
              <div class='h3 text-primary mb-0'>
                {$centerHtml}
              </div>
            </div>
          </div>
        ") . "
        " . (strlen($leftHtml . $rightHtml) == 0 ? "" : "
          <div class='row mt-3'>
            <div class='col-lg-6 p-0 d-flex' style='gap:0.5em'>
              {$leftHtml}
            </div>
            <div class='col-lg-6 p-0 d-flex justify-content-end' style='gap:0.5em'>
              {$rightHtml}
            </div>
          </div>
        ")."
      </div>
    ";
  }
}
