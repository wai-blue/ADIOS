<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers\Desktop;

/**
 * 'Desktop/InstallUpgrades' action. Installs database upgrades of models in the ADIOS application.
 *
 * @package Components\Controllers\Desktop
 */
class InstallUpgrades extends \ADIOS\Core\Controller
{
  function render($params = [])
  {
    $contentHtml = "";
    $foreignKeysToInstall = [];

    foreach ($this->app->models as $modelName) {
      $model = $this->app->getModel($modelName);

      if ($model->hasAvailableUpgrades()) {
        $contentHtml .= "{$model->fullName}: ";
        try {
          $model->installUpgrades();
          $contentHtml .= "<span style='color:green'>OK</span><br/>";
        } catch (\ADIOS\Core\Exceptions\DBException $e) {
          $contentHtml .= "<span style='color:red'>" . $e->getMessage() . "</span><br/>";
        }
      } else if (!$model->hasSqlTable()) {
        $model->install();
        $foreignKeysToInstall[] = $modelName;
        $model->saveConfig('installed-version', max(array_keys($model->upgrades())));
        $contentHtml .= "{$model->fullName}: <span style='color:green'>SQL table created</span><br/>";
      } else if (!$model->isInstalled()) {
        $contentHtml .= "<span style='color:orange'>{$model->fullName}: Information about installed version was missing. Set to 0.</span><br/>";
        $model->saveConfig('installed-version', 0);
      }
    }

    foreach ($foreignKeysToInstall as $modelName) {
      $model = $this->app->getModel($modelName);
      $model->createSqlForeignKeys();
    }

    $html = "
      <div class='card shadow mb-4'>
        <div class='card-header py-3'>
          <h6 class='m-0 font-weight-bold text-primary'>Installing upgrades</h6>
        </div>
        <div class='card-body'>
          " . ($contentHtml == "" ? "Nothing to be done here." : $contentHtml) . "
        </div>
      </div>

    ";

    return $html;
  }
}
