<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\DB\DataTypes;

/**
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

  protected $defaultValue = 0;

  public function sqlCreateString($table_name, $col_name, $params = []) {
    $params['sql_definitions'] = '' != trim((string) $params['sql_definitions']) 
      ? $params['sql_definitions'] 
      : " default " . (int) $this->getDefaultValue($params);

    return "`$col_name` int(1) {$params['sql_definitions']} NOT NULL";
  }

  public function sqlValueString($tableName, $colName, $value, $params = []) {
    return "`{$colName}` = " . (int) $value . ""; 
  }

  private function _toHtmlOrCsv($value, $params = []) {
    if ((int) $value == 1) {
      $html = $this->translate("Yes");
    } else {
      $html = $this->translate("No");
    }

    return $html;
  }

  public function toHtml($value, $params = []) {
    return $this->_toHtmlOrCsv($value, $params);
  }

  public function toCsv($value, $params = []) {
    return $this->_toHtmlOrCsv($value, $params);
  }
}
