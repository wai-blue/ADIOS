<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Table\Import;

/**
 * @package Components\Controllers
 */
class CSV extends \ADIOS\Core\Controller {
  // public static bool $hideDefaultDesktop = TRUE;

  public function render() {
    $model = $this->params['model'];
    $modelObject = $this->adios->getModel($model);

    $fileUploadInput = new \ADIOS\Core\ViewsWithController\Input(
      $this->adios,
      [
        "uid" => "{$this->uid}_csv_file",
        "type" => "file",
        "subdir" => "csv-import",
        "onchange" => "{$this->uid}_previewCsv()",
      ]
    );

    $content = "
      <script>
        function {$this->uid}_close() {
          window_close('{$this->uid}_window');
        }

        function {$this->uid}_import() {
          let warningText = '';
          warningText += '".$this->translate("WARNING !!!")."\\n';
          warningText += '\\n';
          warningText += '".$this->translate("Some data may be overwritten or even lost.")."\\n';
          warningText += '\\n';
          warningText += '".$this->translate("Action cannot be undone.")."\\n';
          warningText += '\\n';
          warningText += '".$this->translate("Continue with import?")."';

          if (confirm(warningText)) {
            let data = ADIOS.views.Form.get_values('{$this->uid}_form', '{$this->uid}_');
            data.model = '{$model}';
            ADIOS.renderWindow(
              '{$modelObject->urlBase}/Import/CSV/Import',
              data
            );
          }
        }

        function {$this->uid}_previewCsv() {
          _ajax_update(
            '{$modelObject->urlBase}/Import/CSV/Preview',
            {
              'parentUid': '{$this->uid}',
              'model': '{$model}',
              'csvFile': $('#{$this->uid}_csv_file').val(),
              'columnNamesInFirstLine': ($('#{$this->uid}_column_names_in_first_line').is(':checked') ? '1' : '0'),
              'separator': $('#{$this->uid}_separator').val(),
            },
            '{$this->uid}_preview_div'
          );
        }

        function {$this->uid}_downloadTemplate() {
          window_popup(
            '{$modelObject->urlBase}/Import/CSV/DownloadTemplate',
            {'model': '{$model}'},
            {'type': 'POST'}
          );
        }
      </script>

      <div id='{$this->uid}_form' class='adios ui Form table'>
        <div class='adios ui Form subrow'>
          <div class='input-title'>
            ".$this->translate("CSV file")."
          </div>
          <div class='input-content'>
            ".$fileUploadInput->render()."
          </div>
        </div>
        <div class='adios ui Form subrow'>
          <div class='input-title'>
            <label for='{$this->uid}_column_names_in_first_line'>".$this->translate("Column names are in the first line")."</label>
          </div>
          <div class='input-content'>
            <input type='checkbox' id='{$this->uid}_column_names_in_first_line' checked onchange='{$this->uid}_previewCsv();'>
          </div>
        </div>
        <div class='adios ui Form subrow'>
          <div class='input-title'>
            ".$this->translate("Separator")."
          </div>
          <div class='input-content'>
            <select id='{$this->uid}_separator' onchange='{$this->uid}_previewCsv();'>
              <option value=','>,</option>
              <option value=';'>;</option>
              <option value='TAB'>TAB</option>
            </select>
          </div>
        </div>
        <div class='adios ui Form subrow'>
          <div class='input-title'>
            ".$this->translate("Preview")."
          </div>
          <div class='input-content'>
            <div id='{$this->uid}_preview_div'></div>
          </div>
        </div>
      </div>
    ";

    $window = $this->adios->view->Window([
      'uid' => "{$this->uid}_window",
      'title' => $this->translate("Import from CSV"),
      'content' => $content,
    ]);

    $window->params['header'] = [
      $this->adios->view->button([
        'type' => 'close',
        'onclick' => "{$this->uid}_close();",
      ]),
      $this->adios->view->button([
        'faIcon' => 'fas fa-file-alt',
        'text' => $this->translate("Download CSV file template"),
        'onclick' => "{$this->uid}_downloadTemplate();",
      ]),
      $this->adios->view->button([
        'type' => 'save',
        'text' => $this->translate("Start import !"),
        'onclick' => "{$this->uid}_import();",
      ]),
    ];
    
    return $window->render();

  }
}
