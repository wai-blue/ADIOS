<?php


namespace ADIOS\Core\UI;

class DataTable extends \ADIOS\Core\UI\View {

  private array $newRowDefaultValues = [];
  private array $tableColumnsEnums = [];

  private string $tableColumnsEnumsInitEditorFunctions = '';
  private string $titleHtml = '';
  private string $script = '';

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
      'datatable_name' => '',
      'table' => '',
      'show_add_button' => true,
      'show_delete_button' => true,
      'edit_enabled' => true,
      'default_values' => [],
      'onrefresh_action' => 'UI/DataTable/Refresh',
      'onupdate_action' => 'UI/DataTable/Update',
      'table_data' => [],
      'table_data_reset' => true,
      'table_columns' => [],
      'style' => 'padding:10px'
    ], $params);

    if ($this->params['refresh'] == false) {
      $this->params['datatable_name'] = $this->params['datatable_name'] . '_datatable';
    }

    if ($this->params['table_data_reset']) $this->params['table_data'] = [];

    if (empty($this->params['column_settings']) && $this->params['table'] != '') {
      $this->params['column_settings'] = $this->adios->db->tables[$this->params['table']];
    }

    /** GET DATA */
    if (empty($this->params['table_data'])) {
      $this->params['table_data'] = array_values(
        $this->adios->db->get_all_rows_query("
          SELECT
            *
          FROM {$this->params['table']}
        ")
      );


      // ENUMS
      foreach ($this->params['table_data'] as $rowKey => $rowData) {
        foreach ($rowData as $colName => $colVal) {
          if (!empty($this->params['column_settings'][$colName]['enum_values'])) {
            $this->params['table_data'][$rowKey][$colName] = 
              $this->params['column_settings'][$colName]['enum_values'][$colVal]
            ;
          }
        }
      }
    }

  }

  public function render($render_panel = ''): string {
    /** titleHtml */
    $this->titleHtml = "<div style='margin-bottom:10px;overflow:auto'>";

    /** ADD BUTTON HTML*/
    if ($this->params['show_add_button']) {
      $this->titleHtml .= "
        <div style='float:right'>
          ".$this->adios->ui->button([
            'text' => 'Add row',
            'type' => 'add',
            'onclick' => "{$this->params['datatable_name']}_add_row()",
          ])->render()."
        </div>
      ";
    }

    $this->titleHtml .= "</div>";

    $contentHtml = "
      {$this->titleHtml}
      <table id='{$this->params['datatable_name']}' class='display' style='width:100%;'></table>
    ";

    if ($this->params['refresh']) {

      // Initialize columns editor functions
      foreach ($this->params['table_columns'] as $colDefinition) {
        $colName = $colDefinition['data'];

        if (
          $colName != null 
          && $colName != 'id'
          && !$colDefinition['adios_column_definition']['readonly']
        ) {
          $this->script .= "{$this->params['datatable_name']}_init_editor_{$colDefinition['data']}();";
        }
      }

      $html = $contentHtml;
    } else {
      $html = "
        <div id='{$this->params['datatable_name']}_main_div' ".$this->main_params().">
          {$contentHtml}
        </div>
      ";

      foreach ($this->params['column_settings'] as $columnName => $column) {
        if ($columnName != '%%table_params%%') {

          if ($columnName == 'id') continue;

          // Create header columns
          $this->params['table_columns'][] = [
            'adios_column_definition' => $column,
            'title' => $column['title'],
            'data' => $columnName
          ];

          // Set new row default values 
          if ($this->params['show_add_button']) {
            $this->newRowDefaultValues[$columnName] = '';
          }

          // Set enum values
          if (isset($column['enum_values'])) {
            $this->tableColumnsEnums[$columnName] = $column['enum_values'];
          }
        }
      }

      if ($this->params['show_delete_button']) {
        $this->params['table_columns'][] = [
          'defaultContent' => '
            <button
              onclick="'. $this->params['datatable_name'] . '_delete_row(this)"
            >
              <i class="fa fa-trash"></i class="fa fa-trash">
            </button>
          ',
          'orderable' => false,
          'className' => 'dt-delete-button dt-center'
        ];
      }

      foreach ($this->params['table_columns'] as $colDefinition) {
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
            function {$this->params['datatable_name']}_refresh() {
              _ajax_update(
                '{$this->params['onrefresh_action']}',
                {
                  table: '{$this->params['table']}',
                  datatable_name: '{$this->params['datatable_name']}',
                  table_columns: " . json_encode($this->params['table_columns']) .",
                  refresh: true
                },
                '{$this->params['datatable_name']}_main_div'
              );
            }

            function {$this->params['datatable_name']}_init_editor_{$colName}() {
              let {$this->params['datatable_name']}_editorSettings_{$colName} = {};
              {$this->params['datatable_name']}_editorSettings_{$colName}.type = '{$editorColType}';
  
              if ({$this->params['datatable_name']}_editorSettings_{$colName}.type == 'select') {
                {$this->params['datatable_name']}_editorSettings_{$colName}.data = '" . json_encode(array_combine($tmpEditableEnumData, $tmpEditableEnumData)) . "';
              }

              {$this->params['datatable_name']}.$('td[col-name={$colName}]').editable(function(value, settings) {
                let data = {};
                data.table = '{$this->params['table']}';
                data.model = '{$this->params['model']}';
                data.datatable_name = '{$this->params['datatable_name']}';
                data.onupdate = '1';
                data.id = $(this).closest('tr').attr('id-record');
                data.colName = $(this).closest('td').attr('col-name');
                data.newValue = value;

                _ajax_read(
                  '{$this->params['onupdate_action']}',
                  data,
                  (res) => {
                    if (isNaN(res)) {
                      alert(res);
                      $(this).closest('tr').addClass('updated-error', 500, () => {
                        $(this).closest('tr').removeClass('updated-error')
                      });
                    } else {
                      $(this).closest('tr').addClass('updated-success', 500, () => {
                        $(this).closest('tr').removeClass('updated-success');

                        {$this->params['datatable_name']}_refresh();
                      });
                    }
                  }
                );

                return(value);
              }, {$this->params['datatable_name']}_editorSettings_{$colName});
            };

            {$this->params['datatable_name']}_init_editor_{$colName}();
          ";

          $this->tableColumnsEnumsInitEditorFunctions .= "{$this->params['datatable_name']}_init_editor_{$colName}();";
        }
      }

      /** SCRIPT */
      /** ADD BUTTON SCRIPT*/
      if ($this->params['show_add_button']) {
        $this->script .= "
          function {$this->params['datatable_name']}_add_row() {
            let data = {};
            data.table = '{$this->params['table']}';
            data.model = '{$this->params['model']}';
            data.default_values = '" . json_encode($this->params['default_values']) . "';
            data.uid = '{$this->params['datatable_name']}';

            _ajax_read(
              'UI/DataTable/AddRow', 
              data, 
              (res) => {
                if (isNaN(res)) {
                  alert(res);
                } else {
                  let defaultValues = " . json_encode($this->newRowDefaultValues) . ";
                  defaultValues.id = res;

                  {$this->params['datatable_name']}.row.add(defaultValues)
                    .node().id = '{$this->params['datatable_name']}_' + res
                  ;

                  {$this->params['datatable_name']}.draw();
                  {$this->tableColumnsEnumsInitEditorFunctions}
                };
              }
            );
          }
        ";
      }

      /** DELETE BUTTON SCRIPT*/
      if ($this->params['show_delete_button']) {
        $this->script .= "
          function {$this->params['datatable_name']}_delete_row(_this) {
            let data = {};
            data.table = '{$this->params['table']}';
            data.model = '{$this->params['model']}';
            data.id = $(_this).closest('tr').attr('id-record');

            _confirm('Are you sure to delete this record?', {}, function() {
              _ajax_read(
                'UI/DataTable/Delete', 
                data, 
                (res) => {
                  if (isNaN(res)) {
                    alert(res);
                  } else {
                    {$this->params['datatable_name']}.row($(_this).closest('tr'))
                      .remove()
                      .draw()
                    ;
                  };
                }
              );
            });
          }
        ";
      }

      $html .= "
        <style>
          .updated-success {
            background: #f1ffed !important;
          }
          .updated-error {
            background: #ffeded !important;
          }
          .dt-delete-button button {
            background: none;
            box-shadow: 0 0 3px grey;
            border-radius: 5px;
          }
          .dt-delete-button button:hover {
            color: grey;
            box-shadow: 0 0 3px #ad241f;
          }
          .dt-delete-button i {
            color: grey;
          }
          table {          
            table-layout: fixed;
            word-wrap: break-word;
          }
        </style>
      ";
    }

    return $html . "
      <script>
        var {$this->params['datatable_name']} = $('#{$this->params['datatable_name']}').DataTable({
          columns: " . json_encode($this->params['table_columns']) . ",
          data: " . json_encode($this->params['table_data']) . ",
          createdRow: function(row, data, dataIndex) {
            $(row).attr('id', '{$this->params['datatable_name']}_' + data.id);
            $(row).attr('id-record', data.id);

            // Add column-name attribute to td element
            let columnNames =  " . json_encode($this->params['table_columns']) . ";
            $.each($('td', row), function (colIndex) {
              $(this).attr('col-name', columnNames[colIndex]['data']);
            });
          }
        });

        {$this->script}
      </script>
    ";
  }

}