<?php

namespace ADIOS\Core\Views;

class TableSearch extends \ADIOS\Core\View {
  
  public function render(string $panel = ''): string
  {
    $model = $this->adios->getModel($this->params['model']);
    $searchGroup = $this->params['searchGroup'];

    $unsearchableColumnTypes = [
      "image",
      "file",
    ];

    $tabs = [];
    $tabs[$model->fullName] = [];
    $tabs[$model->fullName]["title"] = $model->tableTitle;
    $tabs[$model->fullName]["items"] = [];

    foreach ($model->columns() as $colName => $colDef) {
      if (!($colDef["is_searchable"] ?? TRUE)) continue;

      if ($colDef['type'] == "lookup") {
        $lookupModelName = $colDef['model'];
        $lookupModel = $this->adios->getModel($lookupModelName);
        $tabs[$lookupModel->fullName] = [];
        $tabs[$lookupModel->fullName]["title"] = $lookupModel->tableTitle;

        foreach ($lookupModel->columns() as $lookupColName => $lookupColDef) {
          if (!($colDef["is_searchable"] ?? TRUE)) continue;

          if (!in_array($lookupColDef["type"], $unsearchableColumnTypes)) {
            $tabs[$lookupModel->fullName]["items"][] = [
              "title" => $lookupColDef['title'],
              "input" => $this->adios->view->Input([
                "model" => $this->params['model'],
                "type" => $lookupColDef["type"],
                "value" => NULL,
                "uid" => "{$this->uid}_LOOKUP___{$colName}___{$lookupColName}",
              ]),
            ];
          }
        }
      }

      if (!in_array($colDef["type"], $unsearchableColumnTypes)) {
        $tabs[$model->fullName]["items"][] = [
          "title" => $colDef['title'],
          "input" => $this->adios->view->Input([
            "model" => $colDef['model'] ?? $this->params['model'],
            "type" => $colDef["type"],
            "input_style" => "select",
            "value" => NULL,
            "uid" => "{$this->uid}_{$colName}",
          ]),
        ];
      }
    }

    $content = "
      <div class='row'>
        <div class='col-12 col-lg-8'>
          " . (new \ADIOS\Core\Views\Inputs\SettingsPanel(
            $this->adios,
            [
              "uid" => $this->uid . "_settings_panel",
              "settings_group" => "tableSearch",
              "template" => [
                "tabs" => $tabs,
              ],
            ]
          ))->render() . "
        </div>
        <div class='col-12 col-lg-4'>
          <div class='row'>
            <div class='col-12 pb-2'>
              ".(new \ADIOS\Core\Views\Button($this->adios, [
                'text' => $this->translate('Save this search'),
                'onclick' => "{$this->uid}_save_search();",
                'class' => 'btn-light',
                'fa_icon' => 'fas fa-save',
              ]))->render()."
            </div>
            <div class='col-12'>
              <div class='card shadow mb-4'>
                <a class='d-block card-header py-3'>
                  <h6 class='m-0 font-weight-bold text-primary'>" . $this->translate("Saved searches") . "</h6>
                </a>
                <div>
                  <div class='card-body'>
                    <div id='{$this->uid}_saved_searches_div'>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <script>
        function {$this->uid}_get_search_string() {
          let values = JSON.stringify(ui_form_get_values('{$this->uid}_settings_panel_form', '{$this->uid}_'));
          return Base64.encode(values);
        }

        function {$this->uid}_search(searchString) {
          if (typeof searchString == 'undefined') {
            searchString = {$this->uid}_get_search_string();
          }

          desktop_update(
            '{$model->getFullUrlBase($this->params)}',
            {
              'searchGroup': '" . ads($searchGroup) . "',
              'search': searchString,
            },
            {
              'type': 'POST',
            }
          )
        }

        function {$this->uid}_save_search() {
          let searchName = prompt('Enter name of the search:', 'My Search');

          if (searchName != '' && searchName != null) {
            _ajax_read(
              'UI/Table/Search/Save',
              {
                'model': '" . ads($model) . "',
                'searchGroup': '" . ads($searchGroup) . "',
                'searchName': searchName,
                'search': {$this->uid}_get_search_string(),
              },
              function(res) {
                {$this->uid}_update_saved_searches();
              }
            );
          }
        }

        function {$this->uid}_update_saved_searches() {
          _ajax_update(
            'UI/Table/Search/SavedSearchesOverview',
            {
              'parentUid': '{$this->uid}',
              'searchGroup': '" . ads($searchGroup) . "'
            },
            '{$this->uid}_saved_searches_div'
          );
        }

        function {$this->uid}_delete_saved_search(searchName) {
          if (confirm('Do you want to delete saved search?\\n\\n' + searchName)) {
            _ajax_read(
              'UI/Table/Search/Delete',
              {
                'searchGroup': '" . ads($searchGroup) . "',
                'searchName': searchName,
              },
              function(res) {
                {$this->uid}_update_saved_searches();
              }
            );
          }
        }

        function {$this->uid}_load_saved_search(searchName) {
          _ajax_read(
            'UI/Table/Search/Load',
            {
              'searchGroup': '" . ads($searchGroup) . "',
              'searchName': searchName,
            },
            function(res) {
              // TODO: nie je dokoncene nastavovanie hodnot cez JS
              // pretoze chyba na to vhodne JS API.
              for (var i in res) {
                $('#{$this->uid}_' + i).val(res[i]);
              }
            }
          );
        }

        function {$this->uid}_close() {
          window_close('{$this->window->uid}');
        }

        {$this->uid}_update_saved_searches();
      </script>
    ";

    if ($this->window !== NULL) {
      $this->window->setTitle($this->translate("Advanced search") . ": " . hsc($searchGroup));
      $this->window->setCloseButton(
        new \ADIOS\Core\Views\Button($this->adios, [
          'type' => 'close',
          'onclick' => "{$this->uid}_close();",
        ]),
      );
      $this->window->setHeaderLeft([
        new \ADIOS\Core\Views\Button($this->adios, [
          'type' => 'apply',
          'onclick' => "{$this->uid}_search();",
        ]),
      ]);

    }

    return $this->applyDisplayMode((string) $content);
  }
}
