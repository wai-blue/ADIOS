<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\UI\Input;

class CrossTableSelectField extends \ADIOS\Core\Input {
  public function render() {
    $crossModel = $this->adios->getModel($this->params['cross_model']);

    var_dump($this->params);

    switch ($this->params['columns'] ?? 3) {
      case 1: default: $bootstrapColumnSize = 12; break;
      case 2: $bootstrapColumnSize = 6; break;
      case 3: $bootstrapColumnSize = 4; break;
      case 4: $bootstrapColumnSize = 3; break;
      case 6: $bootstrapColumnSize = 2; break;
    }
    
    $columns = $crossModel->columns();

    if (empty($this->params['cross_model']) || empty($this->params['list_model'])) {
      throw new \ADIOS\Core\Exceptions\GeneralException("CrossTableInputField Input: Error #1");
    }

    // Get saved values in DB
    $crossValuesRaw = $this->adios->db->get_all_rows_query("
      select
        *
      from `".$crossModel->getFullTableSQLName()."`
      where
        `{$this->params['cross_key_column']}` = '".$this->adios->db->escape($this->params['cross_key_value'])."'
    ");
    $savedValues = [];
    foreach ($crossValuesRaw as $crossValueRaw) {
      $savedValues[$crossValueRaw['id_branch_risk']] = $crossValueRaw[$this->params['cross_value_column']];
    }
    $savedValues = array_unique($savedValues);
    
    $html = "
      <div class='adios ui Input input-field'>
        <input type='text' id='{$this->uid}' data-is-adios-input='1'>
        <div class='row'>
        
    ";
    $i = 0;
    //var_dump($this->params['list_values']);

    foreach ($this->params['values'] as $assignedItemRowId => $displayValue) {
      // $defaultOption = $savedValues[$value['id']];
      // $optionsHtml = "";
      // foreach ($this->params['select_options'] as $key => $optionValue) {
      //   $isDefaultOption = $defaultOption == $key ? TRUE : FALSE;
      //   $optionsHtml .= "<option ".($isDefaultOption ? "selected='selected'" : "")." value='{$key}'>{$optionValue}</option>";
      // }

      $inputElementId = "{$this->uid}_input_{$i}";
      $inputCallback = $this->params['input_callback'];
      $inputHtml = $inputCallback($this, $inputElementId, $savedValues[$assignedItemRowId]);

      $html .= "
        <div class='col-lg-{$bootstrapColumnSize} col-md-12'>
          <label for='{$inputElementId}'>
            ".hsc($displayValue)."
          </label>
          {$inputHtml}
          <!-- <select
            id='{$inputElementId}}'
            data-key='".ads($assignedItemRowId)."'
            adios-do-not-serialize='1'
            onchange='{$this->uid}_serialize(); console.log(\"a\");'
          >
            {$optionsHtml}
          </select> -->
        </div>
      ";

      $i++;
    }
    $html .= "
        </div>
      </div>
      <script>
        function {$this->uid}_serialize() {
          let data = [];
          console.log('b');
          $('#{$this->uid}').closest('.input-field').find('input,select,textarea').each(function() {
            data.push([
              $(this).data('key'),
              $(this).val()
            ]);
          });
          console.log(data);
          $('#{$this->uid}').val(JSON.stringify(data));
        }

        {$this->uid}_serialize();
      </script>
    ";


    return $html;
  }
}
