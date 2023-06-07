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
    $this->adios = $adios;

    $this->params = parent::params_merge([
      "layout" => [],
      "layoutSm" => [],
      "layoutMd" => [],
      "layoutLg" => [],
      "areas" => []
    ], $params);

    parent::__construct($adios, $params);
  }

  public function getTwigParams(): array {
    $html = '';

    foreach ($this->params['areas'] as $areaName => $areaParams) {
      $html .= "
        <div
          class='{$this->uid}-area-{$areaName} ".($areaParams['cssClass'] ?? '')."'
        >
      ";

      if (!empty($areaParams['uiComponent'])) {
        $html .= $this->adios->ui->create(
          $areaParams['uiComponent'],
          $areaParams['params'],
          $this
        )->render();
      } else if (!empty($areaParams['action'])) {
        $html .= $this->adios->renderAction(
          $areaParams['action'],
          $areaParams['params']
        );
      } else if (!empty($areaParams['html'])) {
        $html .= $areaParams['html'];
      }

      $html .= "
        </div>
      ";
    }

    $this->params['html'] = $html;

    return $this->params;
  }

}
