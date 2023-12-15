<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController;

/* akcia, ktora okrem Input FileBrowser generuje aj nejake window/buttony/titles a funkcionalitu okolo */
/* pouziva \ADIOS\Core\ViewsWithController\Inputs\FileBrowser */
class FileBrowser extends \ADIOS\Core\ViewWithController {

  public function render(string $panel = ''): string
  {
    $inputHtml = (new \ADIOS\Core\ViewsWithController\Inputs\FileBrowser(
      $this->adios,
      $this->params['uid'],
      $this->params
    ))->render();
    
    if ($this->params['__IS_WINDOW__']) {
      if (empty($this->params['onchange'])) {
        $this->params['onchange'] = "{$this->uid}_close($(this).val());";
      }

      $html = $this->adios->view->Window(
        [
          'uid' => "{$this->uid}_window",
          'content' => "
            {$inputHtml}
            <script>
              function {$this->uid}_close(res) {
                try {
                  window_close('{$this->uid}_window', res);
                } catch(e) { }
              }
            </script>
          ",
          'header' => [
            $this->adios->view->Button([
              "text" => $this->translate("Close"),
              "type" => "close",
              "onclick" => "{$this->uid}_close();",
            ]),
          ],
          'title' => htmlspecialchars($this->params['title']),
        ]
      )->render();
    } else {
      $html = "";

      if (!empty($this->params['title'])) {
        $html .= $this->adios->view->Title([
          'center' => $this->translate($this->params['title']),
        ])->render();
      }

      $html .= $inputHtml;
    }

    return $html;
  }
}
