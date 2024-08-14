<?php

namespace Drupal\starshot_installer\Routing;

use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if (InstallerKernel::installationAttempted()) {
      // Always allow access if installation is currently being attempted.
      $routes = [
        'project_browser.api_get_categories',
        'project_browser.api_project_get_all',
        'project_browser.browse',
      ];
      foreach ($routes as $route) {
        $route = $collection->get($route);
        if ($route) {
          $route->setRequirements([
            '_access' => 'TRUE',
          ]);
        }
      }
    }
  }

}
