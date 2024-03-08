<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Controllers;

/**
 * 'Desktop' action. Renders the ADIOS application's desktop.
 *
 * This is the default action rendered when the ADIOS application is open via a URL.
 * The desktop is divided into following visual parts:
 *   * Left sidebar
 *   * Notification and profile information area on the top of the screen
 *   * The main content area
 *
 * Action can be configured to render another action in the main content area.
 *
 * @package Components\Controllers
 */
class Printer extends \ADIOS\Core\Controller {

 public string $contentController = '';

  function __construct(\ADIOS\Core\Loader $adios, array $params = [])
  {
    parent::__construct($adios, $params);

    $this->contentController = $params['contentController'] ?? '';

    $this->params['print'] = TRUE;
    $this->params['displayMode'] = \ADIOS\Core\ViewWithController::DISPLAY_MODE_DESKTOP;
  }

  // public static function overrideConfig($config, $params)
  // {
  //   $config['hideDesktop'] = TRUE;

  //   return $config;
  // }

  public function getViewParams() {
    if (
      !empty($this->contentController)
      && $this->contentController != 'Printer'
    ) {
      $contentHtml = $this->adios->render($this->contentController, $this->params);
    } else {
      $contentHtml = '';
    }

    return [
      "contentHtml" => $contentHtml,
    ];
  }
}
