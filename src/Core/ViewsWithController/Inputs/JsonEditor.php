<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController\Inputs;

class JsonEditor extends \ADIOS\Core\ViewsWithController\Input {

  public function __construct($adios, array $params = [])
  {
    $params = array_replace_recursive([
      'uid' => '',
      'schema' => [],
      'value' => '{}', // JSON string
      'disableEditJson' => FALSE,
      'disableProperties' => FALSE,
    ], $params);

    if (!is_array($params['schema'])) $params['schema'] = [];

    parent::__construct($adios, $params);
  }

  public function render(string $panel = ''): string
  {
    $valueSanitized = json_encode(json_decode($this->params['value'], TRUE));

    return "
      <div>
        <textarea id='{$this->params['uid']}' style='display:none'>{$valueSanitized}</textarea>
        <div id='{$this->params['uid']}_editor' class='pt-2'></div>

        <script>
          var {$this->params['uid']}_editor = {
            element: document.getElementById('{$this->params['uid']}_editor'),
            value: JSON.parse(Base64.decode('".base64_encode($valueSanitized)."')) ?? {},

            options: {
              schema: JSON.parse(Base64.decode('".base64_encode(json_encode($this->params['schema']))."')),
              theme: 'bootstrap4',
              // disable_collapse: true,
              disable_edit_json: ".($this->params['disableEditJson'] ? "true" : "false").",
              disable_properties: ".($this->params['disableProperties'] ? "true" : "false").",
              // use_default_values: false,
              // required_by_default: true,
              remove_empty_properties: true,
              form_name_root: '{$this->params['uid']}'
            },

            render: function() {
              if (Object.keys(this.value).length > 0) {
                this.options['startval'] = this.value;
              }

              editor = new JSONEditor(
                this.element,
                this.options
              ).on('change', () => {
                $('#{$this->params['uid']}').val(JSON.stringify(editor.getValue()));
              });
            }
          }

          {$this->params['uid']}_editor.render();

        </script>
        <style>
          .card-title.je-object__title { display: none !important; }
          .btn-group.je-object__controls button { margin: 2px; border-radius: 0px; }
        </style>
      </div>
    ";
  }
}
