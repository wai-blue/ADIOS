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

  public function __construct($adios, array $params = []) {
    $this->adios = $adios;

    $this->params = parent::params_merge([
    ], $params);

    $this->params["availC"] = $this->getUserDashboardConfig();

    foreach ($this->params['availC'] as &$i) {
      foreach ($i as &$card) {
        $card['params_encoded'] = base64_encode(json_encode($card['params']));
      }
    }
  }

  public function getUserDashboardConfig(): array {
    $userDashboardConfig = $this->adios->config['dashboard-'.$this->adios->userProfile['id'].'0'];

    if ($userDashboardConfig == null || json_decode($userDashboardConfig) == null) {
      $this->initUserDashboardConfig();
    }

    return json_decode($userDashboardConfig, TRUE);
  }

  public function initUserDashboardConfig(): void {
    $cards = $this->getAvailableCards();

    foreach ($cards as &$i) {
      foreach ($i as &$card) {
        $card['left'] = true;
        $card['is_active'] = false;
        $card['order'] = 999;
      }
    }

    $this->adios->saveConfig([json_encode($cards)], 'dashboard-' . $this->adios->userProfile['id']);
  }

  public function getAvailableCards(): array {
    $availableCards = [];
    foreach ($this->adios->models as $model) {
      if ($this->adios->getModel($model)->cards() != [])
        $availableCards[] = $this->adios->getModel($model)->cards();
    }

    // for each model->getDashboardCards, nasledne post processing
    return $availableCards;
  }

  // TODO: Nepouziva sa
  public function getCardContent($cardUid): string {
    if (empty($cardUid)) {
      return "No UID.";
    } else {
      return "card {$cardUid}";
    }
  }

  public function getSettingsInputs($availableCards): array {
    $forms = [];

    foreach ($availableCards as $card) {
      $cardForm = [];
      $card_key = array_search($card, $availableCards);

      $config = $this->getUserDashboardConfig();
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

}
