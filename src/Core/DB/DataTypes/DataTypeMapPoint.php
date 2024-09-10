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
class DataTypeMapPoint extends \ADIOS\Core\DB\DataType
{
  
  protected $defaultValue = '{"latitude": 0,"longitude": 0}';

  public function get_sql_create_string($table_name, $col_name, $params = []) {
    return "`$col_name` json " . $this->getSqlDefinitions($params);
  }

  public function columnDefinitionPostProcess(array $colDef): array
  {
    $colDef['type'] = 'json';
    $colDef['schema'] = [
      'properties' => [
        'latitude' => [
          'title' => 'Latitude',
          'type' => 'string'
        ],
        'longtitude' => [
          'title' => 'Longtitude',
          'type' => 'string'
        ]
      ]
    ];

    return $colDef;
  }

  public function get_sql_column_data_string($table_name, $col_name, $value, $params = []) {
    $params = \ADIOS\Core\Helper::arrayMergeRecursively($params, [
      'null_value' => false,
      'dumping_data' => false,
      'escape_string' => $this->app->getConfig('m_datapub/escape_string', true),
    ]);

    if ($params['null_value']) {
      $sql = "`{$col_name}` = NULL";
    } else {
      $sql = "`{$col_name}` = '".($params['escape_string'] ? $this->app->db->escape($value) : $value)."'";
    }

    return $sql;
  }

  public function get_html_or_csv($value, $params = []) {
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

  public function get_html($value, $params = []) {
    return $this->get_html_or_csv($value, $params);
  }

  public function get_csv($value, $params = []) {
    return $value;
  }
}
