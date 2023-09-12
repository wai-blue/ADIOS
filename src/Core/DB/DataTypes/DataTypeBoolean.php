<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\DB\DataTypes;

/**
 * Boolean data type.
 *
 * Converted to **boolean** in the SQL. Indexed by default. Default 0. NOT NULL.
 *
 * *UI/Input* renders *checkbox* for this data type.
 *
 * Example of definition in \ADIOS\Core\Model's column() method:
 * ```
 *   "myColumn" => [
 *     "type" => "boolean",
 *     "title" => "My Boolean Column",
 *     "show_column" => FALSE,
 *   ]
 * ```
 *
 * @package DataTypes
 */
class DataTypeBoolean extends \ADIOS\Core\DB\DataType {
  
  protected $defaultValue = 0;

  public function get_sql_create_string($table_name, $col_name, $params = []) {
    $params['sql_definitions'] = '' != trim((string) $params['sql_definitions']) 
      ? $params['sql_definitions'] 
      : " default " . (int) $this->getDefaultValue($params);

    return "`$col_name` int(1) {$params['sql_definitions']} NOT NULL";
  }

  public function get_sql_column_data_string($tableName, $colName, $value, $params = []) {
    return "`{$colName}` = " . (int) $value . ""; 
  }

  public function get_html_or_csv($value, $params = []) {
    if ((int) $value == 1) {
      $html = $this->translate("Yes");
    } else {
      $html = $this->translate("No");
    }

    return $html;
  }

  /**
   * @internal
   */
  public function get_html($value, $params = []) {
    if ((int) $value !== 0) {
      $html = "<i class='fas fa-check-circle' style='color:#4caf50' title='".$this->translate("Yes")."'></i>";
    } else {
      $html = "<i class='fas fa-times-circle' style='color:#ff5722' title='".$this->translate("No")."'></i>";
    }

    return "<div style='text-align:center'>{$html}</div>";
  }

  public function get_csv($value, $params = []) {
    return (int) $value;
  }

  public function fromString(?string $value)
  {
    return (bool) $value;
  }
}
