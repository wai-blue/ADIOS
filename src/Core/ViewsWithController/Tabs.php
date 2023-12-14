<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController;

class Tabs extends \ADIOS\Core\ViewWithController {
  
  private array $tabs = [];

  public function __construct($adios, $params = NULL)
  {
    parent::__construct($adios, $params);

    $this->tabs = $this->params['tabs'] ?? [];
  }

  public function renderTabContent($tabKey, $tabContent): string
  {
    $tabContentHtml = "";

    if (!empty($tabContent['view'])) {
      $tabContentHtml = $this->addView(
        $tabContent['view'],
        $tabContent['params'] ?? []
      )->render();
    } else if (!empty($tabContent['action'])) {
      $tabContentHtml = "
        <div
          id='{$this->uid}_tab_{$tabKey}_content_div'
          style='width:100%'
        >
          <div style='
            width:100%;
            height:100%;
            text-align:center;
            padding: 1em;
          '>
            <style>
              .{$this->uid}_spinner {
                display:inline-block;
                color: var(--cl-main);
                width: 50px;
                height: 50px;
                top: 50%;
                left: 50%;
                border-radius: 50%;
                border: 5px solid #EEEEEE;
                border-top-color: var(--cl-main);
                animation: {$this->uid}_rotateSpinner 1200ms cubic-bezier(0.66, 0.41, 0.31, 0.56) infinite;
              }

              @keyframes {$this->uid}_rotateSpinner {
                to {
                  transform: rotate(360deg);
                }
              }
            </style>

            Loading...<br/>
            <br/>
            <div style='margin:auto'>
              <div class='{$this->uid}_spinner'>:</div>
            </div>
          </div>
        </div>
        <script>
          setTimeout(function() {
            let params = JSON.parse(
              Base64.decode('" . base64_encode(json_encode($tabContent['params'])) . "')
            );

            _ajax_update(
              '{$tabContent['action']}',
              params,
              '{$this->uid}_tab_{$tabKey}_content_div'
            );
          }, ".rand(200, 600).");
        </script>
      ";
    } else if (!empty($tabContent['html'])) {
      $tabContentHtml = $tabContent['html'];
    } else if (is_array($tabContent)) {
      foreach ($tabContent as $item) {
        $tabContentHtml .= $this->renderTabContent($tabKey, $item);
      }
    }

    return $tabContentHtml;
  }

  public function render(string $panel = ''): string
  {

    $contents = "";
    $titles = "";

    foreach ($this->tabs as $tabKey => $tabParams) {
      $tabContentHtml = $this->renderTabContent($tabKey, $tabParams['content']);

      $contents .= "
        <div
          id='{$this->uid}_tab_content_{$tabKey}'
          class='tab_content px-2 ".($tabKey == 0 ? "active" : "")."'
        >
          {$tabContentHtml}
        </div>
      ";

      $titles .= "
        <li class='nav-item'>
          <a
            class='nav-link rounded-top tab_title tab_title_{$tabKey} ".($tabKey == 0 ? "active" : "")."'
            href='javascript:void(0);'
            onclick=\"
              ui_tabs_change_tab('{$this->uid}', '{$tabKey}');
              {$tabParams['onclick']} 
            \"
          >
            ".hsc($tabParams['title'])."
          </a>
        </li>
      ";

    }

    $html = "
      <div id='{$this->uid}' class='" . $this->getCssClassesString() . "'>
        <ul class='nav nav-tabs'>
          {$titles}
        </ul>
        <div class='tab_contents'>
          {$contents}
        </div>
      </div>
    ";

    return $this->applyDisplayMode((string) $html);
  }
}
