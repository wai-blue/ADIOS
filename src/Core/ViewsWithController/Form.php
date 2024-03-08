<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController;

class Form extends \ADIOS\Core\ViewWithController
{
  public $model = NULL;
  public array $data = [];
  public array $lookupData = [];

  public $gtp;
  public ?\ADIOS\Core\ViewWithController $closeButton = NULL;
  public ?\ADIOS\Core\ViewWithController $copyButton = NULL;
  public ?\ADIOS\Core\ViewWithController $saveButton = NULL;
  public ?\ADIOS\Core\ViewWithController $deleteButton = NULL;
  public ?\ADIOS\Core\ViewWithController $printButton = NULL;

  public bool $print = FALSE;

  public function __construct(
   object $adios,
   array $params = [],
   ?\ADIOS\Core\ViewWithController $parentView = NULL
  ) {

    $this->adios = $adios;

    // defaultne parametre

    $params = array_replace_recursive([
      'model' => '',
      'table' => '',
      'id' => '-1',
      'title' => '',
      'titleParams' => [],
      'formatter' => 'ui_form_formatter',
      'defaultValues' => [],
      'readonly' => false,
      'template' => [],
      'showSaveButton' => true,
      'saveButtonParams' => [],
      'show_close_button' => true,
      'close_button_params' => [],
      'showDeleteButton' => true,
      'deleteButtonParams' => [],
      'showCopyButton' => false,
      'copyButtonParams' => [],
      'showPrintButton' => true,
      'printButtonParams' => [],
      'formType' => 'window',
      'windowParams' => [],
      'width' => 700,
      'height' => 0,
      'onclose' => '',
      'reopenAfterSave' => FALSE,
      'onload' => '',
      'javascript' => '',
      'displayMode' => 'window'
    ], $params);

    // nacitanie udajov
    if (empty($params['model'])) {
      exit("Components/Form: Don't know what model to work with.");
      return;
    }

    $this->model = $this->adios->getModel($params['model']);
    $this->data = (array) $this->model->getById((int) $params['id']);

    foreach ($this->model->columns() as $colName => $colDefinition) {
      if (!isset($this->data[$colName])) continue;

      if ($colDefinition['type'] == 'lookup') {
        $lookupModel = $this->adios->getModel($colDefinition['model']);
        $this->lookupData[$colName] = $lookupModel->getById($this->data[$colName]);
      }
    }

    $params['table'] = $this->model->getFullTableSqlName();

    if (empty($params['uid'])) {
      $params['uid'] = $this->adios->getUid("{$params['model']}_{$params['id']}")."_".rand(1000, 9999);
    }

    if (empty($params['title'])) {
      // $tmpFormTitle = ($params['id'] <= 0 ? $this->model->formTitleForInserting : $this->model->formTitleForEditing);
      $tmpFormTitle = '';
      if (empty($tmpFormTitle)) {
        if ($params['id'] == -1) {
          $params['title'] = $this->params['model'] . ': ' . $this->translate('Nový záznam');
        } else {
          $params['title'] = $this->model->getLookupSqlValueById($params['id']);
        }
      } else {
        $params['title'] = $tmpFormTitle;
      }
    }

    if (empty($params['saveController'])) {
      $params['saveController'] = $this->model->urlBase."/save";
    }

    if (empty($params['deleteController'])) {
      $params['deleteController'] = $this->model->urlBase."/delete";
    }

    if (empty($params['copyController'])) {
      $params['copyController'] = $this->model->urlBase."/copy";
    }

    // call the parent constructor
    // after this, the $this->params must be used instead of $params.
    parent::__construct($adios, $params, $parentView);

    $this->params['id'] = (int) $this->params['id'];
    $this->params['columns'] = parent::params_merge(
      $this->adios->db->tables[$this->params['table']],
      $this->params['columns'] ?? []
    );

    $this->model->onFormBeforeInit($this);
    $this->params = $this->model->onFormParams($this, $this->params);

    unset($this->params['columns']['id']);

    // default values

    // 2023-01-24 Dusan: default_values ako string je uz deprecated
    // if (is_string($this->params['default_values'])) {
    //   $this->params['default_values'] = @json_decode($this->params['default_values'], TRUE);
    // }

    if (
      isset($this->params['default_values'])
      && !isset($this->params['defaultValues'])
    ) {
      $this->params['defaultValues'] = $this->params['default_values'];
    }

    if (_count($this->params['defaultValues']) && $this->params['id'] <= 0) {
      foreach ($this->params['defaultValues'] as $col_name => $def_value) {
        $this->data[$col_name] = $def_value;
      }
    }

    foreach ($this->data as $key => $value) {
      if (is_string($value)) {
        $this->params['title'] = str_replace("{{ {$key} }}", hsc($value), $this->params['title']);
      }
    }

    // default buttons


    if ($this->params['id'] <= 0) {
      $this->params['showDeleteButton'] = FALSE;
      $this->params['showCopyButton'] = FALSE;
    }

    if ($this->params['readonly']) {
      $this->params['showSaveButton'] = FALSE;
      $this->params['showDeleteButton'] = FALSE;
    }

    // save button
    if ($this->params['showSaveButton']) {
      $this->params['saveButtonParams']['type'] = ($this->params['id'] <= 0 ? 'add' : 'save');

      if ($this->params['id'] <= 0 && !empty($this->model->formAddButtonText)) {
        $this->params['saveButtonParams']['text'] = $this->model->formAddButtonText;
      }

      if ($this->params['id'] > 0 && !empty($this->model->formSaveButtonText)) {
        $this->params['saveButtonParams']['text'] = $this->model->formSaveButtonText;
      }

      if (empty($this->params['saveButtonParams']['onclick'])) {
        $this->params['saveButtonParams']['onclick'] = "ADIOS.views.Form.save('{$this->params['uid']}', {}, this);";
      }

      $this->params['saveButtonParams']['class'] = "btn-save";
      $this->saveButton = $this->create('\\ADIOS\\Core\\ViewsWithController\\Button', $this->params['saveButtonParams']);
    }

    // delete button
    if ($this->params['showDeleteButton']) {
      $this->params['deleteButtonParams']['type'] = 'delete';
      $this->params['deleteButtonParams']['class'] = 'ml-2';

      if (empty($this->params['deleteButtonParams']['onclick'])) {
        $this->params['deleteButtonParams']['onclick'] = "
          _confirm(
            '".$this->translate('You are about to delete the record. Continue?')."',
            {
              'title': '".$this->translate('Delete record confirmation')."',
              'contentClass': 'border-left-danger',
              'confirmButtonClass': 'btn-danger',
              'confirmButtonText': '".$this->translate('Yes, delete the record')."',
              'cancelButtonText': '".$this->translate('Do not delete')."',
            },
            function() { ADIOS.views.Form.delete('{$this->params['uid']}') }
          );
        ";
      }
      $this->deleteButton = $this->create('\\ADIOS\\Core\\ViewsWithController\\Button', $this->params['deleteButtonParams']);
    }

    // copy button
    if ($this->params['showCopyButton']) {
      $this->params['copyButtonParams']['type'] = 'copy';
      $this->params['copyButtonParams']['class'] = 'ml-2';

      if (empty($this->params['copyButtonParams']['onclick'])) {
        $this->params['copyButtonParams']['onclick'] = "
          _confirm(
            '".$this->translate("Are you sure to copy this record?")."',
            {},
            function() {
              ADIOS.views.Form.copy('{$this->params['uid']}')
            }
          );
        ";
      }

      $this->copyButton = $this->create('\\ADIOS\\Core\\ViewsWithController\\Button', $this->params['copyButtonParams']);
    }

    // print button
    if ($this->params['showPrintButton']) {
      $this->params['printButtonParams']['type'] = 'print';
      $this->params['printButtonParams']['class'] = 'ml-2';

      if (empty($this->params['printButtonParams']['onclick'])) {
        $this->params['printButtonParams']['onclick'] = "ADIOS.views.Form.print('{$this->params['uid']}');";
      }

      $this->printButton = $this->create('\\ADIOS\\Core\\ViewsWithController\\Button', $this->params['printButtonParams']);
    }

    // close button
    $this->params['closeButtonParams']['type'] = 'close';
    
    if (empty($this->params['closeButtonParams']['onclick'])) {
      $this->params['closeButtonParams']['onclick'] = "ADIOS.views.Form.close('{$this->params['uid']}');";
    }

    $this->closeButton = $this->create('\\ADIOS\\Core\\ViewsWithController\\Button', $this->params['closeButtonParams']);

    // onAfterInit
    $this->model->onFormAfterInit($this);
  }

  public function renderItem($item) {
    $html = "";

    if (is_string($item)) {
      $inputHtml = "";

      if (strpos($item, ":LOOKUP:") === FALSE) {

        $allItems = explode("+", $item);
        $firstItem = trim(reset($allItems));

        $title = $this->model->translate($this->params['columns'][$firstItem]['title'] ?? '');
        $description = $this->model->translate($this->params['columns'][$firstItem]['description'] ?? '');

        $inputHtml = "";

        foreach ($allItems as $tmpItem) {
          $tmpItem = trim($tmpItem);
          $tmpItemColDefinition = $this->params['columns'][$tmpItem] ?? [];

          if (count($allItems) > 1) {
            $tmpItemColDefinition['cssClass'] .= ' inline';
          }

          $inputHtml .= $this->Input(
            $tmpItem,
            $tmpItem,
            $this->data[$tmpItem],
            $tmpItemColDefinition,
            $this->data,
            $this->params['model']
          );
        }
      } else {
        [$columnName, $lookupColumnName] = explode(":LOOKUP:", $item);
        $lookupModelName = $this->params['columns'][$columnName]['model'] ?? '';
        if (!empty($lookupModelName)) {
          $lookupModel = $this->adios->getModel($lookupModelName);
          $lookupColumn = $lookupModel->columns()[$lookupColumnName] ?? [];

          $title = 
            $this->model->translate($this->params['columns'][$columnName]['title'] ?? '')
            . ' » '
            . $this->model->translate($lookupColumn['title'] ?? '')
          ;
          $description = $this->model->translate($lookupColumn['description'] ?? '');

          $inputHtml = $this->Input(
            $item,
            $lookupColumnName,
            $this->lookupData[$columnName][$lookupColumnName],
            $lookupColumn,
            $this->lookupData[$columnName],
            $this->params['model']
          );
        }
      }

      $html .= "
        <div
          class='
            adios ui Form item
            ".($this->params['columns'][$item]['required'] ? "required" : "")."
            ".(empty($this->params['columns'][$item]['pattern']) ? "" : "has_pattern")."
          '
        >
          <div class='input-title'>
            ".hsc($title)."
          </div>
          ".(empty($description) ? "" : "
            <div class='input-description'>
              ".hsc($description)."
            </div>
          ")."
          <div class='input-content'>
            {$inputHtml}
          </div>
        </div>
      ";
    } else if (is_string($item['html'])) {
      $html .= "
        <div class='adios ui Form item'>
          {$item['html']}
        </div>
      ";

    } else if (is_string($item['controller'])) {
      $html .= "
        <div class='adios ui Form item'>
          ".$this->adios->render($item['controller'], $item['params'])."
        </div>
      ";

    } else if (is_string($item['view'])) {

      $tmpView = $item['view'];
      $tmpViewParams = $this->_renderItemsRecursively($item['params'] ?? []);
      $tmpViewParams['form_uid'] = $this->params['uid'];
      $tmpViewParams['form_data'] = $this->data;
      $tmpViewParams['initiating_model'] = $this->params['model'];

      $html .= "
        <div class='adios ui Form item'>
          ".$this->adios->view->create($tmpView, $tmpViewParams)->render()."
        </div>
      ";

    } else if (isset($item['input'])) {
      $inputHtml = "";

      if (is_string($item['input'])) {
        $inputHtml = $item['input'];
      } else if (is_array($item['input'])) {
        $inputClass = $item['input']['class'];

        $inputParams = $item['input']['params'];
        $inputParams['form_uid'] = $this->params['uid'];
        $inputParams['form_data'] = $this->data;
        $inputParams['initiating_model'] = $this->params['model'];

        $inputHtml = (new $inputClass(
          $this->adios,
          "{$this->params['uid']}_{$item['input']['uid']}",
          $inputParams
        ))->render();
      }

      $html .= "
        <div class='adios ui Form item'>
          ".(empty($item['title']) ? "" : "
            <div class='input-title {$item['class']}'>
              {$item['title']}
            </div>
          ")."
          ".(empty($item['description']) ? "" : "
            <div class='input-description'>
              ".hsc($item['description'])."
            </div>
          <div
            class='input-content {$item['class']}'
            style='{$item['style']}'
          >
            {$inputHtml}
          </div>
          ")."
        </div>
      ";
    }

    return $html;
  }

  // renderItems
  function renderItems($items) {
    $html = "";

    if (!empty($items['controller'])) {
      // ak je definovana akcia, generuje akciu s parametrami
      $tmpController = $items['controller'];

      $tmpControllerParams = $items['params'];
      $tmpControllerParams['form_uid'] = $this->params['uid'];
      $tmpControllerParams['form_data'] = $this->data;
      $tmpControllerParams['initiating_model'] = $this->params['model'];

      $html = $this->adios->render($tmpController, $tmpControllerParams);
    } else if (is_callable($items['template'])) {
      // template je definovany ako anonymna funkcia
      $html = $items['template']($this->params['columns'], $this);
    } else if (is_string($items)) {
      $html = $items;
    } else {
      $html = "
        <div class='".$this->getCssClassesString()." form-wrapper'>
          <div class='adios ui Form table'>
            <div class='adios ui Form item save_error_info' style='display:none'>
              ".$this->translate("Some of the required fields are empty.")."
            </div>
      ";
      foreach ($items as $item) {
        if (!empty($item['view'])) {
          // ak je definovane view, generuje view s parametrami
          $tmpView = $item['view'];

          $tmpViewParams = $item['params'];
          $tmpViewParams['form_uid'] = $this->params['uid'];
          $tmpViewParams['form_data'] = $this->data;
          $tmpViewParams['initiating_model'] = $this->params['model'];

          $html .= $this->adios->view->create($tmpView, $tmpViewParams)->render();
        } else if (isset($item['group']['title'])) {
          $html .= "
            <div class='adios ui Form item'>
              <div class='group-title'>
                ".hsc($item['group']['title'])."
              </div>
            </div>
          ";
          $html .= $this->renderItems($item['group']['items']);
        } else {
          $html .= $this->renderItem($item);
        }
      }
      $html .= "
          </div>
        </div>
      ";
    }

    return $html;
  }

  private function _renderItemsRecursively(array $array): array
  {
    if (isset($array['item'])) {
      $array['html'] = $this->renderItem($array['item']);
      unset($array['item']);
    }

    foreach ($array as $key => $item) {
      if (is_array($item)) {
        $array[$key] = $this->_renderItemsRecursively($array[$key]);
      }
    }

    return $array;
  }


  // render
  public function render(string $panel = ''): string
  {
    $window = $this->findParentView('\\ADIOS\\Core\\ViewsWithController\\Window');

    if ($window !== NULL) {
      $window->setUid(
        \ADIOS\Core\Helper::str2uid($this->model->fullName)
        . ($this->params['id'] <= 0 ? '_add' : '_edit')
      );
    }
    
    if (!_count($this->params['columns'])) {
      $this->adios->console->error("No columns provided: {$this->params['model']}");
    }

    $html = "";

    if (is_callable($this->params['formatter'])) {
      $html .= $this->params['formatter']('before_html', $this, []);
    }

    foreach ($this->params['columns'] as $col_name => $col_def) {

      $this->params['columns'][$col_name]['row_id'] = $this->data['id'];

      if ('table' == $col_def['type']) {
        if ('' != $col_def['child_table']) {
          $this->params['columns'][$col_name]['default_table'] = $col_def['child_table'];
        } else {
          $this->params['columns'][$col_name]['default_table'] = $this->params['table'];
        }
        $this->params['columns'][$col_name]['default_column'] = $col_name;
      }
      if ($this->params['readonly']) {
        $this->params['columns'][$col_name]['readonly'] = true;
      }
      if ($col_def['virtual'] || 'none' == $col_def['type'] || 'virtual' == $col_def['type']) {
        unset($this->params['columns'][$col_name]);
      }
    }

    // params['content']
    if (!empty($this->params['content'])) {
      $tmpView = $this->params['content']['view'];
      $tmpViewParams = $this->_renderItemsRecursively($this->params['content']['params']);

      $contentHtml = $this->adios->view->create($tmpView, $tmpViewParams)->render();
    } else {

      // params['template']
      if (empty($this->params['template'])) {
        $this->params['template'] = [
          "columns" => [
            [
              "items" => array_keys($this->params['columns']),
            ],
          ],
        ];

      }

      if (_count($this->params['columns'])) {

        // renderovanie template

        if (is_callable($this->params['template'])) {

          // cely template definovany ako anonymna funkcia vracajuca HTML
          $contentHtml = $this->params['template']($this->params['columns'], $this);

        } else {

          $cols_html = [];
          $cols_count = count($this->params['template']['columns']);

          if ($cols_count <= 6) {
            $col_class = "col-lg-".round(12 / $cols_count);
          } else {
            $col_class = "col-lg-2";
          }
          foreach ($this->params['template']['columns'] as $col) {

            $col_html = "<div class='col col-sm-12 ".($col["class"] ?? $col_class." pl-0")."'>";

            if (is_string($col)) {
              $col_html .= $col;
            } else if (!empty($col['items'])) {

              $col_html .= $this->renderItems($col['items']);
            } else if (is_array($col['tabs'])) {
              $tabPages = [];

              // kazdy element predstavuje jeden tab vo formulari
              foreach ($col['tabs'] as $tab_name => $items) {
                $tabPages[] = [
                  'title' => $this->model->translate($tab_name),
                  'content' => [ 'html' => $this->renderItems($items) ],
                ];
              }

              $col_html .= $this->addView('\\ADIOS\\Core\\ViewsWithController\\Tabs', [
                'padding' => false,
                'tabs' => $tabPages
              ])->render();

            } else if (is_string($col['controller'])) {
              $col_html .= $this->adios->render($col['controller'], $col['params']);
            } else if (is_string($col['html'])) {
              $col_html .= $col['html'];
            } else if (is_array($col['content'])) {
              foreach ($col['content'] as $item) {
                if (is_string($item)) {
                  $col_html .= $item;
                } else {
                  $col_html .= $item->render();
                }
              }
            }

            $col_html .= "</div>";

            $cols_html[] = $col_html;
          }

          //////////////////////////////
          // contentHtml

          $contentHtml = "
            <div class='row'>
              ".join("", $cols_html)."
            </div>
          ";

        }

      }
    }

    $html .= '
      <div
        '.$this->main_params()."
        data-save-controller='{$this->params['saveController']}'
        data-delete-controller='{$this->params['deleteController']}'
        data-copy-controller='{$this->params['copyController']}'
        data-id='{$this->params['id']}'
        data-model='{$this->params['model']}'
        data-model-url-base='".ads($this->model->getFullUrlBase($this->params))."'
        data-table='{$this->params['table']}'
        data-reopen-after-save='{$this->params['reopenAfterSave']}'
        data-do-not-close='{$this->params['do_not_close']}'
        data-window-uid='".($window === NULL ? "" : $window->uid)."'
        data-form-type='{$this->params['formType']}'
        data-is-ajax='".($this->params['__IS_AJAX__'] || $this->adios->isAjax() ? "1" : "0")."'
        data-is-in-window='".($window === NULL ? "0" : "1")."'
      >
        {$contentHtml}
      </div>
    ";


    if (is_callable($this->params['formatter'])) {
      $html .= $this->params['formatter']('after_html', $this, []);
    }

    $html .= "
      <script>
        ".$this->params['javascript']."

        $(document).ready(function(){
          var uid = '{$this->params['uid']}';
          ".$this->params['onload'].'

        });
      </script>
    ';

    if ($window !== NULL) {
      $window->setCloseButton($this->closeButton);
      $window->setOnclose($this->params['onclose']);
      $window->setTitle($this->model->translate($this->params['title']));
      $window->setHeaderLeft([
        $this->saveButton,
        $this->copyButton,
        $this->deleteButton,
        $this->printButton,
      ]);
      $window->setHeaderRight([
      ]);
    }

    if ($this->displayMode == 'desktop') {
      $this->params['titleParams']['left'] = [$this->closeButton, $this->saveButton];
      $this->params['titleParams']['right'] = [$this->copyButton, $this->deleteButton];

      if ('' == $this->params['titleParams']['center']) {
        $this->params['titleParams']['title'] = $this->params['title'];
      }

      $titleHtml = $this->create('\\ADIOS\\Core\\ViewsWithController\\Title', $this->params['titleParams'])->render();
      $html = $titleHtml.$html;
    }

    return $this->applyDisplayMode((string) $html);
  }

  public function Input(
    $inputId,
    $colName,
    $value,
    $colDefinition,
    $formData = NULL,
    $initiatingModel = NULL
  ) {
    if (!is_array($colDefinition)) $colDefinition = [];
    
    if (empty($colDefinition['onchange'])) {
      $colDefinition['onchange'] = '';
    }

    $colDefinition['onchange'] .= "
      ADIOS.views.Form.change('{$this->params['uid']}', '{$colName}');
    ";

    return $this->addView('\\ADIOS\\Core\\ViewsWithController\\Input',
      array_merge(
        [
          'uid' => $this->params['uid'].'_'.$inputId,
          'form_uid' => $this->params['uid'],
          'form_data' => $formData,
          'initiating_column' => $colName,
          'initiating_model' => $initiatingModel,
          'value' => $value,
        ],
        $colDefinition
      )
    )->render();
  }

}
