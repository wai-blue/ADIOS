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
  public string $twigTemplate = "Core/UI/Dashboard";

  public function __construct($adios, array $params = []) {
    $this->adios = $adios;

    $this->params = parent::params_merge([
    ], $params);

    $this->params["dashboardCards"] = $this->getUserDashboardCards();

    foreach ($this->params['dashboardCards'] as &$i) {
      foreach ($i as &$card) {
        $card['params_encoded'] = base64_encode(json_encode($card['params']));
      }
    }
  }

  public function getUserDashboardCards(): array {
    $useDashboard = $this->adios->config['dashboard-'.$this->adios->userProfile['id'].'0'];

    if (empty($useDashboard)) {
      $this->initDefaultDashboard();
    }

    return json_decode($useDashboard, TRUE);
  }

  public function initDefaultDashboard(): void {
    $cards = $this->getAvailableCards();

    foreach ($cards as &$i) {
      foreach ($i as &$card) {
        $card['left'] = true;
        $card['is_active'] = true;
        $card['order'] = 999;
      }
    }

    $this->adios->saveConfig([json_encode($cards)], 'dashboard-' . $this->adios->userProfile['id']);
  }

  public function getAvailableCards(): array {
    $availableCards = [];

    foreach ($this->adios->models as $model) {
      if ($this->adios->getModel($model)->cards() != []) {
        $availableCards[] = $this->adios->getModel($model)->cards();
      }
    }

    return $availableCards;
  }

  // TODO: Nepouziva sa
  /*public function getCardContent($cardUid): string {
    if (empty($cardUid)) {
      return "No UID.";
    } else {
      return "card {$cardUid}";
    }
  }*/

  public function getSettingsInputs($availableCards): array {
    $forms = [];

    foreach ($availableCards as $card) {
      $cardForm = [];
      $card_key = array_search($card, $availableCards);

      $config = $this->getUserDashboardCards();
      if (!empty($config[0][$card_key])) $config = $config[0][$card_key];

      $cardForm[] = $this->addView(
        "Input",
        array_merge(
          [
            "type" => "bool",
            "title" => 'Located left?',
            'value' => $config['left']
          ],
          ['required' => true]
        )
      )->render();

      $cardForm[] = $this->addView(
        "Input",
        array_merge(
          [
            "type" => "bool",
            "title" => 'Is active?',
            'value' => $config['is_active']
          ],
          ['required' => true]
        )
      )->render();

      $cardForm[] = $this->addView(
        "Input",
        array_merge(
          [
            "type" => "int",
            "value" => $config['order'],
            "title" => 'Order',
          ],
          ['required' => true]
        )
      )->render();
      
      $forms[] = $cardForm;
    }

    return $forms;
  }

  public function getTwigParams(): array {
    return array_merge(
      $this->params,
      [
        'view' => $this->adios->view
      ]
    );
  }

}
