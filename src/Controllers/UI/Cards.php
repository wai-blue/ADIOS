<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\UI;

/**
 * 'UI/Cards' action. Renders a UI/Cards element.
 *
 * Example call inside **Javascript** code (using AJAX):
 * ```
 * _ajax_update(
 *   'UI/Cards',
 *   {'model': 'MyWidget/Models/MyModel'},
 *   'DOM_element_id'
 * );
 * ```
 *
 * Example call inside **PHP** code (works but is *not optimal*):
 * ```
 * echo $adios->renderAction(
 *   "UI/Cards",
 *   ["model" => "MyWidget/Models/MyModel"]
 * );
 * ```
 *
 * @package UI\Controllers
 */

class Cards extends \ADIOS\Core\Controller {
  # TODO: Nepouziva sa
  function render() {
    return (new \ADIOS\Core\Views\Cards($this->adios, $this->params))->render();
  }
}
