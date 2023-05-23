<?php

namespace ADIOS\Core\UI;

/**
 * Renders a layout based on HTML grid configuration.
 *
 * Example code to render layout:
 *
 * ```php
 *   $adios->ui->Layout([
 *     ...
 *   ]);
 * ```
 *
 * @package UI\Elements
 */
class Grid extends \ADIOS\Core\UI\View {

  /**
   * @internal
   */
  public function __construct($adios, $params = null) {
    parent::__construct($adios, $params);
  }

}
