<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController;



class SettingsPanel extends \ADIOS\Core\ViewWithController {

  public function render(string $panel = ''): string
  {

    $inputHtml = (new \ADIOS\Core\ViewsWithController\Inputs\SettingsPanel(
      $this->adios,
      $this->params['uid'],
      $this->params
    ))->render();

    $inputHtml .= "
      <script>
        function {$this->uid}_save() {
          let data = {
            'values': JSON.stringify(ADIOS.views.Form.get_values('{$this->uid}_form', '{$this->uid}_')),
            '__settings_group': '".ads($this->params['settings_group'])."',
          };

          _ajax_read('Components/SettingsPanel/Save', data, function(res) {
            if (isNaN(res)) {
              alert(res);
            } else {
              $('#{$this->uid}_save_info_span').fadeIn();
              setTimeout(function() {
                $('#{$this->uid}_save_info_span').fadeOut();
              }, 1000);
            }
          });
        }

        function {$this->uid}_close() {
          window_close('{$this->uid}_window');
        }
      </script>
    ";
    

    $html = $this->adios->view->Window(
      [
        'uid' => "{$this->uid}_window",
        'content' => $inputHtml,
        'header' => [
          $this->adios->view->Button([
            "type" => "close",
            "onclick" => (empty($this->params['onclose']) ? "{$this->uid}_close();" : $this->params['onsave']),
          ]),
          $this->adios->view->Button([
            "type" => "save",
            "onclick" => (empty($this->params['onsave']) ? "{$this->uid}_save();" : $this->params['onsave']),
          ]),
          "
            <span id='{$this->uid}_save_info_span' class='pl-4' style='color:green;display:none'>
              <i class='fas fa-check'></i>
              Saved
            </span>
          ",
        ],
        'form_close_click' => $this->params['onclose'],
        'title' => htmlspecialchars($this->params['title']),
      ]
    )->render();

    return $html;
  }
}