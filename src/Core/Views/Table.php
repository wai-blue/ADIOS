<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\Views;

use ADIOS\Core\DB\Query as Q;
use ADIOS\Core\HelperFunctions;

class Table extends \ADIOS\Core\View
{

  var $model = NULL;

  var $columns = [];
  var $columnsFilter = [];

  var $data = [];
  var array $search = [];

  private $allRowsCount = 0;

  private int $pagesCount = 0;
  private int $pageButtonsCount = 0;

  private \ADIOS\Core\View $paging;

  /**
   * __construct
   *
   * @param mixed $adios
   * @param mixed $params
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

    // default view parameters
    // 2023-08-27 columnsOrder renamed to columnsDisplayOrder
    // 2023-08-27 allow_order_modification renamed to allowOrderModification

    $params = parent::params_merge([
      'model' => '',
      'uid' => '',
      'title' => '',
      'tag' => '',

      'where' => '',
      'having' => '',
      'orderBy' => '',

      'page' => 1,
      'itemsPerPage' => 25,

      'columnsDisplayOrder' => [],
      'showColumnTitles' => true,
      'showColumnsFilter' => true,
      'allowOrderModification' => true,

      'refreshAction' => 'UI/Table',
      'onclick' => '',

      'showTitle' => true,
      'showPaging' => true,
      'showControls' => true,
      'showAddButton' => true,
      'showPrintButton' => true,
      'showSearchButton' => true,
      'showExportCsvButton' => true,
      'showImportCsvButton' => false,

      /*
        rowButtons, TO BE IMPLEMENTED: vyvstalo ako nova funkcionalita pocas telefonatu s JG 31.8.
        Priklad:
          'rowButtons' => [
            [
              'text' => 'Activate',
              'onclick' => 'console.log(row);', // `row` obsahuje udaje zobrazovaneho riadku
              'href' => '', // nepovinne, ak nie je zadane, pouzije sa javascript:void()
              'target' => '', // nepovinne
              'cssClass' => 'btn-danger', // nepovinne
              'cssStyle' => 'color:var(--indigo);', // nepovinne
            ],
            [
              'text' => 'Open external',
              'onclick' => '... any javascript code ...',
              'cssClass' => 'btn-info'
            ]
          ],
      */
      'rowButtons' => [
        [
          'text' => 'Activate',
          'onclick' => 'alert(window);',
          'cssStyle' => 'color:var(--indigo);'
        ],
        [
          'text' => 'Open external',
          'onclick' => '... any javascript code ...',
          'cssClass' => 'btn-info'
        ]
      ],

      'buttons' => [],

      'form_data' => [],

      'readonly' => false
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

    if ((bool)$params['reset']) {
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

    $this->params = $this->model->tableParams($this->params, $this);

    $this->columns = $this->getColumns();

    $this->params['page'] = (int)$this->params['page'];
    $this->params['itemsPerPage'] = (int)$this->params['itemsPerPage'];

    if (_count($this->params['columnsDisplayOrder'])) {
      $tmp_columns = [];
      foreach ($this->params['columnsDisplayOrder'] as $col_name) {
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
      $this->columns[$this->params['foreignKey']]['show_column'] = FALSE; // 2023-06-27 Deprecated
      $this->columns[$this->params['foreignKey']]['showColumn'] = FALSE;
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
      $tmpParentFormId = (int)($this->params['form_data']['id'] ?? 0);

      if (!empty($this->params['foreignKey'])) {
        $fkColumnName = $this->params['foreignKey'];
        $fkColumnDefinition = $this->columns[$fkColumnName] ?? NULL;
        if ($fkColumnDefinition !== NULL) {
          $tmpModel = $this->adios->getModel($fkColumnDefinition['model']);
          $tmpUrl = $tmpModel->urlBase . "/" . $tmpParentFormId . "/" . $tmpUrl;
        }

        $tmpUrl = str_replace("{{ {$this->params['foreignKey']} }}", $tmpParentFormId, $tmpUrl);
      }

      $this->params['buttons']['add']['onclick'] = "
        window_render(
          '" . $tmpUrl . "/add'
        )
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

    $this->pagesCount = ceil($this->allRowsCount / $this->params['itemsPerPage']);
    $this->pageButtonsCount = 4;

    $this->params['showAddButton'] = (empty($this->params['buttons']['add']['onclick']) ? FALSE : $this->params['showAddButton']);

    if (empty($this->params['buttons']['add']['type'])) {
      $this->params['buttons']['add']['type'] = 'add';
    }

    if ($this->model->addButtonText != null) {
      $this->params['buttons']['add']['text'] = $this->model->addButtonText;
    }
  }

  protected function getColumns(): array
  {
    $columns = $this->model->columns();

    if (!empty($this->params['columns'])) {
      foreach ($this->params['columns'] ?? [] as $columnName => $columnParams) {
        $columns[$columnName] = array_merge(
          (array)$columns[$columnName],
          (array)$columnParams
        );
      }

      $columnsOrder = array_merge(
        array_keys($columns),
        array_keys($this->params['columns'])
      );

      array_multisort(array_flip($columnsOrder), $columns);
    }

    return $columns;
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
        && (int)$this->params['form_data']['id'] > 0
      ) {
        $fkColumnName = $this->params['foreignKey'];
        $fkColumnDefinition = $this->columns[$fkColumnName] ?? NULL;
        if ($fkColumnDefinition !== NULL) {
          $tmpModel = $this->adios->getModel($fkColumnDefinition['model']);
          $whereRaw .= "
            and
              `lookup_{$tmpModel->getFullTableSqlName()}_{$fkColumnName}`.`id`
              = " . ((int)$this->params['form_data']['id']);
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

    if (!empty($this->params['fulltext'])) {
      $havingFulltext = [
        'logic' => Q::logicOr,
        'statements' => [],
      ];
      foreach ($this->model->columns() as $modelColumn => $modelColumnParams) {
        if (isset($modelColumnParams['model'])) {
          $havingFulltext['statements'][] = [Q::having, $modelColumn . ':LOOKUP', Q::like, $this->params['fulltext']];
        } else if (in_array($modelColumnParams['type'], ['varchar', 'text'])) {
          $havingFulltext['statements'][] = [Q::having, $modelColumn, Q::like, $this->params['fulltext']];
        }
      }
      $having[] = $havingFulltext;
    }

    // query
    $query = $db->select($this->model, [Q::countRows])
      ->columns([Q::allColumnsWithLookups])
      ->where($where)
      ->whereRaw($whereRaw)
      ->having($having)
      ->order($orderBy);

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
   * getCellCsv
   *
   * @param mixed $columnName
   * @param mixed $columnDefinition
   * @param mixed $rowValues
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
   * @param mixed $panel
   * @return void
   */
  public function render(string $panel = ''): string
  {
    $params = $this->params;

    $html = "";
    $this->addCssClass('Container');

    if (!in_array("UI/Form", $this->adios->actionStack)) {
      $this->addCssClass('shadow');
    }

    if (!$this->params['__IS_WINDOW__']) {
      $this->addCssClass('desktop');
    }


    $this->paging = $this->addView();

    if ($this->params['showPaging']) {
      $this->paging->addView('Button', [
        'faIcon' => 'fas fa-angle-double-left',
        'class' => 'btn-light btn-circle btn-sm',
        'onclick' => "ui_table_show_page('{$this->params['uid']}', '1'); ",
        'disabled' => (1 == $this->params['page'] ? true : false)
      ]);

      $this->paging->addView('Button', [
        'faIcon' => 'fas fa-angle-left',
        'class' => 'btn-light btn-circle btn-sm',
        'onclick' => "ui_table_show_page('{$this->params['uid']}', '" . ($this->params['page'] - 1) . "'); ",
        'disabled' => (1 == $this->params['page'] ? true : false)
      ]);

      for ($i = 1; $i <= $this->pagesCount; ++$i) {
        if ($i == $this->params['page']) {
          $this->paging->addView('Html', ["html" => "
              <input
                type='text'
                value='{$this->params['page']}'
                class='paging_input'
                id='{$this->params['uid']}_paging_bottom_input'
                onchange=\"ui_table_show_page('{$this->params['uid']}', this.value);\"
                onkeypress=\"if (event.keyCode == 13) { ui_table_show_page('{$this->params['uid']}', this.value); } \"
                onclick='this.select();' />
              <script>
              draggable_int_input(
                '{$this->params['uid']}_paging_bottom_input',
                { min_val: 1, max_val: {$this->pagesCount} }
              )
            </script>
          "]);
        } elseif (
          abs($this->params['page'] - $i) <= ($this->pageButtonsCount / 2)
          || (
            $this->params['page'] <= ($this->pageButtonsCount / 2)
            && $i <= ($this->pageButtonsCount + 1)
          )
          || (
            $this->pagesCount - $this->params['page'] <= $this->pageButtonsCount / 2
            && $i >= $this->pagesCount - $this->pageButtonsCount
          )
        ) {
          $this->paging->addView('Button', [
            'text' => $i,
            'class' => 'pages',
            'onclick' => "ui_table_show_page('{$this->params['uid']}', '{$i}');",
            'show_border' => FALSE
          ]);
        }
      }

      $this->paging->addView('Button', [
        'faIcon' => 'fas fa-angle-right',
        'class' => 'btn-light btn-circle btn-sm',
        'onclick' => "ui_table_show_page('{$this->params['uid']}', '" . ($this->params['page'] + 1) . "'); ",
        'disabled' => ($this->params['page'] == $this->pagesCount || 0 == $this->allRowsCount ? true : false)
      ]);
      $this->paging->addView('Button', [
        'faIcon' => 'fas fa-angle-double-right',
        'class' => 'btn-light btn-circle btn-sm',
        'onclick' => "ui_table_show_page('{$this->params['uid']}', '" . ($this->pagesCount) . "'); ",
        'disabled' => ($this->params['page'] == $this->pagesCount || 0 == $this->allRowsCount ? true : false)
      ]);
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
          $searchAction = $this->model->searchAction ?? $this->model->getFullUrlBase($params) . "/search";

          $moreActionsButtonItems[] = [
            "faIcon" => "fas fa-search",
            "text" => $this->translate("Advanced search"),
            "onclick" => "
              window_render(
                '{$searchAction}',
                {},
                function(res) {
                  ui_table_refresh_by_model('{$this->params['model']}');
                }
              );
            ",
          ];
        }

        if ($this->params['showExportCsvButton']) {
          $exportCsvAction = $this->model->exportCsvAction ?? $this->model->getFullUrlBase($params) . "/Export/CSV";

          $moreActionsButtonItems[] = [
            "faIcon" => "fas fa-file-export",
            "text" => $this->translate("Export to CSV"),
            "onclick" => "
              let tmpTableParams = Base64.encode(JSON.stringify(ui_table_params['{$this->uid}']));
              window_popup(
                '{$exportCsvAction}',
                {tableParams: tmpTableParams},
                {'type': 'POST'}
              );
            ",
          ];
        }

        if ($this->params['showPrintButton']) {
          $printButtonAction = $this->model->printButtonAction ?? "UI/Table/PrintPdf";

          $moreActionsButtonItems[] = [
            "faIcon" => "fas fa-print",
            "text" => $this->translate('Print'),
            "onclick" => "
              let tmpTableParams = Base64.encode(JSON.stringify(ui_table_params['{$this->uid}']));
              _ajax_read(
                '{$printButtonAction}',
                {
                  modelParams: '" . base64_encode(json_encode($this->params)) . "',
                  tableParams: tmpTableParams,
                  orderBy: ui_table_order_by
                },
                (res) => {
                  const downloadLink = document.createElement('a');
                  downloadLink.href = 'data:application/octet-stream;base64,' + res;
                  downloadLink.download = new Date().toLocaleDateString('en-UK') + '_{$this->params['table']}.pdf';
                  downloadLink.click();
                }
              )
            ",
          ];
        }

        if ($this->params['showImportCsvButton']) {
          $importCsvAction = $this->model->importCsvAction ?? $this->model->getFullUrlBase($params) . "/Import/CSV";

          $moreActionsButtonItems[] = [
            "faIcon" => "fas fa-file-import",
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

        $titleLeftContent = [];
        $titleRightContent = [];

        if ($this->params['showAddButton']) {
          $titleLeftContent[] = $this->addView('Button', $this->params['buttons']['add']);
        }

        // fulltext search
        $titleRightContent[] = new Html($this->adios, [
          'html' => "
            <input
              type='input'
              id='{$this->uid}_fulltext'
              class='form-control p-2'
              style='width:15em'
              onkeypress='
                if (event.keyCode == 13) {
                  event.cancelBubble = true;
                  ui_table_set_fulltext_search(\"{$params['uid']}\");
                }
              '
              placeholder='" . $this->translate("Press Enter to search...") . "'
              value='" . ads($this->params['fulltext']) . "'
            />
          ",
        ]);

        if (_count($moreActionsButtonItems)) {
          $titleRightContent[] = $this->addView('Button', [
            "faIcon" => "fas fa-ellipsis-v",
            "title" => "",
            "onclick" => "window_render('{$searchAction}');",
            "dropdown" => $moreActionsButtonItems,
            "class" => "btn-light",
          ]);
        }


        if (
          !empty($titleButtons)
          || !empty($this->params['title'])
        ) {
          $html .= $this->addView('Title')
            ->setLeftContent($titleLeftContent)
            ->setRightContent($titleRightContent)
            ->setTitle($this->model->translate($this->params['title']))
            ->render();
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
                " . $this->addView('Button', [
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
          data-model='" . ads(strtolower($this->params['model'])) . "'
          data-refresh-action='" . ads($this->params['refreshAction']) . "'
          data-refresh-params='" . (empty($this->params['uid'])
          ? json_encode($this->params['_REQUEST'])
          : json_encode(['uid' => $this->params['uid']])
        ) . "'
          data-action='" . ads($this->adios->action) . "'
          data-page='" . (int)$this->params['page'] . "'
          data-items-per-page='" . (int)$this->params['items-per-page'] . "'
          data-is-ajax='" . ($this->adios->isAjax() ? "1" : "0") . "'
          data-is-in-form='" . (in_array("UI/Form", $this->adios->actionStack) ? "1" : "0") . "'
        >
      ";
    }

    if (_count($this->columns)) {
      foreach ($this->columns as $col_name => $col_def) {
        if (
          !$col_def['show_column'] // 2023-06-27 Deprecated
          && !$col_def['showColumn']
        ) {
          unset($this->columns[$col_name]);
        }
      }

      $ordering = explode(' ', $this->params['orderBy']);

      $html .= "<div class='adios ui Table Header'>";

      // title riadok - nazvy stlpcov

      if ($params['showColumnTitles']) {
        $html .= "<div class='Row ColumnNames'>";

        foreach ($this->columns as $col_name => $col_def) {
          if ($params['allowOrderModification']) {
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
              " . ($params['allowOrderModification'] ? "
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
              " . nl2br(hsc($this->model->translate($col_def['title']))) . "
              " . ('' == $col_def['unit'] ? '' : '[' . hsc($col_def['unit']) . ']') . "
              <i class='fas fa-chevron-down order_desc'></i>
              <i class='fas fa-chevron-up order_asc'></i>
            </div>
          ";
        }

        if (_count($this->params['rowButtons'])) {
          $html .= "<div class='Column'>.</div>";
        }

        // koniec headeru
        $html .= '</div>';
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
            case 'decimal':
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
                value=\"" . htmlspecialchars((string)$this->columnsFilter[$col_name]) . "\"
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

        if (_count($this->params['rowButtons'])) {
          $html .= "<div class='Column'>.</div>";
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

          $rowOnclick = $params['onclick'] ?: "
            window_render(
              '" . $this->model->getFullUrlBase(array_merge($params, $val)) . "/' + id + '/edit'
            );
            $(this).closest('.Content').find('.Row').removeClass('highlighted');
            $(this).closest('.Row').addClass('highlighted');
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
                let id = " . (int)$val['id'] . ";

                let base64 = $(this).data('row-values-base64');
                let rowValues = JSON.parse(Base64.decode(base64));

                {$rowOnclick}
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

            if ((in_array($colDef['type'], ['int', 'float', 'decimal']) && !is_array($colDef['enum_values']))) {
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

          if (_count($this->params['rowButtons'])) {
            $html .= "<div class='Column'>";
            foreach ($this->params['rowButtons'] as $rowButton) {
              $html .= "
                <a
                  href='" . ($rowButton['href'] ?? "javascript:void(0)") . "'
                  onclick='
                    event.cancelBubble = true;
                    let rowValuesBase64 = $(this).closest(\".Row\").data(\"row-values-base64\");
                    let row = JSON.parse(Base64.decode(rowValuesBase64));

                    " . ($rowButton['onclick'] ?? "") . "
                  '
                  ".(empty($rowButton['cssStyle']) ? "" : "style='" . ads($rowButton['cssStyle']) . "'")."
                  ".(empty($rowButton['cssClass']) ? "" : "class='" . ads($rowButton['cssClass']) . "'")."
                  ".(empty($rowButton['target']) ? "" : "target='" . ads($rowButton['target']) . "'")."
                >" . hsc($rowButton['text']) . "</a>
              ";
            }
            $html .= "</div>";
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
                " . $this->paging->render() . "
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

                " . $this->addView('Button', [
            'faIcon' => 'fas fa-sync-alt',
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

    // if ($params['__IS_WINDOW__']) {
    //   $html = $this->adios->view->Window(
    //     [
    //       'uid' => "{$this->uid}_window",
    //       'content' => $html,
    //       'header' => [
    //         $this->addView('Button', ["text" => $this->translate("Close"), "type" => "close", "onclick" => "ui_form_close('{$this->uid}_window');"]),
    //       ],
    //       'title' => " ",
    //     ]
    //   )->render();
    // } else {
    //   //
    // }

    return HelperFunctions::minifyHtml($html);
  }

  /**
   * getCellHtml
   *
   * @param mixed $columnName
   * @param mixed $columnDefinition
   * @param mixed $rowValues
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
}
