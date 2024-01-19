<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\DB\DataTypes;

/**
 * @package DataTypes
 */
class DataTypeVarchar extends \ADIOS\Core\DB\DataType
{

  public function sqlCreateString($table_name, $col_name, $params = []) {
    $params['sql_definitions'] = '' != trim((string) $params['sql_definitions']) 
      ? $params['sql_definitions'] 
      : "default '" . (string) $this->getDefaultValue($params) . "' ";

    return "`$col_name` varchar({$params['byte_size']}) {$params['sql_definitions']}";
  }

  public function sqlValueString($table_name, $col_name, $value, $params = []) {
    $params = _put_default_params_values($params, [
      'null_value' => false,
      'dumping_data' => false,
      'escape_string' => $this->adios->getConfig('m_datapub/escape_string', true),
    ]);

    if ($params['null_value']) {
      $sql = "`{$col_name}` = NULL";
    } else {
      $sql = "`{$col_name}` = '".($params['escape_string'] ? $this->adios->db->escape($value) : $value)."'";
    }

    return $sql;
  }

  private function _toHtmlOrCsv($value, $params = []) {
    $html = '';

    $value = $params['export_csv'] ? $value : htmlspecialchars((string) $value);

    if (is_array($params['col_definition']['enumValues'])) {
      $html = l($params['col_definition']['enumValues'][$value]);
    } else {
      if (empty($value)) {
        $html = "<div style='color:#EEEEEE'>[N/A]</div>";
      } else {
        $html = mb_substr($value, 0, ($params['col_definition']['wa_list_char_length'] ? $params['col_definition']['wa_list_char_length'] : 80), 'utf-8');
        if (strlen($html) < strlen($value)) {
          $html .= '...';
        }
      }
    }

    return $html;
  }

  public function toHtml($value, $params = []) {
    return $this->_toHtmlOrCsv($value, $params);
  }

  public function toCsv($value, $params = []) {
    return $value;
  }
}
