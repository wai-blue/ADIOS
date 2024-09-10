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
class DataTypePassword extends \ADIOS\Core\DB\DataType
{
  public function sqlCreateString($table_name, $col_name, $params = []) {
    return "`$col_name` varchar({$params['byte_size']}) " . $this->getSqlDefinitions($params);
  }

  public function sqlValueString($table_name, $col_name, $value, $params = []) {
    $params = \ADIOS\Core\Helper::arrayMergeRecursively($params, [
      'null_value' => false,
      'dumping_data' => false,
      'escape_string' => $this->app->getConfig('m_datapub/escape_string', true),
    ]);

    if ($params['null_value']) {
      $sql = "$col_name=NULL";
    } else {
      $pswd_1 = $params["data"]["{$col_name}_1"] ?? "";
      $pswd_2 = $params["data"]["{$col_name}_2"] ?? "";

      if ($pswd_1 != "" && $pswd_1 == $pswd_2) {
        $sql = "`{$col_name}` = '".password_hash($pswd_1, PASSWORD_DEFAULT)."'";
      }
    }

    return $sql;
  }

  public function validate(\ADIOS\Core\Model $model, $value): bool
  {
    if (is_array($value)) {
      return $value[0] == $value[1];
    } else {
      return true;
    }
  }
  
  public function normalize(\ADIOS\Core\Model $model, string $colName, $value, $colDefinition)
  {
    if (is_array($value)) {
      if (method_exists($model, 'hashPassword')) {
        return $model->hashPassword((string) $value[0]);
      } else {
        return password_hash($value[0], PASSWORD_DEFAULT);
      }
    } else {
      return null;
    }
  }
  
  public function toHtml($value, $params = []) {
    return "...".substr(hsc($value), 8, 8)."...";
  }

  public function toCsv($value, $params = []) {
    return '';
  }
}
