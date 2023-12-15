<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController\Inputs;

class CrossTableInputField extends \ADIOS\Core\ViewsWithController\Input {

  public function render(string $panel = ''): string
  {
    $crossModel = $this->adios->getModel($this->params['cross_model']);

    switch ($this->params['columns'] ?? 3) {
      case 1: default: $bootstrapColumnSize = 12; break;
      case 2: $bootstrapColumnSize = 6; break;
      case 3: $bootstrapColumnSize = 4; break;
      case 4: $bootstrapColumnSize = 3; break;
      case 6: $bootstrapColumnSize = 2; break;
    }

    if (empty($this->params['cross_model'])) {
      throw new \ADIOS\Core\Exceptions\GeneralException("CrossTableInputField Input: Error #1");
    }

    // Get saved values in DB
    $itemValues = $this->adios->db->fetchRaw(
      "
        select
          *
        from `".$crossModel->getFullTableSqlName()."`
        where
          `".$this->adios->db->escape($this->params['cross_key_column'])."` = '".$this->adios->db->escape($this->params['cross_key_value'])."'
      ",
      $this->adios->db->escape($this->params['key_by'])
    );
    
    $html = "
      <div class='adios ui Input cross-table-input-field' data-uid='{$this->cssUid}'>
        <input type='hidden' id='{$this->uid}' data-is-adios-input='1'>
        <div class='row'>
    ";
    $i = 0;

    foreach ($this->params['items'] as $itemUID => $itemParams) {

      $inputElementId = "{$this->uid}_input_{$i}";
      $inputCallback = $this->params['input_callback'];
      $inputHtml = $inputCallback($this, $inputElementId, $itemUID, $itemValues);

      $html .= "
        <div class='col-lg-{$bootstrapColumnSize} col-md-12 wrap-flex'>
          <label for='{$inputElementId}'>
            ".hsc($itemParams['title'])."
          </label>
          {$inputHtml}
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
          $('#{$this->uid}').closest('.cross-table-input-field').find('input[type!=hidden],select,textarea').each(function() {
            if ($(this).attr('type') == 'checkbox') {
              data.push([
                $(this).data('item-uid'),
                $(this).is(':checked')
              ]);
            } else {
              data.push([
                $(this).data('item-uid'),
                $(this).val()
              ]);
            }
          });
          $('#{$this->uid}').val(JSON.stringify(data));
        }

        {$this->uid}_serialize();
      </script>
    ";


    return $html;
  }
}
