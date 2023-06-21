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

  # TODO: Nepouziva sa
  public function preRender(string $panel = ''): array
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

  public function getAvailableCards(): array
  {
    $availableCards = [];
    foreach ($this->adios->models as $model) {
      if ($this->adios->getModel($model)->cards() != [])
        $availableCards[] = $this->adios->getModel($model)->cards();
    }

    foreach ($availableCards as &$i) {
      foreach ($i as &$card) {
        $card['params_encoded'] = base64_encode(json_encode($card['params']));
      }
    }

    // for each model->getDashboardCards, nasledne post processing
    return $availableCards;
  }

  // TODO: Nepouziva sa
  public function getCardContent($cardUid): string
  {
    if (empty($cardUid)) {
      return "No UID.";
    } else {
      return "card {$cardUid}";
    }
  }

}
