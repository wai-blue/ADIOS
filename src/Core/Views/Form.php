<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\Views;

class Form extends \ADIOS\Core\View
{
  public $model = NULL;
  public array $data = [];
  public array $lookupData = [];

  public $gtp;
  public ?\ADIOS\Core\View $closeButton = NULL;
  public ?\ADIOS\Core\View $copyButton = NULL;
  public ?\ADIOS\Core\View $saveButton = NULL;
  public ?\ADIOS\Core\View $deleteButton = NULL;

  public function __construct(
   object $adios,
   array $params = [],
   ?\ADIOS\Core\View $parentView = NULL
  ) {

    $this->adios = $adios;

    // defaultne parametre

    $params = parent::params_merge([
      'model' => '',
      'table' => '',
      'id' => '-1',
      'title' => '',
      'title_params' => [],
      'formatter' => 'ui_form_formatter',
      'defaultValues' => [],
      'readonly' => false,
      'template' => [],
      'show_save_button' => true,
      'save_button_params' => [],
      'show_close_button' => true,
      'close_button_params' => [],
      'show_delete_button' => true,
      'delete_button_params' => [],
      'show_copy_button' => false,
      'copy_button_params' => [],
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
      exit("UI/Form: Don't know what model to work with.");
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
      $tmpFormTitle = ($params['id'] <= 0 ? $this->model->formTitleForInserting : $this->model->formTitleForEditing);
      if (empty($tmpFormTitle)) {
        $this->params['title'] = "{$this->params['model']}: ".($this->params['id'] == -1
          ? "Nový záznam"
          : "Upraviť záznam č. {$this->params['id']}"
        );
      } else {
        $params['title'] = $tmpFormTitle;
      }
    }

    if (empty($params['save_action'])) {
      $params['save_action'] = $this->model->urlBase."/save";
    }

    if (empty($params['delete_action'])) {
      $params['delete_action'] = $this->model->urlBase."/delete";
    }

    if (empty($params['copy_action'])) {
      $params['copy_action'] = $this->model->urlBase."/copy";
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
    $this->params = $this->model->formParams($this->data, $this->params);

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

    // default button params

    if ($this->params['id'] <= 0) {
      $this->params['show_delete_button'] = FALSE;
      $this->params['show_copy_button'] = FALSE;
    }

    if ($this->params['readonly']) {
      $this->params['show_save_button'] = FALSE;
      $this->params['show_delete_button'] = FALSE;
    }

    if ($this->params['show_save_button']) {
      $this->params['save_button_params']['type'] = ($this->params['id'] <= 0 ? 'add' : 'save');

      if ($this->params['id'] <= 0 && !empty($this->model->formAddButtonText)) {
        $this->params['save_button_params']['text'] = $this->model->formAddButtonText;
      }

      if ($this->params['id'] > 0 && !empty($this->model->formSaveButtonText)) {
        $this->params['save_button_params']['text'] = $this->model->formSaveButtonText;
      }

      if (empty($this->params['save_button_params']['onclick'])) {
        $this->params['save_button_params']['onclick'] = "ui_form_save('{$this->params['uid']}', {}, this);";
      }

      $this->params['save_button_params']['class'] = "btn-save";
      $this->saveButton = $this->addView('Button', $this->params['save_button_params']);
    } else {
      $this->saveButton = NULL;
    }

    if ($this->params['show_delete_button']) {
      $this->params['delete_button_params']['type'] = 'delete';

      if (empty($this->params['delete_button_params']['onclick'])) {
        $this->params['delete_button_params']['onclick'] = "
          _confirm(
            '".$this->translate('You are about to delete the record. Continue?')."',
            {
              'title': '".$this->translate('Delete record confirmation')."',
              'contentClass': 'border-left-danger',
              'confirmButtonClass': 'btn-danger',
              'confirmButtonText': '".$this->translate('Yes, delete the record')."',
              'cancelButtonText': '".$this->translate('Do not delete')."',
            },
            function() { ui_form_delete('{$this->params['uid']}') }
          );
        ";
      }
      $this->deleteButton = $this->addView('Button', $this->params['delete_button_params']);
    }

    if ($this->params['show_copy_button']) {
      $this->params['copy_button_params']['type'] = 'copy';

      if (empty($this->params['copy_button_params']['onclick'])) {
        $this->params['copy_button_params']['onclick'] = "
          _confirm(
            '".$this->translate("Are you sure to delete this record?")."',
            {},
            function() {
              ui_form_copy('{$this->params['uid']}')
            }
          );
        ";
      }

      $this->copyButton = $this->addView('Button', $this->params['copy_button_params']);
    }

    $this->params['close_button_params']['type'] = 'close';

    if (empty($this->params['close_button_params']['onclick'])) {
      $this->params['close_button_params']['onclick'] = "ui_form_close('{$this->params['uid']}');";
    }

    $this->closeButton = $this->addView('Button', $this->params['close_button_params']);


    // if (empty($this->params['windowParams']['uid'])) {
    //   $this->params['windowParams']['uid'] = $this->params['uid'].'_form';
    // }
    // if (!empty($this->params['windowParams']['uid'])) {
    //   $this->params['windowUid'] = $this->params['windowParams']['uid'];
    // }

    if ($this->displayMode == 'desktop') {
      if (is_array($this->params['title_params']['left'])) {
        $this->params['title_params']['left'] = array_merge([$this->closeButton, $this->saveButton], $this->params['title_params']['left']);
      } elseif ('' != $this->params['title_params']['left']) {
        $this->params['title_params']['left'] = [$this->closeButton, $this->saveButton, $this->params['titles']['left']];
      } else {
        $this->params['title_params']['left'] = [$this->closeButton, $this->saveButton];
      }

      if (is_array($this->params['title_params']['right'])) {
        $this->params['title_params']['right'] = array_merge([$this->copyButton, $this->deleteButton], $this->params['title_params']['right']);
      } elseif ('' != $this->params['title_params']['right']) {
        $this->params['title_params']['right'] = array_merge([$this->copyButton, $this->deleteButton], [$this->params['title_params']['right']]);
      } else {
        $this->params['title_params']['right'] = [$this->copyButton, $this->deleteButton];
      }

      if ('' == $this->params['title_params']['center']) {
        $this->params['title_params']['center'] = $this->params['title'];
      }

      $this->add($this->addView('Button', $this->params['title_params']), 'title');
    }

    $this->model->onFormAfterInit($this);
  }

  public function renderItem($item) {
    $html = "";

    if (is_string($item)) {
      $inputHtml = "";

      if (strpos($item, ":LOOKUP:") === FALSE) {

        $title = $this->model->translate($this->params['columns'][$item]['title'] ?? '');
        $description = $this->model->translate($this->params['columns'][$item]['description'] ?? '');

        $inputHtml = $this->Input(
          $item,
          $item,
          $this->data[$item],
          $this->params['columns'][$item] ?? [],
          $this->data,
          $this->params['model']
        );
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
            adios ui Form subrow
            ".($this->params['columns'][$item]['required'] ? "required" : "")."
            ".(empty($this->params['columns'][$item]['pattern']) ? "" : "has_pattern")."
          '
        >
          <div class='input-title'>
            ".hsc($title)."
          </div>
          <div class='input-content'>
            {$inputHtml}
          </div>
          ".(empty($description) ? "" : "
            <div class='input-description'>
              ".hsc($description)."
            </div>
          ")."
        </div>
      ";
    } else if (is_string($item['html'])) {
      $html .= "
        <div class='adios ui Form subrow'>
          {$item['html']}
        </div>
      ";

    } else if (is_string($item['action'])) {
      $html .= "
        <div class='adios ui Form subrow'>
          ".$this->adios->renderAction($item['action'], $item['params'])."
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
        <div class='adios ui Form subrow'>
          ".(empty($item['title']) ? "" : "
            <div class='input-title {$item['class']}'>
              {$item['title']}
            </div>
          ")."
          <div
            class='input-content {$item['class']}'
            style='{$item['style']}'
          >
            {$inputHtml}
          </div>
          ".(empty($item['description']) ? "" : "
            <div class='input-description'>
              ".hsc($item['description'])."
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

    if (!empty($items['action'])) {
      // ak je definovana akcia, generuje akciu s parametrami
      $tmpAction = $items['action'];

      $tmpActionParams = $items['params'];
      $tmpActionParams['form_uid'] = $this->params['uid'];
      $tmpActionParams['form_data'] = $this->data;
      $tmpActionParams['initiating_model'] = $this->params['model'];

      $html = $this->adios->renderAction($tmpAction, $tmpActionParams);
    } else if (is_callable($items['template'])) {
      // template je definovany ako anonymna funkcia
      $html = $items['template']($this->params['columns'], $this);
    } else if (is_string($items)) {
      $html = $items;
    } else {
      $html = "
        <div class='".$this->getCssClassesString()." form-wrapper'>
          <div class='adios ui Form table'>
            <div class='adios ui Form subrow save_error_info' style='display:none'>
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
            <div class='adios ui Form subrow'>
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

  // render
  public function render(string $panel = ''): string
  {
    $window = $this->findParentView('Window');

    if ($window !== NULL) {
      $window->setUid(
        \ADIOS\Core\HelperFunctions::str2uid($this->model->fullName)
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
        $form_content_html = $this->params['template']($this->params['columns'], $this);

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

            $col_html .= $this->addView('Tabs', [
              'padding' => false,
              'tabs' => $tabPages
            ])->render();

          } else if (is_string($col['action'])) {
            $col_html .= $this->adios->renderAction($col['action'], $col['params']);
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
        // FORM_CONTENT_HTML

        $form_content_html = "
          <div class='row'>
            ".join("", $cols_html)."
          </div>
        ";

      }

      $html .= '
        <div
          '.$this->main_params()."
          data-save-action='{$this->params['save_action']}'
          data-delete-action='{$this->params['delete_action']}'
          data-copy-action='{$this->params['copy_action']}'
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
          {$form_content_html}
        </div>
      ";

    }

    if (is_callable($this->params['formatter'])) {
      $html .= $this->params['formatter']('after_html', $this, []);
    }

    // DEPRECATED
    //$this->params['onclose'] = $this->params['form_onclose'].$this->params['onclose'];

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
      $window->setTitle($this->model->translate($this->params['title']));
      $window->setHeaderLeft([
        $this->saveButton,
        $this->copyButton,
        $this->deleteButton
      ]);
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
    return $this->addView('Input',
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
