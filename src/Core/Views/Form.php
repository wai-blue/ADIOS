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
      'table' => '',
      'id' => '-1',
      'title' => '',
      'formatter' => 'ui_form_formatter',
      'columns_order' => [],
      'defaultValues' => [],
      'readonly' => false,
      'template' => [],
      'template_callback' => '',
      'show_save_button' => true,
      'save_button_params' => [],
      'show_close_button' => true,
      'close_button_params' => [],
      'show_delete_button' => true,
      'delete_button_params' => [],
      'show_copy_button' => false,
      'copy_button_params' => [],
      'append_buttons' => [],
      'form_type' => 'window',
      'window_uid' => '',
      'window_params' => [],
      'show_modal' => false,
      'width' => 700,
      'height' => '',
      'onclose' => '',
      'hide_id_column' => true,
      // 'save_action' => 'UI/Form/Save',
      // 'delete_action' => 'UI/Form/Delete',
      // 'copy_action' => 'UI/Table/Copy',
      'do_not_close' => false, // DEPRECATED, je nahradeny parametrom reopen_after_save
      'reopen_after_save' => $this->adios->getConfig(
        "ui/form/reopen_after_save",
        ((int) $params['id'] > 0 ? TRUE : FALSE)
      ),
      'onbeforesave' => '',
      'onaftersave' => '',
      'onbeforeclose' => '',
      'onafterclose' => '',
      'onbeforedelete' => '',
      'onafterdelete' => '',
      'onbeforecopy' => '',
      'onaftercopy' => '',
      'onload' => '',
      'simple_insert' => false,
      'javascript' => '',
      'windowCssClass' => '',
    ], $params);

    // nacitanie udajov
    if (empty($params['model'])) {
      exit("UI/Form: Don't know what model to work with.");
      return;
    }

    $this->model = $this->adios->getModel($params['model']);
    $this->data = (array) $this->model->getById((int) $params['id']);

    $params['table'] = $this->model->getFullTableSqlName();

    if (empty($params['uid'])) {
      $params['uid'] = $this->adios->getUid("{$params['model']}_{$params['id']}")."_".rand(1000, 9999);
    }

    if (empty($params['title'])) {
      $tmpFormTitle = ($params['id'] <= 0 ? $this->model->formTitleForInserting : $this->model->formTitleForEditing);
      if (empty($tmpFormTitle)){
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
    $this->params['columns'] = $this->adios->db->tables[$this->params['table']];

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

    if ($this->params['show_close_button']) {
      $this->params['close_button_params']['type'] = 'close';

      if (empty($this->params['close_button_params']['onclick'])) {
        $this->params['close_button_params']['onclick'] = "ui_form_close('{$this->params['uid']}');";
      }

      $this->closeButton = $this->addView('Button', $this->params['close_button_params']);
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
      $this->params['delete_button_params']['style'] .= 'float:right;';
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

      $this->params['copy_button_params']['style'] .= 'float:right;';
      $this->copyButton = $this->addView('Button', $this->params['copy_button_params']);
    }

    if (empty($this->params['header'])) {
      $this->params['window']['header'] = [
        $this->closeButton,
        $this->saveButton,
        $this->deleteButton,
        $this->copyButton
      ];
    }

    if ('' == $this->params['window_uid']) {
      $this->params['window_uid'] = $this->params['uid'].'_form_window';
    }
    if ('' != $this->params['window_params']['uid']) {
      $this->params['window_uid'] = $this->params['window_params']['uid'];
    }

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

  // renderRows
  function renderRows($rows) {
    $html = "";

    if (!empty($rows['action'])) {
      // ak je definovana akcia, generuje akciu s parametrami
      $tmpAction = $rows['action'];
      
      $tmpActionParams = $rows['params'];
      $tmpActionParams['form_uid'] = $this->params['uid'];
      $tmpActionParams['form_data'] = $this->data;
      $tmpActionParams['initiating_model'] = $this->params['model'];

      $html = $this->adios->renderAction($tmpAction, $tmpActionParams);
    } else if (is_callable($rows['template'])) {
      // template je definovany ako anonymna funkcia
      $html = $rows['template']($this->params['columns'], $this);
    } else if (is_string($rows)) {
      $html = $rows;
    } else {
      $html = "
        <div class='".$this->getCssClassesString()." form-wrapper'>
          <div class='adios ui Form table'>
            <div class='adios ui Form subrow save_error_info' style='display:none'>
              ".$this->translate("Some of the required fields are empty.")."
            </div>
      ";
      foreach ($rows as $row) {
        if (is_string($row)) {
          $html .= "
            <div
              class='
                adios ui Form subrow
                ".($this->params['columns'][$row]['required'] ? "required" : "")."
                ".(empty($this->params['columns'][$row]['pattern']) ? "" : "has_pattern")."
              '
            >
              <div class='adios ui Form form_title'>
                ".hsc($this->params['columns'][$row]['title'])."
              </div>
              <div class='adios ui Form form_input'>
                ".$this->Input($row, $this->data, $this->params['model'])."
              </div>
              ".(empty($this->params['columns'][$row]['description']) ? "" : "
                <div class='adios ui Form form_description'>
                  ".hsc($this->params['columns'][$row]['description'])."
                </div>
              ")."
            </div>
          ";
        } else if (is_string($row['html'])) {
          $html .= "
            <div class='adios ui Form subrow'>
              {$row['html']}
            </div>
          ";
        } else if (is_string($row['action'])) {
          $html .= "
            <div class='adios ui Form subrow'>
              ".$this->adios->renderAction($row['action'], $row['params'])."
            </div>
          ";
        } else if (isset($row['input'])) {
          $inputHtml = "";

          if (is_string($row['input'])) {
            $inputHtml = $row['input'];
          } else if (is_array($row['input'])) {
            $inputClass = $row['input']['class'];

            $inputParams = $row['input']['params'];
            $inputParams['form_uid'] = $this->params['uid'];
            $inputParams['form_data'] = $this->data;
            $inputParams['initiating_model'] = $this->params['model'];

            $inputHtml = (new $inputClass(
              $this->adios,
              "{$this->params['uid']}_{$row['input']['uid']}",
              $inputParams
            ))->render();
          }
          $html .= "
            <div class='adios ui Form subrow'>
              ".(empty($row['title']) ? "" : "
                <div class='adios ui Form form_title {$row['class']}'>
                  {$row['title']}
                </div>
              ")."
              <div
                class='adios ui Form form_input {$row['class']}'
                style='{$row['style']}'
              >
                {$inputHtml}
              </div>
              ".(empty($row['description']) ? "" : "
                <div class='adios ui Form form_description'>
                  ".hsc($row['description'])."
                </div>
              ")."
            </div>
          ";
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
    
    if (!_count($this->params['columns'])) {
      $this->adios->console->error("No columns provided: {$this->params['model']}");
    }

    $html = "";

    if (is_callable($this->params['formatter'])) {
      $html .= $this->params['formatter']('before_html', $this, []);
    }

    foreach ($this->params['columns'] as $col_name => $col_def) {

      $this->params['columns'][$col_name]['row_id'] = $this->data['id'];

      // andy test - mozno sposobi problemy v o forme, ale riesi moznost zmenit enum_values v ui formularu cez objekt - inak by dochadzalo k opatovnemu merge enum_values v input komponente
      // nahradene specialitkou pre table input - vid if nizsie
      //if ($this->params['table'] != '') $this->params['columns'][$col_name]['table_column'] = $this->params['table'].".".$col_name;
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
      if ('' !== $this->data[$col_name] && _count($this->data) && isset($this->data[$col_name])) {
        if (!$col_def['no_view_permissions']) {
          $this->params['columns'][$col_name]['value'] = $this->data[$col_name];
        }
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
            "rows" => array_keys($this->params['columns']),
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
          } else if (!empty($col['rows'])) {
            $col_html .= $this->renderRows($col['rows']);
          } else if (is_array($col['tabs'])) {
            $tabPages = [];

            // kazdy element predstavuje jeden tab vo formulari
            foreach ($col['tabs'] as $tab_name => $rows) {
              $tabPages[] = [
                'title' => $tab_name,
                'content' => $this->renderRows($rows),
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
            foreach ($col['content'] as $element) {
              if (is_string($element)) {
                $col_html .= $element;
              } else {
                $col_html .= $element->render();
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
          data-reopen-after-save='{$this->params['reopen_after_save']}'
          data-do-not-close='{$this->params['do_not_close']}'
          data-window-uid='".($window === NULL ? "" : $window->uid)."'
          data-form-type='{$this->params['form_type']}'
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

    $this->params['onclose'] = $this->params['form_onclose'].$this->params['onclose'];

    $html .= "
      <script>

        function {$this->params['uid']}_onbeforesave(uid, data, params){
          var allowed = true;
          {$this->params['onbeforesave']}
          return {data: data, allowed: allowed}
        }
        function {$this->params['uid']}_onaftersave(uid, data, params){
          {$this->params['onaftersave']}

          ".($this->params['simple_insert'] ?
          "var re_render_params = $.parseJSON(decodeURIComponent('".rawurlencode(json_encode($_REQUEST))."'));
          re_render_params['simple_insert'] = 0;
          re_render_params.id = data.inserted_id;
          re_render_params.after_simple_insert = 1;
          window_render('{$this->adios->action}', re_render_params);" : '')."
          return {}
        }

        function {$this->params['uid']}_onbeforedelete(uid, data, params){
          var allowed = true;
          {$this->params['onbeforedelete']}
          return {data: data, allowed: allowed}
        }
        function {$this->params['uid']}_onafterdelete(uid, data, params){
          {$this->params['onafterdelete']}
          return {}
        }

        function {$this->params['uid']}_onbeforeclose(uid, data, params){
          var allowed = true;
          {$this->params['onbeforeclose']}
          return {data: data, allowed: allowed}
        }
        function {$this->params['uid']}_onafterclose(uid, data, params){
          {$this->params['onafterclose']}
          return {}
        }

        function {$this->params['uid']}_onbeforecopy(uid, data, params){
          var allowed = true;
          {$this->params['onbeforecopy']}
          return {data: data, allowed: allowed}
        }
        function {$this->params['uid']}_onaftercopy(uid, data, params){
          {$this->params['onaftercopy']}
          return {}
        }

        ".('' != $this->params['onclose'] ?
          "function {$this->params['uid']}_ondesktopclose(uid, data, params){
          {$this->params['onclose']}
          return {}
          }" : '').'

        '.('' != $this->params['onclose'] ?
          "function {$this->params['uid']}_onclose(uid, data, params){
          {$this->params['onclose']}
          return {}
          }" : '').'

        '.$this->params['javascript']."

        $(document).ready(function(){
          var uid = '{$this->params['uid']}';
          ".$this->params['onload'].'

        });
      </script>
    ';

    if ($window !== NULL) {
      $window->setTitle($this->model->formTitleForEditing);
      $window->setHeaderLeft([
        $this->closeButton,
        $this->saveButton
      ]);
      $window->setHeaderRight([
        $this->copyButton,
        $this->deleteButton
      ]);
    }

    return $this->applyDisplayMode((string) $html);
  }

  public function Input($colName, $formData = NULL, $initiatingModel = NULL) {
    return $this->addView('Input',
      array_merge(
        [
          'uid' => $this->params['uid'].'_'.$colName,
          'form_uid' => $this->params['uid'],
          'form_data' => $formData,
          'initiating_column' => $colName,
          'initiating_model' => $initiatingModel,
        ],
        $this->params['columns'][trim($colName)] ?? []
      )
    )->render();
  }

}
