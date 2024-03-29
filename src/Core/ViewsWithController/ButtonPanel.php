<?php

namespace ADIOS\Core\ViewsWithController;

/**
 * Renders a panel with buttons
 *
 * Example code to render:
 *
 * ```php
 *   $adios->view->create('\\ADIOS\\Core\\ViewsWithController\\ButtonPanel', [
 *     "columns" => [
 *       [
 *         "cssClass" => "col-6",
 *         "buttons" => [
 *           [
 *             "text" => "This is a button #1",
 *             "faIcon" => "fas fa-home",
 *             "onclick" => "alert('Hello #1 world!');",
 *             "hint" => "An alert will pop up when you click this button."
 *           ],
 *           [
 *             "text" => "This is a button #2",
 *             "faIcon" => "fas fa-home",
 *             "onclick" => "alert('Hello #2 world!');",
 *             "hint" => "An alert will pop up when you click this button."
 *           ]
 *         ]
 *       ],
 *       [
 *         "cssClass" => "col-6",
 *         "buttons" => [
 *           [
 *             "text" => "This is a button #3",
 *             "faIcon" => "fas fa-home",
 *             "onclick" => "alert('Hello #3 world!');",
 *             "hint" => "An alert will pop up when you click this button."
 *           ],
 *           [
 *             "text" => "This is a button #4",
 *             "faIcon" => "fas fa-home",
 *             "onclick" => "alert('Hello #4 world!');",
 *             "hint" => "An alert will pop up when you click this button."
 *           ]
 *         ]
 *       ]
 *     ]
 *   ]);
 * ```
 *
 * @package UI\Elements
 */
class ButtonPanel extends \ADIOS\Core\ViewWithController {

  public function __construct(
    ?\ADIOS\Core\Loader $adios = NULL,
    array $params = [],
    ?\ADIOS\Core\ViewWithController $parentView = NULL
  ) {

    parent::__construct($adios, $params, $parentView);

    if (!is_array($this->params['columns'])) $this->params['columns'] = [];

  }

  public function render(string $panel = ''): string
  {
    $html = "
      ".(empty($this->params['title']) ? "" : "<div class='h3 text-primary mb-0 p-4'>".hsc($this->params['title'])."</div>")."
      <div class='row'>
    ";

    foreach ($this->params['columns'] as $column) {
      $columnHtml = "
        <div class='".hsc($column['cssClass'])."'>
          <div class='card shadow-sm mb-2'>
            ".(empty($column['title']) ? "" : "
              <div class='card-header py-3'>
                <div class='m-0 font-weight-bold text-primary'>".hsc($column['title'])."</div>
              </div>
            ")."
            <div class='card-body'>
      ";

      if (is_array($column['buttons'])) {
        foreach ($column['buttons'] as $button) {
          $columnHtml .= "
            <div class='row py-2'>
              <div class='col-md-3 align-self-center'>".$this->adios->view->create('\\ADIOS\\Core\\ViewsWithController\\Button', $button)->render()."</div>
              <div class='col-md-9 align-self-center'>".hsc($button['hint'])."</div>
            </div>
          ";
        }
      }

      $columnHtml .= "
            </div>
          </div>
        </div>
      ";

      $html .= $columnHtml;

    }

    $html .= "
      </div>
    ";

    return $html;
  }
}
