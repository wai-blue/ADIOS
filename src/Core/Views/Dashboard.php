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

    $this->params['title'] = 'Dashboard'; # TODO: Localization
    $this->params['saveAction'] = '/UI/Dashboard/SaveConfig';
    $this->params['addCardsAction'] = '/UI/Dashboard/AddCards';
    $this->params["dashboardConfiguration"] = $this->getUserDashboard($_GET['preset'] ?? 0);
    $this->params['preset'] = $_GET['preset'] ?? 0; # TODO: If preset isn't specified, use the last used
    $this->params['availablePresets'] = $this->getAvailablePresets();
    $this->params['availableCards'] = $this->getAvailableCards($this->params['preset']);



    if (!in_array($this->params['preset'], $this->params['availablePresets'])) {
      $this->params['availablePresets'][] = $this->params['preset'];
    }

    foreach ($this->params['dashboardConfiguration']['data'] as &$area) {
      foreach ($area['cards'] as &$card) {
        $card['params_encoded'] = base64_encode(json_encode($card['params']));
      }

      $area['cards'] = array_values($area['cards']) ?? [];
    }
  }

  public function getUserDashboard($preset = 0): bool|string|array
  {
    if ($preset < 0)
      return $this->adios->renderReturn(400);

    $userDashboard = $this->adios->config['dashboard-'.$this->adios->userProfile['id']. '-' . $preset .'0']; # TODO: Odstranit nulu

    if (empty($userDashboard)) {
      $userDashboard = $this->initDefaultDashboard($_GET['preset'] ?? 0);
    }

    return json_decode($userDashboard, TRUE);
  }

  public function initDefaultDashboard($preset = 0): string
  {
    $areas = 5;
    $configuration = ['grid' => ['A B', 'C C', 'D E'] ];
    $configuration['data'] = array_fill(0, $areas, array());

    foreach ($configuration['data'] as $key => &$area) {
      $area['key'] = chr(((int) $key) + 65);
      $area['cards'] = [];
    }

    $this->adios->saveConfig([json_encode($configuration)], 'dashboard-' . $this->adios->userProfile['id'] . '-' . $preset);
    return json_encode($configuration);
  }

  public function addCardsToConfiguration(array $cards, int $preset, string $area): bool|string
  {
    $userConfiguration = $this->getUserDashboard($preset);
    $areaCards = $userConfiguration['data'][ord($area) - 65]['cards'];

    $availableCards = $this->getAvailableCards();
    foreach ($availableCards as $card) {
      if (in_array($card['action'], $cards)) {
        $areaCards[] = json_decode(json_encode($card), true);
      }
    }

    $userConfiguration['data'][ord($area) - 65]['cards'] = $areaCards;

    return $this->saveConfiguration(json_encode($userConfiguration), $preset);
  }

  public function saveConfiguration($configuration, $preset): bool|string
  {
    # TODO: Maybe vulnerable against SQL Injection etc.? $_POST['configuration'] goes straight into database...
    $this->adios->saveConfig([$configuration], 'dashboard-' . $this->adios->userProfile['id'] . '-' . $preset);
    return $this->adios->renderReturn(200);
  }

  public function getAvailableCards(int $preset = -1): array {
    $availableCards = [];
    $usedCards = [];

    if ($preset != -1) {
      foreach($this->getUserDashboard($preset)['data'] as $area) {
        foreach ($area['cards'] as $card) {
          $usedCards[] = $card['action'];
        }
      }
    }

    foreach ($this->adios->models as $model) {
      foreach ($this->adios->getModel($model)->cards() as $card) {
        if (!in_array($card['action'], $usedCards)) {
          $availableCards[] = $card;
        }
      }
    }

    return $availableCards;
  }

  public function getAvailablePresets(): array {
    $presets = [0];

    $i = 1;
    while (!empty($this->adios->config['dashboard-'.$this->adios->userProfile['id']. '-' . $i .'0'])) {
      $presets[] = $i;
      $i++;
    }

    return $presets;
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

      $config = $this->getUserDashboard();
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
