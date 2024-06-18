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
class DataTypeImage extends \ADIOS\Core\DB\DataType {
  public function sqlCreateString($table_name, $col_name, $params = []) {
    $sqlDef = $params['sql_definitions'] ?? '';
    $params['sql_definitions'] = '' != trim($sqlDef) ? $sqlDef : "default " . (int) $this->getDefaultValue($params);
    return "`$col_name` varchar(255) {$params['sql_definitions']}";
  }

  public function sqlValueString($table_name, $col_name, $value, $params = []) {
    $params = \ADIOS\Core\Helper::arrayMergeRecursively($params, [
      'null_value' => false,
      'dumping_data' => false,
      'supported_extensions' => $this->app->getConfig('m_datapub/columns/image/supported_extensions', ['jpg', 'gif', 'png', 'jpeg', 'webp']),
      'escape_string' => $this->app->getConfig('m_datapub/escape_string', true),
    ]);

    if ($params['dumping_data']) {
      $sql = "$col_name='$value'";
    } else {
      if ($value == 'delete_image') {
        $sql = "$col_name=''";
      } else {
        $sql = "$col_name='".($params['escape_string'] ? $this->app->db->escape($value) : $value)."'";
      }
    }

    return $sql;
  }

  public function toHtml($value, $params = []) {
    $html = '';

    $value = htmlspecialchars($value);

    if ('' != $value && file_exists($this->app->config['uploadDir']."/{$value}")) {
      $img_url = "{$this->app->config['images_url']}/{$value}";
      $img_style = "style='height:30px;border:none'";

      $img_url = "{$this->app->config['url']}/Image?f=".urlencode($value).'&cfg=wa_list&rand='.rand(1, 999999);
      $img_style = "style='border:none'";

      $pathinfo = pathinfo($value);
      $html = "<a href='{$this->app->config['url']}/Image?f=".urlencode($value)."' target='_blank' onclick='event.cancelBubble=true;'><img src='{$img_url}' {$img_style} class='list_image'></a>";
      if ($params['display_basename']) {
        $html .= "<br/>{$pathinfo['basename']}";
      }
    }

    $html = "<div style='text-align:center'>{$html}</div>";

    return $html;
  }

  public function toCsv($value, $params = []) {
    return "{$this->app->config['images_url']}/{$value}";
  }
}
