<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController\Inputs;

class Tags extends \ADIOS\Core\ViewsWithController\Input {
  
  public function render(string $panel = ''): string
  {
    $params = parent::params_merge([
      'model' => '',
      'tagColumn' => 'tag',
      'initialTags' => '[""]' 
    ], $this->params);

    $model = $this->adios->getModel($params['model']);
    $data = $model->getAll();

    if (!array_key_exists($params['tagColumn'], $model->columns())) {
      exit($this->translate("Column named [{$params['tagColumn']}] does not exists for the model {$params['model']}"));
    }

    $allTags = [];
    foreach ($data as $item) {
      $allTags[] = strtolower(ads($item[$params['tagColumn']]));
    }

    $allTagsAutocomplete = "'" . implode("','", $allTags). "'";

    $html = "<textarea id='{$this->uid}_tag'></textarea>";
    $html .= "<input type='hidden' name='{$this->uid}' id='{$this->uid}'>";
    $html .= "<div style='display: none' id='{$this->uid}_desc' class='input-description'>".$this->translate("New tag will be created.")."</div>";

    $html .= "
      <script>
        $('#{$this->uid}_tag').tagEditor({
          initialTags: ".json_encode(json_decode($params["initialTags"], TRUE)).",
          autocomplete: {
              delay: 0,
              position: { collision: 'flip' },
              source: [{$allTagsAutocomplete}],
          },
          forceLowercase: true,
          placeholder: 'Enter tags ...',
          onChange: function(field, editor, tags) {
            let tagsJson = JSON.stringify(tags);
            $('#{$this->uid}').val(
              tagsJson
            );
            let newTagChecks = false;
            tags.forEach((element, index) => {
              if (this.autocomplete.source.indexOf(element) < 0) {
                newTagChecks = true;
              }
            });
            if (newTagChecks) {
              $('#{$this->uid}_desc').css('display','block');
            }
            else {
              $('#{$this->uid}_desc').css('display','none');
            }
          },
        });
      </script>
      <style>
        .ui-menu {
          z-index: 9999999;
        }
      </style>
    ";

    return $html;
  }
}
