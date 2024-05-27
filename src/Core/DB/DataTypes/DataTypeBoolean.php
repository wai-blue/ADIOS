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
 * *Components/Input* renders *checkbox* for this data type.
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

  public function sqlCreateString($table_name, $col_name, $params = []) {
    $sqlDef = $params['sql_definitions'] ?? '';
    $params['sql_definitions'] = '' != trim($sqlDef) ? $sqlDef : " default " . (int) $this->getDefaultValue($params);
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

  /**
   * @internal
   */
  public function toHtml($value, $params = []) {
    if ((int) $value !== 0) {
      $html = "<i class='fas fa-check-circle' style='color:#4caf50' title='".$this->translate("Yes")."'></i>";
    } else {
      $html = "<i class='fas fa-times-circle' style='color:#ff5722' title='".$this->translate("No")."'></i>";
    }

    return "<div style='text-align:center'>{$html}</div>";
  }

  public function toCsv($value, $params = []) {
    return (int) $value;
  }

  public function fromString(?string $value)
  {
    return (bool) $value;
  }
}
