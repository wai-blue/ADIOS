<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\UI;

use \ADIOS\Core\DB\Query as Q;

class Table extends \ADIOS\Core\UI\View
{

  var $model = NULL;

  var $columns = [];
  var $columnsFilter = [];

  var $data = [];
  var array $search = [];

  private $allRowsCount = 0;

  /**
   * __construct
   *
   * @param  mixed $adios
   * @param  mixed $params
   * @return void
   */
  public function __construct($adios, $params = null)
  {

    $this->adios = $adios;

    if ($params['refresh'] && !empty($params['uid'])) {
      $params = parent::params_merge(
        $_SESSION[_ADIOS_ID]['table'][$params['uid']],
        $params
      );
    }

    // defaultne parametre
    $params = parent::params_merge([
      'title' => '',
      'tag' => '',
      'page' => 1,

      'where' => '',
      'having' => '',
      'orderBy' => '',
      'itemsPerPage' => 25,

      'refreshAction' => 'UI/Table',

      'onclick' => '',

      'showTitle' => true,
      'showPaging' => true,
      'showColumnTitles' => true,
      'showColumnsFilter' => true,
      'showControls' => true,
      'showAddButton' => true,
      'showSearchButton' => true,
      'showExportCsvButton' => true,
      'showImportCsvButton' => false,
      'allow_order_modification' => true,

      'buttons' => [],

      'columnsOrder' => [],
      'form_data' => [],
    ], $params);

    if (empty($params['model'])) {
      throw new \Exception("UI/Table: Don't know what model to work with.");
    }

    $this->model = $this->adios->getModel($params['model']);
    $params['table'] = $this->model->getFullTableSqlName();

    if (empty($params['uid'])) {
      $params['uid'] = $this->adios->getUid($params['model']);
    }

    if (empty($params['title'])) {
      $params['title'] = $this->model->tableTitle;
    }

    if (!empty($params['search'])) {
      $this->search = @json_decode(base64_decode($params['search']), TRUE);
    } else {
      $this->search = [];
    }

    if ((bool) $params['reset']) {
      $params['page'] = 1;
    }

    if ($this->model->isCrossTable) {
      $params['onclick'] = "";
      $params['showAddButton'] = FALSE;
    }

    $paramsToSession = $params;
    unset($params['__IS_AJAX__']);
    unset($params['__IS_WINDOW__']);
    unset($params['_REQUEST']);
    unset($params['_COOKIE']);

    foreach ($paramsToSession as $k => $v) {
      if (strpos($k, "column_filter_") === 0) {
        unset($paramsToSession[$k]);
      }
    }

    $_SESSION[_ADIOS_ID]['table'][$params['uid']] = $paramsToSession;

    parent::__construct($adios, $params);







    $this->model->onTableBeforeInit($this);

    $this->params["columns"] = $this->model->columns();
    $this->params = $this->model->tableParams($this->params, $this);

    $this->columns = $this->params["columns"];

    $this->params['page'] = (int) $this->params['page'];
    $this->params['itemsPerPage'] = (int) $this->params['itemsPerPage'];

    if (_count($this->params['columnsOrder'])) {
      $tmp_columns = [];
      foreach ($this->params['columnsOrder'] as $col_name) {
        $tmp_columns[$col_name] = $this->columns[$col_name];
      }
      foreach ($this->columns as $col_name => $col_definition) {
        if (!isset($tmp_columns[$col_name])) {
          $tmp_columns[$col_name] = $col_definition;
        }
      }
      $this->columns = $tmp_columns;
    }

    if (
      !empty($this->params['foreignKey'])
      && isset($this->columns[$this->params['foreignKey']])
    ) {
      $this->columns[$this->params['foreignKey']]['show_column'] = FALSE;
    }

    //
    $this->columnsFilter = [];

    foreach ($this->columns as $col_name => $col_def) {
      if (isset($this->params['column_filter_' . $col_name])) {
        $this->columnsFilter[$col_name] = $this->params['column_filter_' . $col_name];
        unset($this->params['column_filter_' . $col_name]);
      }
    }

    //
    if (empty($this->params['buttons']['add']['onclick'])) {
      $tmpUrl = $this->model->getFullUrlBase($this->params);
      $tmpParentFormId = (int) ($this->params['form_data']['id'] ?? 0);

      if (!empty($this->params['foreignKey'])) {
        $fkColumnName = $this->params['foreignKey'];
        $fkColumnDefinition = $this->columns[$fkColumnName] ?? NULL;
        if ($fkColumnDefinition !== NULL) {
          $tmpModel = $this->adios->getModel($fkColumnDefinition['model']);
          $tmpUrl = $tmpModel->urlBase."/".$tmpParentFormId."/".$tmpUrl;
        }

        $tmpUrl = str_replace("{{ {$this->params['foreignKey']} }}", $tmpParentFormId, $tmpUrl);
      }

      $this->params['buttons']['add']['onclick'] = "
        window_render('" . $tmpUrl . "/Add')
      ";
    }

    // kontroly pre vylucenie nelogickosti parametrov

    if (!$this->params['showControls']) {
      $this->params['showPaging'] = false;
    }

    if ('lookup_select' == ($this->params['list_type'] ?? '')) {
      $this->params['show_insert_row'] = false;
      $this->params['show_insert_row'] = false;
      $this->params['showTitle'] = false;

      $this->params['showAddButton'] = false;
    }

    if ($this->params['readonly']) {
      $this->params['showAddButton'] = false;
      $this->params['showSearchButton'] = false;
      $this->params['showExportCsvButton'] = false;
      $this->params['showImportCsvButton'] = false;
    }

    $this->model->onTableAfterInit($this);

    $this->loadData();

    $this->adios->test->assert("loadedRowsCount", count($this->data), ["model" => $params['model']]);

    // strankovanie

    $pagesCount = ceil($this->allRowsCount / $this->params['itemsPerPage']);
    $pageButtonsCount = 4;

    if ($this->params['showPaging']) {
      $this->add(
        $this->adios->ui->button(
          [
            'fa_icon' => 'fas fa-angle-double-left',
            'class' => 'btn-light btn-circle btn-sm',
            'onclick' => "ui_table_show_page('{$this->params['uid']}', '1'); ",
            'disabled' => (1 == $this->params['page'] ? true : false)
          ]
        ),
        'paging'
      );
      $this->add(
        $this->adios->ui->button([
          'fa_icon' => 'fas fa-angle-left',
          'class' => 'btn-light btn-circle btn-sm',
          'onclick' => "ui_table_show_page('{$this->params['uid']}', '" . ($this->params['page'] - 1) . "'); ",
          'disabled' => (1 == $this->params['page'] ? true : false)
        ]),
        'paging'
      );

      for ($i = 1; $i <= $pagesCount; ++$i) {
        if ($i == $this->params['page']) {
          $this->add(
            "
              <input
                type='text'
                value='{$this->params['page']}'
                id='{$this->params['uid']}_paging_bottom_input'
                onchange=\"
                  ui_table_show_page('{$this->params['uid']}', this.value);
                \"
                onkeypress=\"
                  if (event.keyCode == 13) {
                    ui_table_show_page('{$this->params['uid']}', this.value);
                  }
                \"
                onclick=\"
                  this.select();
                \"
              />
              <script>
                draggable_int_input(
                  '{$this->params['uid']}_paging_bottom_input',
                  { min_val: 1, max_val: {$pagesCount} }
                );
              </script>
            ",
            'paging'
          );
        } elseif (
          abs($this->params['page'] - $i) <= ($pageButtonsCount / 2)
          || (
            $this->params['page'] <= ($pageButtonsCount / 2)
            && $i <= ($pageButtonsCount + 1)
          )
          || (
            $pagesCount - $this->params['page'] <= $pageButtonsCount / 2
            && $i >= $pagesCount - $pageButtonsCount
          )
        ) {
          $this->add(
            $this->adios->ui->button([
              'text' => $i,
              'class' => 'pages',
              'onclick' => "ui_table_show_page('{$this->params['uid']}', '{$i}');",
              'show_border' => FALSE
            ]),
            'paging'
          );
        }
      }

      $this->add(
        $this->adios->ui->button([
          'fa_icon' => 'fas fa-angle-right',
          'class' => 'btn-light btn-circle btn-sm',
          'onclick' => "ui_table_show_page('{$this->params['uid']}', '" . ($this->params['page'] + 1) . "'); ",
          'disabled' => ($this->params['page'] == $pagesCount || 0 == $this->allRowsCount ? true : false)
        ]),
        'paging'
      );
      $this->add(
        $this->adios->ui->button([
          'fa_icon' => 'fas fa-angle-double-right',
          'class' => 'btn-light btn-circle btn-sm',
          'onclick' => "ui_table_show_page('{$this->params['uid']}', '" . ($pagesCount) . "'); ",
          'disabled' => ($this->params['page'] == $pagesCount || 0 == $this->allRowsCount ? true : false)
        ]),
        'paging'
      );
    }

    $this->params['showAddButton'] = (empty($this->params['buttons']['add']['onclick']) ? FALSE : $this->params['showAddButton']);

    if (empty($this->params['buttons']['add']['type'])) {
      $this->params['buttons']['add']['type'] = 'add';
    }

    if (!empty($this->model->addButtonText)) {
      $this->params['buttons']['add']['text'] = $this->model->addButtonText;
    }
  }

  /**
   * loadData
   *
   * @return void
   */
  public function loadData()
  {
    $db = $this->adios->db;
    if (empty($this->params['table'])) return;

    // where and whereRaw
    $whereRaw = "";
    $where = [];
    
    if (is_string($this->params['where'])) {
      $whereRaw = (empty($this->params['where']) ? 'TRUE' : $this->params['where']);

      if (
        !empty($this->params['foreignKey'])
        && (int) $this->params['form_data']['id'] > 0
      ) {
        $fkColumnName = $this->params['foreignKey'];
        $fkColumnDefinition = $this->columns[$fkColumnName] ?? NULL;
        if ($fkColumnDefinition !== NULL) {
          $tmpModel = $this->adios->getModel($fkColumnDefinition['model']);
          $whereRaw .= "
            and
              `lookup_{$tmpModel->getFullTableSqlName()}_{$fkColumnName}`.`id`
              = ".((int) $this->params['form_data']['id'])
          ;
        }
      }
    } else {
      $where = $this->params['where'];
    }

    // orderBy
    $orderBy = [];
    if (!empty($this->params['orderBy'])) {
      foreach (explode(',', $this->params['orderBy']) as $item) {
        $item = trim($item);
        list($tmpColumn, $tmpDirection) = explode(' ', $item);

        if (($this->model->columns()[$tmpColumn]['type'] ?? '') == 'lookup') {
          $tmpColumn = $tmpColumn . ':LOOKUP';
        }

        $orderBy[] = [$tmpColumn, $tmpDirection];
      }
    }

    // having
    $having = [];
    foreach ($this->columnsFilter as $tmpColumn => $tmpFilter) {
      $having[] = [$tmpColumn, Q::columnFilter, $tmpFilter];
    }

    foreach ($this->search as $tmpColumn => $tmpFilter) {
      $having[] = [$tmpColumn, Q::columnFilter, $tmpFilter];
    }

    // query
    $query = $db->select($this->model, [Q::countRows])
      ->columns([Q::allColumnsWithLookups])
      ->where($where)
      ->whereRaw($whereRaw)
      ->having($having)
      ->order($orderBy)
    ;

    // limit
    if ($this->params['showPaging']) {
      $query = $query->limit(
        max(0, ($this->params['page'] - 1) * $this->params['itemsPerPage']),
        $this->params['itemsPerPage']
      );
    }

    // fetch
    $this->data = $query->fetch();

    $this->allRowsCount = $db->countRowsFromLastSelect();

    if ($this->params['page'] * $this->params['itemsPerPage'] > $this->allRowsCount) {
      $this->params['page'] = floor($this->allRowsCount / $this->params['itemsPerPage']) + 1;
    }

    // onTableAfterDataLoaded
    $this->model->onTableAfterDataLoaded($this);
  }

  /**
   * getCellHtml
   *
   * @param  mixed $columnName
   * @param  mixed $columnDefinition
   * @param  mixed $rowValues
   * @return void
   */
  public function getCellHtml($columnName, $columnDefinition, $rowValues)
  {
    if (!empty($col_def['input']) && is_string($col_def['input'])) {
      $inputClassName = "\\ADIOS\\" . str_replace("/", "\\", $col_def['input']);
      $tmpInput = new $inputClassName($this->adios, "", ["value" => $rowValues[$columnName]]);
      $cellHtml = $tmpInput->formatValueToHtml();
    } else if ($this->adios->db->isRegisteredColumnType($columnDefinition['type'])) {
      $cellHtml = $this->adios->db->columnTypes[$columnDefinition['type']]->get_html(
        $rowValues[$columnName],
        [
          'col_name' => $columnName,
          'col_definition' => $columnDefinition,
          'row' => $rowValues,
        ]
      );
    } else {
      $cellHtml = $rowValues[$columnName];
    }

    return $cellHtml;
  }

  /**
   * getCellCsv
   *
   * @param  mixed $columnName
   * @param  mixed $columnDefinition
   * @param  mixed $rowValues
   * @return void
   */
  public function getCellCsv($columnName, $columnDefinition, $rowValues)
  {
    if (!empty($col_def['input']) && is_string($col_def['input'])) {
      $inputClassName = "\\ADIOS\\" . str_replace("/", "\\", $col_def['input']);
      $tmpInput = new $inputClassName($this->adios, "", ["value" => $rowValues[$columnName]]);
      $cellCsv = $tmpInput->formatValueToCsv();
    } else if ($this->adios->db->isRegisteredColumnType($columnDefinition['type'])) {
      if (!empty($columnDefinition['enum_values'])) {
        $cellCsv = $columnDefinition['enum_values'][$rowValues[$columnName]];
      } else {
        $cellCsv = $this->adios->db->columnTypes[$columnDefinition['type']]->get_csv(
          $rowValues[$columnName],
          [
            'col_name' => $columnName,
            'col_definition' => $columnDefinition,
            'row' => $rowValues,
          ]
        );
      }
    } else {
      $cellCsv = $rowValues[$columnName];
    }

    return $cellCsv;
  }

  /**
   * render
   *
   * @param  mixed $panel
   * @return void
   */
  public function render(string $panel = '')
  {
    $params = $this->params;

    $html = "";
    $this->add_class('Container');

    if (!in_array("UI/Form", $this->adios->actionStack)) {
      $this->add_class('shadow');
    }

    if (!$this->params['__IS_WINDOW__']) {
      $this->add_class('desktop');
    }

    if (!$this->params['refresh']) {
      if (_count($this->params)) {
        $tmp = json_encode($this->params);
        if (!empty($tmp)) {
          $html .= "
            <script>
              ui_table_params['{$this->uid}'] = JSON.parse(Base64.decode('" . base64_encode($tmp) . "'));
            </script>
          ";
        }
      }

      if ($this->params['showTitle']) {

        $moreActionsButtonItems = [];

        if ($this->params['showSearchButton']) {
          $searchAction = $this->model->searchAction ?? $this->model->getFullUrlBase($params) . "/Search";

          $moreActionsButtonItems[] = [
            "fa_icon" => "fas fa-search",
            "text" => $this->translate("Search"),
            "onclick" => "window_render('{$searchAction}');",
          ];
        }

        if ($this->params['showExportCsvButton']) {
          $exportCsvAction = $this->model->exportCsvAction ?? $this->model->getFullUrlBase($params) . "/Export/CSV";

          $moreActionsButtonItems[] = [
            "fa_icon" => "fas fa-file-export",
            "text" => $this->translate("Export to CSV"),
            "onclick" => "
              let tmpTableParams = Base64.encode(JSON.stringify(ui_table_params['{$this->uid}']));
              window_popup('{$exportCsvAction}', {tableParams: tmpTableParams}, {'type': 'POST'});
              // window.open(
              //   '{$this->adios->config['url']}/{$exportCsvAction}?tableParams=' + tmpTableParams
              // );
            ",
          ];
        }

        if ($this->params['showImportCsvButton']) {
          $importCsvAction = $this->model->importCsvAction ?? $this->model->getFullUrlBase($params) . "/Import/CSV";

          $moreActionsButtonItems[] = [
            "fa_icon" => "fas fa-file-import",
            "text" => $this->translate("Import from CSV"),
            "onclick" => "
              let tmpTableParams = Base64.encode(JSON.stringify(ui_table_params['{$this->uid}']));
              window_render(
                '{$importCsvAction}',
                { model: '" . ads($this->params['model']) . "' }
              );
            ",
          ];
        }

        $titleButtons = [];

        if ($this->params['showAddButton']) {
          $titleButtons[] = $this->adios->ui->Button($this->params['buttons']['add']);
        }

        if (_count($moreActionsButtonItems)) {
          $titleButtons[] = $this->adios->ui->Button([
            "fa_icon" => "fas fa-ellipsis-v",
            "title" => "",
            "onclick" => "window_render('{$searchAction}');",
            "dropdown" => $moreActionsButtonItems,
            "class" => "btn-light",
          ]);
        }

        if (
          !empty($titleButtons)
          || !empty($this->params['title'])
          || !empty($this->params['right'])
        ) {
          $html .= $this->adios->ui->Title([
            'left' => $titleButtons,
            'center' => $this->params['title'],
            'right' => $this->params['right'],
          ])->render();
        }
      }

      if (_count($this->search)) {
        $tmpSearchHtml = "";
        $tmpColumns = $this->model->columns();

        foreach ($this->search as $searchColName => $searchValue) {
          if (!empty($searchValue)) {
            $tmpColumn = $this->columns[$searchColName];

            if (strpos($searchColName, "LOOKUP___") === 0) {
              list($tmp, $tmpSrcColName, $tmpLookupColName) = explode("___", $searchColName);
              $tmpSrcColumn = $tmpColumns[$tmpSrcColName];
              $tmpLookupModel = $this->adios->getModel($tmpSrcColumn["model"]);
              $tmpColumn = $tmpLookupModel->columns()[$tmpLookupColName];
              $tmpTitle = $tmpLookupModel->tableTitle . " / " . $tmpColumn["title"];
            } else if ($tmpColumn["type"] == "lookup" && is_numeric($searchValue)) {
              $tmpLookupModel = $this->adios->getModel($tmpColumn["model"]);

              $tmp = reset($tmpLookupModel->lookupQuery(
                NULL,
                NULL,
                [],
                [],
                "`id` = {$searchValue}" // having
              )->fetch());

              $tmpTitle = $tmpColumn['title'];
              $searchValue = $tmp['input_lookup_value'];
            } else {
              $tmpTitle = $tmpColumn['title'];
            }

            $tmpSearchHtml .= "
              " . hsc($tmpTitle) . "
              = " . hsc($searchValue) . "
            ";
          }
        }

        $html .= "
          <div class='card shadow mb-4'>
            <a class='card-header py-3'>
              <h6 class='m-0 font-weight-bold text-primary'>
                <i class='fas fa-filter mr-2'></i>
                " . $this->translate("Records are filtered") . "
              </h6>
            </a>
            <div>
              <div class='card-body'>
                <div class='mb-2'>
                  {$tmpSearchHtml}
                </div>
                " . $this->adios->ui->Button([
                  "type" => "close",
                  "text" => $this->translate("Clear filter"),
                  "onclick" => "desktop_update('{$this->adios->requestedAction}');",
                ])->render() . "
              </div>
            </div>
          </div>
        ";
      }

      if (!empty($this->params['header'])) {
        $html .= "
          <div class='adios ui TableHeader'>
            {$params['header']}
          </div>
        ";
      }

      $html .= "
        <div
          " . $this->main_params() . "
          data-model='" . ads($this->params['model']) . "'
          data-refresh-action='" . ads($this->params['refreshAction']) . "'
          data-refresh-params='" . (empty($this->params['uid'])
        ? json_encode($this->params['_REQUEST'])
        : json_encode(['uid' => $this->params['uid']])
      ) . "'
          data-action='" . ads($this->adios->action) . "'
          data-page='" . (int) $this->params['page'] . "'
          data-items-per-page='" . (int) $this->params['items-per-page'] . "'
          data-is-ajax='" . ($this->adios->isAjax() ? "1" : "0") . "'
          data-is-in-form='" . (in_array("UI/Form", $this->adios->actionStack) ? "1" : "0") . "'
        >
      ";
    }

    if (_count($this->columns)) {
      foreach ($this->columns as $col_name => $col_def) {
        if (!$col_def['show_column']) {
          unset($this->columns[$col_name]);
        }
      }

      $ordering = explode(' ', $this->params['orderBy']);

      $html .= "<div class='adios ui Table Header'>";

      // title riadok - nazvy stlpcov

      if ($params['showColumnTitles']) {
        $html .= "<div class='Row ColumnNames'>";

        foreach ($this->columns as $col_name => $col_def) {
          if ($params['allow_order_modification']) {
            $new_ordering = "$col_name asc";
            $order_class = 'unordered';

            if ($ordering[0] == $col_name || $params['table'] . '.' . $col_name == $ordering[0]) {
              switch ($ordering[1]) {
                case 'asc':
                  $new_ordering = "$col_name desc";
                  $order_class = 'asc_ordered';
                  break;
                case 'desc':
                  $new_ordering = 'none';
                  $order_class = 'desc_ordered';
                  break;
              }
            }
          }

          $html .= "
            <div
              class='Column {$col_def['css_class']} {$order_class}'
              " . ($params['allow_order_modification'] ? "
                onclick='
                  ui_table_refresh(
                    \"{$params['uid']}\",
                    {
                     reset: \"1\",
                     orderBy: \"{$new_ordering}\"
                    });
                '
              " : "") . "
            >
              " . nl2br(hsc($col_def['title'])) . "
              " . ('' == $col_def['unit'] ? '' : '[' . hsc($col_def['unit']) . ']') . "
              <i class='fas fa-chevron-down order_desc'></i>
              <i class='fas fa-chevron-up order_asc'></i>
            </div>
          ";
        }

        // koniec headeru
        $html .= '  </div>';
      }

      // filtrovaci riadok

      if ($params['showColumnsFilter']) {
        $html .= "<div class='Row ColumnFilters'>";

        foreach ($this->columns as $col_name => $col_def) {
          $filter_input = "";

          switch ($col_def['type']) {
            case 'varchar':
            case 'text':
            case 'password':
            case 'lookup':
            case 'color':
            case 'date':
            case 'datetime':
            case 'timestamp':
            case 'time':
            case 'year':
              $input_type = 'text';
              break;
            case 'float':
            case 'int':
              if (_count($col_def['enum_values'])) {
                $input_type = 'select';
                $input_values = $col_def['enum_values'];
              } else {
                $input_type = 'text';
              }
              break;
            case 'enum':
              $input_type = 'select';
              $input_values = explode(',', $col_def['enum_values']);
              break;
            case 'boolean':
              $input_type = 'bool';
              $true_value = 1;
              $false_value = 0;
              break;
            default:
              $input_type = '';
              $filter_input = '';
          }

          if ('text' == $input_type) {
            $filter_input = "
              <input
                type='text'
                class='{$params['uid']}_column_filter'
                data-col-name='{$col_name}'
                id='{$params['uid']}_column_filter_{$col_name}'
                required='required'
                value=\"" . htmlspecialchars((string) $this->columnsFilter[$col_name]) . "\"
                title=' '
                onkeydown='
                  if (event.keyCode == 13) { event.cancelBubble = true; }
                '
                onkeypress='
                  if (event.keyCode == 13) {
                    event.cancelBubble = true;
                    ui_table_set_column_filter(\"{$params['uid']}\");
                  }
                '
                {$col_def['table_filter_attributes']}
                placeholder='🔍'
              >
            ";
          }

          if ('select' == $input_type) {
            $filter_input = "
              <select
                class='{$params['uid']}_column_filter'
                data-col-name='{$col_name}'
                id='{$params['uid']}_column_filter_{$col_name}'
                title=' '
                required='required'
                onchange=' ui_table_set_column_filter(\"{$params['uid']}\");'
              >
              <option></option>
            ";

            if (_count($input_values)) {
              foreach ($input_values as $enum_val) {
                $filter_input .= "<option value='{$enum_val}' " . ($this->columnsFilter[$col_name] == $enum_val ? "selected='selected'" : '') . '>' . l($enum_val) . '</option>';
              }
            }

            $filter_input .= '</select>';
          }

          if ('bool' == $input_type) {
            $filter_input = "
              <div
                class='bool_controls " . (is_numeric($this->columnsFilter[$col_name]) ? "filter_active" : "") . "'
              >
                <input type='hidden'
                  class='{$params['uid']}_column_filter'
                  data-col-name='{$col_name}'
                  id='{$params['uid']}_column_filter_{$col_name}'
                  required='required'
                  value='" . ads($this->columnsFilter[$col_name]) . "'
                />

                <i
                  class='fas fa-check-circle " . ($this->columnsFilter[$col_name] == 1 ? "active" : "") . "'
                  style='color:#4caf50'
                  onclick='
                    if ($(\"#{$params['uid']}_column_filter_{$col_name}\").val() == \"$true_value\") {
                      $(\"#{$params['uid']}_column_filter_{$col_name}\").val(\"\");
                    } else {
                      $(\"#{$params['uid']}_column_filter_{$col_name}\").val(\"{$true_value}\");
                    }
                    ui_table_set_column_filter(\"{$params['uid']}\");
                  '
                ></i>
                <i
                  class='fas fa-times-circle " . ($this->columnsFilter[$col_name] == 0 ? "active" : "") . "'
                  style='color:#ff5722'
                  onclick='
                    if ($(\"#{$params['uid']}_column_filter_{$col_name}\").val() == \"{$false_value}\") {
                      $(\"#{$params['uid']}_column_filter_{$col_name}\").val(\"\");
                    } else {
                      $(\"#{$params['uid']}_column_filter_{$col_name}\").val(\"{$false_value}\");
                    }
                    ui_table_set_column_filter(\"{$params['uid']}\");
                  '
                ></i>
              </div>
            ";
          }

          $html .= "
              <div class='Column {$col_def['css_class']} {$input_type}'>
                {$filter_input}
              </div>
            ";
        }

        // koniec filtra
        $html .= '</div>';
      }

      $html .= "</div>"; // adios ui Table Header
      $html .= "<div class='adios ui Table Content " . (_count($this->data) == 0 ? "empty" : "") . "'>";

      // zaznamy tabulky
      if (_count($this->data)) {

        foreach ($this->data as $val) {
          $rowCss = $this->model->tableRowCSSFormatter([
            'table' => $this,
            'row' => $val,
          ]);

          $onclick = $params['onclick'] ?: "
            window_render(
              '" . $this->model->getFullUrlBase(array_merge($params, $val)) . "/' + id + '/Edit'
            )
          ";

          $html .= "
            <div
              class='Row'
              data-id='{$val['id']}'
              data-row-values-base64='" . base64_encode(json_encode($val)) . "'
              style='{$rowCss}'
              onclick=\"
                let _this = $(this);
                _this.closest('.data_tr').css('opacity', 0.5);
                setTimeout(function() {
                  _this.closest('.data_tr').css('opacity', 1);
                }, 300);
                let id = " . (int) $val['id'] . ";

                let base64 = $(this).data('row-values-base64');
                let rowValues = JSON.parse(Base64.decode(base64));

                {$onclick}
              \"
            >
          ";

          foreach ($this->columns as $colName => $colDef) {
            $cellHtml = $this->getCellHtml($colName, $colDef, $val);
            $cellHtml = $this->model->tableCellHTMLFormatter([
              'table' => $this,
              'column' => $colName,
              'row' => $val,
              'html' => $cellHtml,
            ]);

            if ((in_array($colDef['type'], ['int', 'float']) && !is_array($colDef['enum_values']))) {
              $alignClass = 'align_right';
            } else {
              $alignClass = 'align_left';
            }

            $cellStyle = $this->model->tableCellCSSFormatter([
              'table' => $this,
              'column' => $colName,
              'row' => $val,
              'value' => $val[$colName],
            ]);

            $html .= "
              <div class='Column {$colDef['css_class']} {$alignClass}' style='{$cellStyle}'>
                {$cellHtml}
              </div>
            ";
          }

          $html .= '</div>';
        }
      }

      $html .= "</div>"; // adios ui Table Content

      if ($params['showControls']) {
        $html .= "
          <div class='adios ui Table Footer'>
            <div class='Row'>
              <div class='Column count'>
                {$this->allRowsCount} " . $this->translate("items total") . "
              </div>
              <div class='Column paging'>
                " . parent::render('paging') . "
              </div>
              <div class='Column settings'>
                <select
                  id='{$this->params['uid']}_table_count'
                  onchange='ui_table_change_items_per_page(\"{$this->params['uid']}\", this.value);'
                >
                  <option value='10' " . ($this->params['itemsPerPage'] == 10 ? "selected" : "") . ">10</option>
                  <option value='25' " . ($this->params['itemsPerPage'] == 25 ? "selected" : "") . ">25</option>
                  <option value='100' " . ($this->params['itemsPerPage'] == 100 ? "selected" : "") . ">100</option>
                  <option value='500' " . ($this->params['itemsPerPage'] == 500 ? "selected" : "") . ">500</option>
                  <option value='1000' " . ($this->params['itemsPerPage'] == 1000 ? "selected" : "") . ">1000</option>
                </select>

                " . $this->adios->ui->button([
                  'fa_icon' => 'fas fa-sync-alt',
                  'class' => 'btn-light btn-circle btn-sm',
                  'title' => "Refresh",
                  'onclick' => "ui_table_refresh('{$this->params['uid']}');",
                ])->render() . "
              </div>
            </div>
          </div>
        ";
      }
    }

    // koniec obsahu
    if (!$this->params['refresh']) {
      $html .= '</div>';
    }

    if ($params['__IS_WINDOW__']) {
      $html = $this->adios->ui->Window(
        [
          'uid' => "{$this->uid}_window",
          'content' => $html,
          'header' => [
            $this->adios->ui->Button(["text" => $this->translate("Close"), "type" => "close", "onclick" => "ui_form_close('{$this->uid}_window');"]),
          ],
          'title' => " ",
        ]
      )->render();
    } else {
      //
    }

    return \ADIOS\Core\HelperFunctions::minifyHtml($html);
  }
}
