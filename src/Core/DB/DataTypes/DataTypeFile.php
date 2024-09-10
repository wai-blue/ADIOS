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
class DataTypeFile extends \ADIOS\Core\DB\DataType
{
  public function sqlCreateString($table_name, $col_name, $params = [])
  {
    return "`$col_name` varchar(255) " . $this->getSqlDefinitions($params);
  }

  public function sqlValueString($table_name, $col_name, $value, $params = [])
  {
    if ($value == 'delete_file') {
      $sql = "`{$col_name}` = ''";
    } else {
      if (is_string($value)) {
        $sql = "`{$col_name}` = '" . $this->app->db->escape($value) . "'";
      } else {
        $sql = "`{$col_name}` = ''";
      }
    }

    return $sql;
  }

  public function toHtml($value, $params = [])
  {
    $html = '';

    $value = htmlspecialchars($value);

    if ('' != $value && file_exists($this->app->config['uploadDir']."/{$value}")) {
      $value = str_replace('\\', '/', $value);
      $value = explode('/', $value);
      $value[count($value) - 1] = rawurlencode($value[count($value) - 1]);
      $value = implode('/', $value);

      $html = "<a href='{$this->app->config['accountUrl']}/File?f={$value}' onclick='event.cancelBubble = true;' target='_blank'>".basename($value).'</a>';
    }

    return $html;
  }

  public function toCsv($value, $params = [])
  {
    return "{$this->app->config['accountUrl']}/File?f=/{$value}";
  }
}
