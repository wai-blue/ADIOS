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

  private string $lastChangedAreaKeyName = '';

  private bool $areaKeyNameReseted = false;

  /**
   * Remove AREA from user dashboard data
   */
  private function removeDataFromGrid(int $areaIndex): void {
    unset($this->dashboardConfiguration['data'][$areaIndex]);
  }

  /**
   *  Remove AREA GRID keys 
   */
  private function removeGridAreas(array $splitedAreas, string $areaKeyName): array {
    foreach ($splitedAreas as $index => $areaName) {
      if ($areaName === $areaKeyName) {
        unset($splitedAreas[$index]);
      }
    }

    return $splitedAreas;
}

  /**
   * Remove AREA KEY from user dashboard grid settings
   */
  private function removeAreaFromGrid(string $areaKeyName, int $areaIndex): void {
    foreach ($this->dashboardConfiguration['grid'] as $gridAreaIndex =>  &$gridArea) {
      if (str_contains($gridArea, $areaKeyName)) {
        $splitedAreas = explode(' ', $gridArea);
        $splitedAreas = $this->removeGridAreas($splitedAreas, $areaKeyName);

        if (!empty($splitedAreas)) {
          $gridArea = implode(' ', $splitedAreas);
        } else {
          unset($this->dashboardConfiguration['grid'][$gridAreaIndex]);
        }
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

  private function getLastAreaKeyName(): string {
    return (string) end($this->currentAreaKeyNames);
  }

  private function addRow(): void {
    $lastAreaKeyName = $this->getLastAreaKeyName();

    if (!empty($this->dashboardConfiguration['grid'])) {
      $lastGridItem = reset($this->dashboardConfiguration['grid']);

      $rowGridSize = strlen($lastGridItem);
      $rowGridSize = $rowGridSize - substr_count($lastGridItem, ' ');

      $keysToInsert = array_fill(0, $rowGridSize, ++$lastAreaKeyName);
    } else {
      $keysToInsert = ['A'];
      $lastAreaKeyName = 'A';
    }

    $this->dashboardConfiguration['grid'][] = implode(' ', $keysToInsert);
    $this->insertKeyToData($lastAreaKeyName);
  }

  private function reOrderGrid(int $gridAreaCount, ?string $addedAreaKeyName = null): void {
    $incrementAreaNames = false;

    foreach ($this->dashboardConfiguration['grid'] as &$gridArea) {
      $splitedAreas = explode(' ', $gridArea);

      if (
        !$incrementAreaNames 
        && $addedAreaKeyName != null 
        && in_array($addedAreaKeyName, $splitedAreas)
      ) {
        $incrementAreaNames = true;
        $currentAreaIndex = array_search($addedAreaKeyName , $splitedAreas);
        $this->insertKeyToData($addedAreaKeyName);
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

  private function getLongestGridAreaSize(): int {
    $longestGridAreaSize = 0;
    foreach ($this->dashboardConfiguration['grid'] as &$gridArea) {
      $splitedAreasCount = count(explode(' ', $gridArea));

      if ($splitedAreasCount > $longestGridAreaSize) {
        $longestGridAreaSize = $splitedAreasCount;
      }
    }

    return $longestGridAreaSize;
  }

  private function reOrderGrid2(): void {
    $longestGridAreaSize = $this->getLongestGridAreaSize();

    foreach ($this->dashboardConfiguration['grid'] as &$gridArea) {
      $splitedAreas = explode(' ', $gridArea);
      $splitedAreasCount = count($splitedAreas);

      if ($splitedAreasCount < $longestGridAreaSize) {
        $lastChar = end($splitedAreas);

        for ($i=0;$i<($longestGridAreaSize - $splitedAreasCount);$i++) {
          $splitedAreas[] = $lastChar;
        }
      }

      $gridArea = implode(' ', $splitedAreas);
    }
  }

  private function correctGridAreaNames() {
    $nextChar = 'A';
    foreach ($this->dashboardConfiguration['grid'] as &$gridArea) {
      $splitedAreas = explode(' ', $gridArea);
      //$lastChar = 
      foreach ($splitedAreas as &$areaName) {
        if ($areaName != $nextChar) {
          $areaName = $nextChar;
        }
      }

      $gridArea = implode(' ', $splitedAreas);
    }
  }

  private function correctAreaKeysNames(int $gridAreaCount, string $startFromKeyName = 'A'): void {
    foreach ($this->dashboardConfiguration['grid'] as &$gridArea) {
      $splitedAreas = explode(' ', $gridArea);

      foreach ($splitedAreas as &$areaKeyName) {
        if ($areaKeyName <= $startFromKeyName) continue;

        $areaKeyName = $startFromKeyName;
        //$startFromKeyName = ++$areaKeyName;
      }

      if (count($splitedAreas) != $gridAreaCount) {
        $splitedAreas[] = end($splitedAreas);
      }

      $gridArea = implode(' ', $splitedAreas);
    }
  }

  private function incrementAreaNames(array &$splitedAreas, ?string $skipFromAreaIndex = null): void {
    // /$lastArea = "";
    foreach ($splitedAreas as $areaIndex => &$area) {
      if (
        $skipFromAreaIndex != null 
        && ($areaIndex <= $skipFromAreaIndex)
        //|| $this->lastChangedAreaKeyName == $area
      ) {
        continue;
      }
      
      $area++;

      $this->insertKeyToData($area);
    }

    //$this->lastChangedAreaKeyName = $lastArea;
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
      
        $this->lastChangedAreaKeyName = $areaKeyName;
        $this->lastChangedAreaKeyName++;
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
              $this->removeDataFromGrid($areaIndex);
              $this->reOrderGrid2();
              $this->correctGridAreaNames();
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
        case 'addRow':
          $this->addRow();
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
        case 'addEmptyGrid':
          $defaultDashboard = $dashboard->initDefaultDashboard(0, 0, []);
          $this->dashboardConfiguration = json_decode($defaultDashboard, TRUE);
        break;
      }
    }
var_dump($this->dashboardConfiguration['grid']);
//var_dump($this->dashboardConfiguration['data']);
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