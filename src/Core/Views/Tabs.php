<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\Views;

class Tabs extends \ADIOS\Core\View {
  
  private array $tabs = [];

  public function __construct($adios, $params = NULL)
  {
    parent::__construct($adios, $params);

    $this->tabs = $this->params['tabs'] ?? [];
  }

  public function render(string $panel = ''): string
  {

    $contents = "";
    $titles = "";

    $i = 0;
    foreach ($this->tabs as $tabKey => $tabParams) {
      $tabContentHtml = "";

      if (!empty($tabParams['content']['view'])) {
        $tabContentHtml = $this->addView(
          $tabParams['content']['view'],
          $tabParams['content']['params'] ?? []
        )->render();
      } else if (!empty($tabParams['content']['action'])) {
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
                Base64.decode('" . base64_encode(json_encode($tabParams['content']['params'])) . "')
              );

              _ajax_update(
                '{$tabParams['content']['action']}',
                params,
                '{$this->uid}_tab_{$tabKey}_content_div'
              );
            }, ".rand(200, 1300).");
          </script>
        ";
      } else if (!empty($tabParams['content']['html'])) {
        $tabContentHtml = $tabParams['content']['html'];
      }

      $contents .= "
        <div
          class='
            shadow-sm
            tab_content
            ".($tabKey == 0 ? "active" : "")."
          '
          id='{$this->uid}_tab_content_{$tabKey}'
          onclick=\"
            $(this).closest('.tab_contents').find('.tab_content').removeClass('active');
            $(this).addClass('active');
          \"
        >
          <div class='tab_title_tag'>
            ".hsc($tabParams['title'])."
          </div>
          <div class='px-2'>
            {$tabContentHtml}
          </div>
        </div>
      ";

      $titles .= "
        <li class='nav-item'>
          <a
            class='nav-link tab_title tab_title_{$tabKey} ".($i == 0 ? "active" : "")."'
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

      $i++;
    }

    $html = "
      <div
        id='{$this->uid}'
        class='" . $this->getCssClassesString() . "'
      >
        <ul class='nav nav-tabs'>
          {$titles}
        </ul>
        <div
          class='tab_contents'
          onscroll=\"
            let st = $(this).scrollTop();
            let tab = 0;

            $(this).find('.tab_content').each(function() {
              if ($(this).position().top < 200) {
                tab++;
              }
            });

            $('#{$this->uid} .tab_title').removeClass('active');
            $('#{$this->uid} .tab_title_' + (tab - 1)).addClass('active');
          \"
        >
          {$contents}
        </div>
      </div>
    ";

    return $this->applyDisplayMode((string) $html);
  }
}
