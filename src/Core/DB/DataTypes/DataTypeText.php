<?php

namespace ADIOS\Core\DB\DataTypes;

/**
 * Multiline text data type.
 *
 * Converted to **text** in the SQL.
 *
 * *Components/Input* renders *textarea* for this data type.
 *
 * Example of definition in \ADIOS\Core\Model's column() method:
 * ```
 *   "myColumn" => [
 *     "type" => "text",
 *     "title" => "My Text Column",
 *     "show_column" => FALSE,
 *   ]
 * ```
 *
 * @package DataTypes
 */
class DataTypeText extends \ADIOS\Core\DB\DataType
{
  public function sqlCreateString($table_name, $col_name, $params = [])
  {
    $sqlDataType = ($params['sql_data_type'] ?? "text");

    if (!in_array($sqlDataType, ["tinytext", "text", "mediumtext", "longtext"])) {
      $sqlDataType = "text";
    }

    $params['sql_definitions'] = '' != trim((string) $params['sql_definitions']) 
      ? $params['sql_definitions'] 
      : "default '" . (string) $this->getDefaultValue($params) . "'";

    return "`$col_name` {$sqlDataType} {$params['sql_definitions']}";
  }

  public function sqlValueString($table_name, $col_name, $value, $params = [])
  {
    $params = _put_default_params_values($params, [
      'null_value' => false,
      'dumping_data' => false,
      'escape_string' => $this->adios->getConfig('m_datapub/escape_string', true),
    ]);

    if ($params['null_value']) {
      $sql = "`{$col_name}` = NULL";
    } else {
      $sql = "`{$col_name}` = '" . ($params['escape_string'] ? $this->adios->db->escape($value) : $value) . "'";
    }

    return $sql;
  }

  public function toHtml($value, $params = [])
  {
    $value = 'yes' == $params['col_definition']['wa_list_no_html_convert'] ? $value : strip_tags($value ?? '');
    $html = mb_substr($value, 0, ($params['col_definition']['wa_list_char_length'] ? $params['col_definition']['wa_list_char_length'] : 80), 'utf-8');
    if (strlen($html) < strlen($value)) {
      $html .= '...';
    }
    $html = ($html);

    return $html;
  }
}
