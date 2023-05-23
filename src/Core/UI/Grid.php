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

  public string $twigTemplate = "Core/UI/Grid";

  /**
   * @internal
   */
  public function __construct($adios, ?array $params = null) {
    $this->adios = &$adios;

    $this->params = parent::params_merge([
      "layout" => [],
      "layoutSm" => [],
      "layoutMd" => [],
      "layoutLg" => [],
      "areas" => []
    ], $params);
  }

  public function getTwigParams(): array {
    return $this->params;
  }

}
