<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\DB\DataTypes;

/**
 * Deprecated boolean data type.
 *
 * Converted to **char(1)** in the SQL. Indexed by default. Default 'N'.
 * 'Y' means TRUE, 'N' means FALSE.
 *
 * *UI/Input* renders *checkbox* for this data type.
 *
 * Example of definition in \ADIOS\Core\Model's column() method:
 * ```
 *   "myColumn" => [
 *     "type" => "bool",
 *     "title" => "My Bool Column",
 *     "show_column" => FALSE,
 *   ]
 * ```
 *
 * @deprecated
 * @package DataTypes
 */
class DataTypeBool extends \ADIOS\Core\DB\DataType {

  protected mixed $defaultValue = 0;

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

  public function get_html($value, $params = []) {
    return $this->get_html_or_csv($value, $params);
  }

  public function get_csv($value, $params = []) {
    return $this->get_html_or_csv($value, $params);
  }
}
