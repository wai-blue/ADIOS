<?php


namespace ADIOS\Core\UI;

class DataTable extends \ADIOS\Core\UI\View {

  private array $tableColumnsEnums = [];

  private string $tableColumnsEnumsInitEditorFunctions = '';
  private string $titleHtml = '';
  private string $script = '';

  var string $twigTemplate = "Core/UI/DataTable"; // Neviem uplne presne, aka ma byt tuto cesta. Treba to vydebugovat.

  private ?\ADIOS\Core\Model $model = null;

  /**
   * __construct
   *
   * @param  mixed $adios
   * @param  mixed $params
   * @return void
   */
  public function __construct(&$adios, $params = null) {
    $this->adios = &$adios;

    $this->params = parent::params_merge([
      'datatableName' => null,
      'model' => null,
      'loadDataAction' => 'UI/DataTable/LoadData',
      'refreshAction' => 'UI/DataTable/Refresh',
      'updateAction' => 'UI/DataTable/Update',
      'defaultValues' => [],
      'columns' => [],
      'data' => [],
      'showAddButton' => true,
      'showDeleteButton' => true,
      'itemsPerPage' => 10,
      'displayStart' => 0,
      'style' => 'padding:10px',
      'tooltip' => '⊘'
    ], $params);

    if ($this->params['model'] == '') {
      exit("UI/DataTable: Don't know what model to work with.");
      return;
    }

    if ($this->params['refresh'] == false) {
      $this->params['datatableName'] = 
        ($this->params['datatableName'] ?? $this->adios->uid) 
        . '_datatable'
      ;
    }

    if (empty($this->params['columnSettings']) && $this->params['model'] != null) {
      $tmpModel = $this->adios->getModel($this->params['model']);

      $this->params['columnSettings'] = $this->adios->db->tables[
        "{$this->adios->gtp}_{$tmpModel->sqlName}"
      ];

      foreach ($this->params['columnSettings'] as $columnName => $column) {
        if ($columnName != '%%table_params%%') {
  
          if ($columnName == 'id') continue;
  
          $this->params['columns'][] = [
            'adios_column_definition' => $column,
            'title' => $column['title'],
            'data' => $columnName
          ];
  
          if (isset($column['enum_values'])) {
            $this->tableColumnsEnums[$columnName] = $column['enum_values'];
          }
        }
      }

      if ($this->params['showDeleteButton']) {
        $this->params['columns'][] = [
          'defaultContent' => '
            <button
              onclick="'. $this->params['datatableName'] . '_delete_row(this)"
            >
              <i class="fa fa-trash"></i class="fa fa-trash">
            </button>
          ',
          'orderable' => false,
          'className' => 'dt-delete-button dt-center'
        ];
      }
    }

    $this->saveParamsToSession($this->params['datatableName'], $this->params);

    /** data */
    if (empty($this->params['data'])) {
      $this->model = $this->adios->getModel($this->params['model']);

      $this->params['data'] = array_values($this->model->getAll());

      /** Enums */
      foreach ($this->params['data'] as $rowKey => $rowData) {
        foreach ($rowData as $colName => $colVal) {
          if (!empty($this->params['columnSettings'][$colName]['enum_values'])) {
            $this->params['data'][$rowKey][$colName] = 
              $this->params['columnSettings'][$colName]['enum_values'][$colVal]
            ;
          }
        }
      }
    }
  }


  public function getTwigParams(): array {
    return [
      "today" => date("d.m.Y"),
    ];
  }
  
  // render() metoda bude pouzita default z View. Vsetky nastavovacky treba presunut do getTwigParams()
  // Komentar zmaz.
  public function Xrender($render_panel = ''): string {
    $this->titleHtml = "<div style='margin-bottom:10px;overflow:auto'>";

    if ($this->params['showAddButton']) {
      $this->titleHtml .= "
        <div style='float:right'>
          ".$this->adios->ui->button([
            'text' => 'Add row',
            'type' => 'add',
            'onclick' => "{$this->params['datatableName']}_add_row()",
          ])->render()."
        </div>
      ";
    }

    $this->titleHtml .= "</div>";

    $contentHtml = "
      {$this->titleHtml}
      <table id='{$this->params['datatableName']}' class='display' style='width:100%;'></table>
    ";

    if ($this->params['refresh']) {
      $this->initEditor();
      $html = $contentHtml;
    } else {
      $html = "
        <div id='{$this->params['datatableName']}_main_div' ".$this->main_params().">
          {$contentHtml}
        </div>
      ";

      $this->script .= "
        function {$this->params['datatableName']}_refresh() {
          _ajax_update(
            '{$this->params['refreshAction']}',
            {
              uid: '{$this->params['datatableName']}'
            },
            '{$this->params['datatableName']}_main_div'
          );
        }
      ";

      foreach ($this->params['columns'] as $colDefinition) {
        $colName = $colDefinition['data'];

        if (
          $colName != null 
          && $colName != 'id'
          && !$colDefinition['adios_column_definition']['readonly']
        ) {
          $tmpEditableEnumData = (array)$colDefinition['adios_column_definition']['enum_values'];

          $editorColType = 'text';
          if (!empty($tmpEditableEnumData)) $editorColType = 'select';

          $this->script .= "
            function {$this->params['datatableName']}_init_editor_{$colName}() {
              let {$this->params['datatableName']}_editorSettings_{$colName} = {
                type: '{$editorColType}',
                placeholder: '{$this->params['tooltip']}',
                tooltip: '{$this->params['tooltip']}'
              };
  
              if ('{$editorColType}' == 'select') {
                {$this->params['datatableName']}_editorSettings_{$colName}.data = '" . json_encode(array_combine($tmpEditableEnumData, $tmpEditableEnumData)) . "';
              }

              {$this->params['datatableName']}.$('td[col-name={$colName}]').editable(function(value, settings) {
                let data = {};
                data.uid = '{$this->params['datatableName']}';
                data.id = $(this).closest('tr').attr('id-record');
                data.colName = $(this).closest('td').attr('col-name');
                data.newValue = value;

                _ajax_read(
                  '{$this->params['updateAction']}',
                  data,
                  (res) => {
                    if (isNaN(res)) {
                      alert(res);
                      $(this).closest('tr').addClass('updated-error');
                    } else {
                      {$this->params['datatableName']}_refresh();
                    }
                  }
                );

                return(value);
              }, {$this->params['datatableName']}_editorSettings_{$colName});
            };

            {$this->params['datatableName']}_init_editor_{$colName}();
          ";

          $this->tableColumnsEnumsInitEditorFunctions .= "{$this->params['datatableName']}_init_editor_{$colName}();";
        }
      }

      if ($this->params['showAddButton']) {
        $this->script .= "
          function {$this->params['datatableName']}_add_row() {
            _ajax_read(
              'UI/DataTable/AddRow', 
              {
                uid: '{$this->params['datatableName']}'
              }, 
              (res) => {
                if (isNaN(res)) {
                  alert(res);
                } else {
                  {$this->params['datatableName']}_refresh();
                };
              }
            );
          }
        ";
      }

      if ($this->params['showDeleteButton']) {
        $this->script .= "
          function {$this->params['datatableName']}_delete_row(_this) {
            let idRecordToDelete = $(_this).closest('tr').attr('id-record');

            _confirm('Are you sure to delete this record?', {}, function() {
              _ajax_read(
                'UI/DataTable/Delete', 
                {
                  uid: '{$this->params['datatableName']}',
                  id: idRecordToDelete,
                }, 
                (res) => {
                  if (isNaN(res)) {
                    alert(res);
                  } else {
                    {$this->params['datatableName']}_refresh();
                  };
                }
              );
            });
          }
        ";
      }
    }

    return $html . "
      <script>
        var {$this->params['datatableName']} = $('#{$this->params['datatableName']}').DataTable({
          columns: " . json_encode($this->params['columns']) . ",
          ajax: '{$this->adios->config['url']}/{$this->params['loadDataAction']}?uid={$this->params['datatableName']}',
          proccessing: true,
          serverSide: true,
          pageLength: {$this->params['itemsPerPage']},
          displayStart: {$this->params['displayStart']},
          'fnDrawCallback': () => {
            {$this->tableColumnsEnumsInitEditorFunctions}
          },
          createdRow: function(row, data, dataIndex) {
            $(row).attr('id', '{$this->params['datatableName']}_' + data.id);
            $(row).attr('id-record', data.id);

            // Add column-name attribute to td element
            let columnNames =  " . json_encode($this->params['columns']) . ";
            $.each($('td', row), function (colIndex) {
              if (data[columnNames[colIndex]['data']] == '') {
                $(this).addClass('dt-empty-td');
              }

              $(this).attr('col-name', columnNames[colIndex]['data']);
            });
          }
        });

        {$this->script}
      </script>
    ";
  }

  private function initEditor(): void {
    foreach ($this->params['columns'] as $colDefinition) {
      $colName = $colDefinition['data'];

      if (
        $colName != null 
        && $colName != 'id'
        && !$colDefinition['adios_column_definition']['readonly']
      ) {
        $this->tableColumnsEnumsInitEditorFunctions .= "{$this->params['datatableName']}_init_editor_{$colName}();";
      }
    }
  }


}