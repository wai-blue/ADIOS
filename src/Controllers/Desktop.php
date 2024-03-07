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
class Desktop extends \ADIOS\Core\Controller {

  // public string $twigTemplate = "App/Core/Views/Desktop";
  // public string $contentController = '';

  // function __construct(\ADIOS\Core\Loader $adios, array $params = [])
  // {
  //   parent::__construct($adios, $params);

  //   $this->contentController = $params['contentController'] ?? '';
  // }

  public function addSidebarItem($widget, $item) {
    $item['___widgetClassName'] = get_class($widget);
    $this->viewParams['sidebar']['items'][] = $item;
  }

  public function getViewParams(): array {
    $this->viewParams = $this->adios->params;

    foreach ($this->adios->widgets as $widget) {
      $widget->onBeforeDesktopParams($this);
    }

    $topRightMenu = ["items" => []];

    $topRightMenu["items"][] = [
      "faIcon" => "fas fa-user",
      "text" => $this->translate("My profile"),
      "onclick" => "
        ADIOS.renderWindow(
          'MyProfile',
          '',
          function() {
            setTimeout(function() {
              window.location.reload();
            }, 10);
          }
        );
      ",
    ];

    $topRightMenu["items"][] = [
      "faIcon" => "fas fa-window-restore",
      "text" => $this->translate("Open new tab"),
      "onclick" => "window.open('{$this->adios->config['url']}');",
    ];

    $this->viewParams["topRightMenu"] = $topRightMenu;

    return $this->viewParams;
  }
}
