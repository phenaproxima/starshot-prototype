<?php

declare(strict_types=1);

namespace Drupal\starshot_installer;

use Composer\Script\Event;
use Drupal\Component\Serialization\Yaml;

/**
 * Contains Composer scripts used during Starshot installation.
 */
final class ScriptHandler {

  /**
   * Writes Drush configuration for installing Starshot.
   *
   * @param \Composer\Script\Event $event
   *   The event object.
   */
  public static function configureDrush(Event $event): void {
    $data = [];
    $arguments = $event->getArguments();

    // If SQLite is available, use a SQLite database by default. Otherwise,
    // Drush will prompt for a database URL during installation.
    if (extension_loaded('pdo_sqlite')) {
      $arguments[0] ??= 'sqlite://db.sqlite';
    }
    if ($arguments) {
      $data['command']['site']['install']['options']['db-url'] = $arguments[0];
    }
    file_put_contents('drush-install.yml', Yaml::encode($data));
  }

}
