<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Components\Table\Search;

/**
 * @package Components\Controllers
 */
class Delete extends \ADIOS\Core\Controller {
  public function render() {
    $this->adios->deleteConfig(
      "Components/Table/savedSearches/{$this->params['searchGroup']}/{$this->params['searchName']}"
    );

    return TRUE;
  }
}
