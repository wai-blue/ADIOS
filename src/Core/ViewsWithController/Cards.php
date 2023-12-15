<?php

namespace ADIOS\Core\ViewsWithController;

/**
 * Renders Card-based list of elements.
 *
 * @package UI\Elements
 */
class Cards extends \ADIOS\Core\ViewWithController {
  var bool $useSession = TRUE;

  # TODO: Nepouziva sa
  public function render(string $panel = ''): string {
    $card = $this->params['card'];

    return;
  }
}
