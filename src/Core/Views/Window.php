<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\Views;

class Window extends \ADIOS\Core\View {

  public \ADIOS\Core\View $headerLeft;
  public \ADIOS\Core\View $headerRight;

  public function __construct($adios, $params = null) {
    $this->adios = $adios;

    $this->params = [
      'title' => 'Window',
      'subtitle' => '',
      'content' => '',
      'footer' => '',
      'window' => [],
      'onclose' => '',
      'cssClass' => '',
    ];

    parent::__construct($adios, $params);

    $this->headerLeft = new \ADIOS\Core\View($this->adios, $params, $this);
    $this->headerRight = new \ADIOS\Core\View($this->adios, $params, $this);

    $this->headerLeft->addView('Button', [
      "type" => "close",
      "onclick" => "window_close('{$this->uid}');",
    ]);

  }

  public function setContent($content): \ADIOS\Core\View {
    $this->params['content'] = $content;
    return $this;
  }

  public function setTitle(string $title): \ADIOS\Core\View {
    $this->params['titleRaw'] = $title;
    return $this;
  }

  public function setSubtitle(string $subtitle): \ADIOS\Core\View {
    $this->params['subtitle'] = $subtitle;
    return $this;
  }

  public function setHeaderLeft(array $viewObjects = []): \ADIOS\Core\View {
    $this->headerLeft->removeAllViews();
    foreach ($viewObjects as $viewObject) {
      if ($viewObject instanceof \ADIOS\Core\View) {
        $this->headerLeft->addViewAsObject($viewObject);
      }
    }
    return $this;
  }

  public function setHeaderRight(array $viewObjects = []): \ADIOS\Core\View {
    $this->headerRight->removeAllViews();
    foreach ($viewObjects as $viewObject) {
      if ($viewObject instanceof \ADIOS\Core\View) {
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
      <span
        class='".$this->getCssClassesString()."'
        id='{$this->params['uid']}'
      >
        <div class='header'>
          <div class='bg-white p-4 mb-4 shadow'>
            <div class='row'>
              <div class='col'>
                " . $this->headerLeft->render() . "
              </div>
              <div class='col'>
                <div class='h4 mb-2 text-primary'>
                  ".(empty($this->params['titleRaw'])
                    ? hsc($this->params['title'])
                    : $this->params['titleRaw']
                  )."
                </div>
                ".(empty($this->params['subtitle']) ? "" : "
                  <div class='h6 mb-4'>
                    ".hsc($this->params['subtitle'])."
                  </div>
                ")."
              </div>
              <div class='col text-end'>
                " . $this->headerRight->render() . "
              </div>
            </div>
          </div>
        </div>
        <div class='content'>
          <div class='container-fluid'>
            {$this->params['content']}
          </div>
        </div>
      </span>

      <script>
        setTimeout(function() {
          $('#{$this->params['uid']}').addClass('activated');
        }, 0)
      </script>
    ";

    return $html;
  }
}
