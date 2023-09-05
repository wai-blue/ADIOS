<?php

namespace ADIOS\Core\Views;

/**
 * Renders a layout based on HTML grid configuration.
 *
 * Example code to render layout:
 *
 * ```php
 *   $adios->view->create('Grid', [
 *     ...
 *   ]);
 * ```
 *
 * @package UI\Elements
 */
class Grid extends \ADIOS\Core\View {

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
      "layoutXl" => [],
      "layoutXXl" => [],
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

      if (!empty($areaParams['view'])) {
        $tmp = $this->addView(
          $areaParams['view'],
          $areaParams['params'] ?? []
        );
        $html .= $tmp->render();
      } else if (!empty($areaParams['action'])) {
        $html .= "
          <div
            id='{$this->uid}_area_{$areaName}_content_div'
            style='width:100%'
          >
            <div style='
              width:100%;
              height:100%;
              text-align:center;
              padding: 1em;
            '>
              <style>
                .{$this->uid}_spinner {
                  display:inline-block;
                  color: var(--cl-main);
                  width: 50px;
                  height: 50px;
                  top: 50%;
                  left: 50%;
                  border-radius: 50%;
                  border: 5px solid #EEEEEE;
                  border-top-color: var(--cl-main);
                  animation: {$this->uid}_rotateSpinner 1200ms cubic-bezier(0.66, 0.41, 0.31, 0.56) infinite;
                }

                @keyframes {$this->uid}_rotateSpinner {
                  to {
                    transform: rotate(360deg);
                  }
                }
              </style>

              Loading...<br/>
              <br/>
              <div style='margin:auto'>
                <div class='{$this->uid}_spinner'>:</div>
              </div>
            </div>
          </div>
          <script>
            setTimeout(function() {
              let params = JSON.parse(
                Base64.decode('" . base64_encode(json_encode($areaParams['params'])) . "')
              );

              _ajax_update(
                '{$areaParams['action']}',
                params,
                '{$this->uid}_area_{$areaName}_content_div'
              );
            }, ".rand(200, 600).");
          </script>
        ";
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
