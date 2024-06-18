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
class Save extends \ADIOS\Core\Controller {
  public function render() {
    $search = json_encode([
      "model" => $this->params['model'],
      "searchGroup" => $this->params['searchGroup'],
      "searchName" => $this->params['searchName'],
      "search" => $this->params['search'],
    ]);

    $this->app->saveConfig(
      [
        "model" => $this->params['model'],
        "search" => $this->params['search'],
      ],
      "Components/Table/savedSearches/{$this->params['searchGroup']}/{$this->params['searchName']}/"
    );

    return $searchUID;
  }
}
