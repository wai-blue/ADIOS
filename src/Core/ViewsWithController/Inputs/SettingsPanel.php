<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController\Inputs;

class SettingsPanel extends \ADIOS\Core\ViewsWithController\Input {
  var $folderTreeHtmlItems = [];

  public function render(string $panel = ''): string
  {
    $html = "
      <div id='{$this->uid}_form' class='adios ui Form'>
        <div class='adios ui Form table'>
    ";

    if (is_array($this->params['template']['tabs'])) {
      $tabPages = [];
      foreach ($this->params['template']['tabs'] as $tab) {
        $tabHtml = "";

        foreach ($tab['items'] as $item) {
          if (empty($item)) continue;

          if (is_string($item)) {
            $tabHtml .= "
              <div class='adios ui Form subrow'>
                {$item}
              </div>
            ";
          } else {
            $itemHtml = "";

            if (isset($item['html'])) {
              $itemHtml = $item['html'];
            } else {
              $itemHtml = "
                <div class='input-content'>
                  ".$item['input']->render()."
                </div>
                ".(empty($item['description']) ? "" : "
                  <div class='input-description'>
                    {$item['description']}
                  </div>
                ")."
              ";
            }
            $tabHtml .= "
              <div class='adios ui Form subrow'>
                <div class='input-title'>
                  {$item['title']}
                </div>
                {$itemHtml}
              </div>
            ";
          }
        }

        $tabPages[] = [
          'title' => $tab['title'],
          'content' => [
            'html' => $tabHtml
          ],
        ];
      }

      $html .= $this->addView('\\ADIOS\\Core\\ViewsWithController\\Tabs', [
        'tabs' => $tabPages,
        'height' => "calc(100vh - 16em)",
      ])->render();
    }

    if (is_array($this->params['template']['items'])) {
      foreach ($this->params['template']['items'] as $item) {
        if (empty($item)) continue;

        if (is_string($item)) {
          $html .= "
            <div class='adios ui Form subrow'>
              {$item}
            </div>
          ";
        } else {
          $html .= "
            <div class='adios ui Form subrow'>
              <div class='input-title'>
                {$item['title']}
              </div>
              <div class='input-content'>
                ".$item['input']->render()."
              </div>
              ".(empty($item['description']) ? "" : "
                <div class='input-description'>
                  {$item['description']}
                </div>
              ")."
            </div>
          ";
        }
      }
    }

    $html .= "
        </div>
      </div>
    ";


    return $html;
  }
}
