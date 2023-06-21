<?php

namespace ADIOS\Core\Views;

/**
 * Renders Card-based list of elements.
 *
 * @package UI\Elements
 */
class Cards extends \ADIOS\Core\View {
  var bool $useSession = TRUE;

  public function render(string $panel = ''): string {
    $card = $this->params['card'];

    return;
  }
}
