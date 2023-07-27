<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Actions\UI\Dashboard;

/**
 * @package UI\Actions
 */
class Configure extends \ADIOS\Core\Action {
  public string $twigTemplate = "ADIOS/Templates/UI/Dashboard/Configure";

  private array $dashboardConfiguration = [];
  private array $currentAreaKeyNames = [];

  private function removeDataFromGrid(int $areaIndex): void {
    unset($this->dashboardConfiguration['data'][$areaIndex]);
  }

  private function removeAreaFromGrid(string $areaKeyName, int $areaIndex): void {
    foreach ($this->dashboardConfiguration['grid'] as &$gridArea) {
      if (str_contains($gridArea, $areaKeyName)) {
        $splitedAreas = explode(' ', $gridArea);
        $areaToRemoveIndex = array_search($areaKeyName, $splitedAreas);
        $splitedAreasCount = count($splitedAreas);

        if ($areaToRemoveIndex == ($splitedAreasCount - 1)) {
          $newIndex = $areaToRemoveIndex - 1;
        } else {
          $newIndex = $areaToRemoveIndex + 1;
        }

        $splitedAreas[$areaToRemoveIndex] = $splitedAreas[$newIndex];
        $gridArea = implode(' ', $splitedAreas);

        $this->removeDataFromGrid($areaIndex);
        break;
      }
    }
  }

  private function setCurrentAreaKeyNames() {
    foreach ($this->dashboardConfiguration['data'] as $area) {
      $this->currentAreaKeyNames[] = $area['key'];
    }
  }

  private function insertKeyToData(string $areaKeyName): void {
    if (!in_array($areaKeyName, $this->currentAreaKeyNames)) {
      $this->dashboardConfiguration['data'][] = [
        'key' => $areaKeyName,
        'cards' => []
      ];
    }
  }

  private function reOrderGrid(int $gridAreaCount, ?string $addedAreKeyName = null): void {
    $incrementAreaNames = false;

    foreach ($this->dashboardConfiguration['grid'] as &$gridArea) {
      $splitedAreas = explode(' ', $gridArea);

      if (
        !$incrementAreaNames 
        && $addedAreKeyName != null 
        && in_array($addedAreKeyName, $splitedAreas)
      ) {
        $incrementAreaNames = true;
        $currentAreaIndex = array_search($addedAreKeyName , $splitedAreas);
        $this->insertKeyToData($addedAreKeyName);
        $this->incrementAreaNames($splitedAreas, $currentAreaIndex);
      } else if ($incrementAreaNames) {
        $this->incrementAreaNames($splitedAreas);
      }

      if (count($splitedAreas) != $gridAreaCount) {
        $splitedAreas[] = end($splitedAreas);
      }

      $gridArea = implode(' ', $splitedAreas);
    }
  }

  private function incrementAreaNames(array &$splitedAreas, ?string $skipFromAreaIndex = null): void {
    foreach ($splitedAreas as $areaIndex => &$area) {
      if ($skipFromAreaIndex != null && ($areaIndex <= $skipFromAreaIndex)) continue;
      $area++;

      $this->insertKeyToData($area);
    }
  }

  private function increaseAreaInGrid(string $areaKeyName, int $areaIndex) {
    foreach ($this->dashboardConfiguration['grid'] as &$gridArea) {
      if (str_contains($gridArea, $areaKeyName)) {
        $splitedAreas = explode(' ', $gridArea);
        $currentAreaIndex = array_search($areaKeyName, $splitedAreas);

        array_splice($splitedAreas, $currentAreaIndex + 1, 0, $areaKeyName);
        $gridArea = implode(' ', $splitedAreas);
        $gridAreaCount = count($splitedAreas);

        $this->reOrderGrid($gridAreaCount);
        break;
      }
    }
  }

  private function addAreaToGrid(string $areaKeyName, int $areaIndex) {
    foreach ($this->dashboardConfiguration['grid'] as &$gridArea) {
      if (str_contains($gridArea, $areaKeyName)) {
        $splitedAreas = explode(' ', $gridArea);

        array_splice($splitedAreas, $areaIndex + 1, 0, ++$areaKeyName);
        $gridArea = implode(' ', $splitedAreas);
        $gridAreaCount = count($splitedAreas);

        $this->reOrderGrid($gridAreaCount, $areaKeyName);
        break;
      }
    }
  }

  /*private function decreaseAreaInGrid(string $areaKeyName, int $areaIndex) {
    foreach ($this->dashboardConfiguration['grid'] as &$gridArea) {
      if (str_contains($gridArea, $areaKeyName)) {
        $splitedAreas = explode(' ', $gridArea);
        $currentAreaIndex = array_search($areaKeyName, $splitedAreas);

        array_splice($splitedAreas, $currentAreaIndex - 1, 0, $areaKeyName);
        $gridArea = implode(' ', $splitedAreas);
        $gridAreaCount = count($splitedAreas);

        $this->reOrderGrid($gridAreaCount);
        break;
      }
    }
  }*/

  function preRender() {
    $dashboard = new \ADIOS\Core\Views\Dashboard($this->adios, $this->params);

    $availablePresets = $dashboard->getAvailablePresets();
    $this->dashboardConfiguration = json_decode($dashboard->getUserDashboard(), true);
    $this->setCurrentAreaKeyNames();

    if (isset($this->params['configurationAction'])) {
      switch ($this->params['configurationAction']) {
        case 'delete':
          foreach ($this->dashboardConfiguration['data'] as $areaIndex => $area) {
            if ($areaIndex == (int) $this->params['areaIndexToDelete']) {
              $this->removeAreaFromGrid($area['key'], $areaIndex);
              break;
            }
          }
        break;
        case 'add':
          foreach ($this->dashboardConfiguration['data'] as $areaIndex => $area) {
            if ($areaIndex == (int) $this->params['areaIndex']) {
              $this->addAreaToGrid($area['key'], $areaIndex);
              break;
            }
          }
        break;
        case 'increase':
          foreach ($this->dashboardConfiguration['data'] as $areaIndex => $area) {
            if ($areaIndex == (int) $this->params['areaIndex']) {
              $this->increaseAreaInGrid($area['key'], $areaIndex);
              break;
            }
          }
        break;
        /*case 'decrease':
          foreach ($this->dashboardConfiguration['data'] as $areaIndex => $area) {
            if ($areaIndex == (int) $this->params['areaIndex']) {
              $this->decreaseAreaInGrid($area['key'], $areaIndex);
              $dashboard->saveConfiguration(
                json_encode($this->dashboardConfiguration),
                0
              );
              break;
            }
          }
        break;*/
        case 'restoreDefaultGrid':
          $defaultDashboard = $dashboard->initDefaultDashboard();
          $this->dashboardConfiguration = json_decode($defaultDashboard, TRUE);
        break;
      }
    }

    $dashboard->saveConfiguration(
      json_encode($this->dashboardConfiguration),
      0
    );

    return [
      'availablePresets' => $availablePresets,
      'dashboardConfiguration' => $this->dashboardConfiguration
    ];
  }
}