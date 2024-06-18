<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController;

class Window extends \ADIOS\Core\ViewWithController {

  public \ADIOS\Core\ViewWithController $headerLeft;
  public \ADIOS\Core\ViewWithController $headerRight;

  public ?\ADIOS\Core\ViewsWithController\Button $closeButton = NULL;

  public function __construct($app, $params = null)
  {
    $this->app = $app;

    $this->params = [
      'title' => 'Window',
      'subtitle' => '',
      'content' => '',
      'footer' => '',
      'window' => [],
      'onclose' => '',
      'cssClass' => '',
    ];

    parent::__construct($app, $params);

    $this->headerLeft = new \ADIOS\Core\ViewWithController($this->app, $params, $this);
    $this->headerRight = new \ADIOS\Core\ViewWithController($this->app, $params, $this);

  }

  public function setOnclose(string $onclose): \ADIOS\Core\ViewWithController
  {
    $this->params['onclose'] = $onclose;
    return $this;
  }

  public function setContent($content): \ADIOS\Core\ViewWithController
  {
    $this->params['content'] = $content;
    return $this;
  }

  public function setTitle(string $title): \ADIOS\Core\ViewWithController
  {
    $this->params['titleRaw'] = $title;
    return $this;
  }

  public function setSubtitle(string $subtitle): \ADIOS\Core\ViewWithController
  {
    $this->params['subtitle'] = $subtitle;
    return $this;
  }

  public function setCloseButton(\ADIOS\Core\ViewsWithController\Button $closeButton): \ADIOS\Core\ViewWithController
  {
    $this->closeButton = $closeButton;
    return $this;
  }

  public function setHeaderLeft(array $viewObjects = []): \ADIOS\Core\ViewWithController
  {
    $this->headerLeft->removeAllViews();
    foreach ($viewObjects as $viewObject) {
      if ($viewObject instanceof \ADIOS\Core\ViewWithController) {
        $this->headerLeft->addViewAsObject($viewObject);
      }
    }
    return $this;
  }

  public function setHeaderRight(array $viewObjects = []): \ADIOS\Core\ViewWithController
  {
    $this->headerRight->removeAllViews();
    foreach ($viewObjects as $viewObject) {
      if ($viewObject instanceof \ADIOS\Core\ViewWithController) {
        $this->headerRight->addViewAsObject($viewObject);
      }
    }
    return $this;
  }

  public function render(string $panel = ''): string
  {
    $_REQUEST_without_action = $_REQUEST;
    unset($_REQUEST_without_action['action']);

    $html = "
      <div
        class='".$this->getCssClassesString()."'
        id='{$this->uid}'
      >
        <a
          href='javascript:void(0)'
          class='--onclose-href'
          onclick='{$this->params['onclose']}'
          style='display:none'
        ></a>
        <div class='modal-overlay'></div>
        <div class='header'>
          ".($this->closeButton === NULL ? "" : "
            <div class='float-right text-right'>
              " . $this->closeButton->render() . "
            </div>
          ")."
          <div class='row'>
            <div class='col-10 h3 text-primary'>
              ".(empty($this->params['titleRaw'])
                ? hsc($this->params['title'])
                : $this->params['titleRaw']
              )."
              ".(empty($this->params['subtitle']) ? "" : "
                <div class='h6 mb-4'>
                  ".hsc($this->params['subtitle'])."
                </div>
              ")."
            </div>
          </div>
          <div class='row'>
            <div class='col-6'>
              " . $this->headerLeft->render() . "
            </div>
            <div class='col-6 text-right'>
              " . $this->headerRight->render() . "
            </div>
          </div>
        </div>
        <div class='content'>
          {$this->params['content']}
        </div>
      </div>
      <script>
        window_post_render(
          $('#{$this->uid}'),
          '".ads($this->app->action)."',
          '".ads($this->app->requestedUri)."',
          {},
          {}
        );
      </script>
    ";

    return $html;
  }
}
