<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\Views;

class Dashboard extends \ADIOS\Core\View
{
  public string $twigTemplate = "ADIOS/Templates/UI/Dashboard";

  public function preRender(string $panel = '')
  {
    return [
      'cfg' => $this->getUserDashboardConfig(),
      'param2' => 'aoj',
      'availableCards' => $this->getAvailableCards(),
    ];
  }

  public function getUserDashboardConfig() {
    return
      $this->adios->config['dashboard']
      [$this->adios->userProfile['id']]
      [$this->uid]
    ;
  }

  // public function getUserAvailableCards() {
  //   return $this->adios->renderReturn(["param1" => "xahoj"]);
  // }

  public function getAvailableCards() {
    $availableCards = [];
    foreach ($this->adios->models as $model) {
      if ($this->adios->getModel($model)->cards() != [])
        $availableCards[] = $this->adios->getModel($model)->cards();
    }
    // for each model->getDashboardCards, nasledne post processing
    return $this->adios->renderReturn($availableCards);
  }

  public function getCardContent($cardUid) {
    if (empty($cardUid)) {
      return "No UID.";
    } else {
      return "card {$cardUid}";
    }
  }

}
