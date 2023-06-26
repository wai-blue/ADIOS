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

  public function getUserDashboardConfig() {
    if ($this->adios->config['dashboard-'.$this->adios->userProfile['id'].'0'] == null || json_decode($this->adios->config['dashboard-'.$this->adios->userProfile['id'].'0']) == null) {
      $this->initUserDashboardConfig();
    }
    return
      $this->adios->config['dashboard-'.$this->adios->userProfile['id'].'0']
    ;
  }

  public function initUserDashboardConfig(): void
  {
    $cards = $this->getAvailableCards();

    foreach ($cards as &$i) {
      foreach ($i as &$card) {
        $card['left'] = true;
        $card['is_active'] = false;
        $card['order'] = 999;
      }
    }

    $this->adios->saveConfig([json_encode($cards)], 'dashboard-'.$this->adios->userProfile['id']);
  }

  public function getAvailableCards(): array
  {
    $availableCards = [];
    foreach ($this->adios->models as $model) {
      if ($this->adios->getModel($model)->cards() != [])
        $availableCards[] = $this->adios->getModel($model)->cards();
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

  public function getSettingsInputs($availableCards): array {
    $forms = [];

    foreach ($availableCards as $card) {
      $card_form = [];
      $card_form[] = $this->addView(
        "Input",
        array_merge(
          [
            "type" => "varchar",
            "value" => false,
            "enum_values" => ['Right', 'Left'],
            "title" => 'Column'
          ],
          ['required' => true]
        )
      )->render();

      $card_form[] = $this->addView(
        "Input",
        array_merge(
          [
            "type" => "bool",
            "value" => false,
            "title" => 'Is active?'
          ],
          ['required' => true]
        )
      )->render();

      $card_form[] = $this->addView(
        "Input",
        array_merge(
          [
            "type" => "int",
            "value" => "999",
            "title" => 'Order'
          ],
          ['required' => true]
        )
      )->render();
      $forms[] = $card_form;
    }

    return $forms;
  }

}
