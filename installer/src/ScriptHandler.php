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
   * Sets the database URL in `drush.yml`, if a URL is supplied.
   *
   * @param \Composer\Script\Event $event
   *   The event object.
   */
  public static function setDatabaseUrl(Event $event): void {
    $file = getcwd() . '/drush/drush.yml';
    if (file_exists($file)) {
      $data = file_get_contents($file);
      $data = Yaml::decode($data);
    }
    else {
      $data = [];
    }

    $arguments = $event->getArguments();

    // If SQLite is available, use a SQLite database by default. Otherwise,
    // Drush will prompt for a database URL during installation.
    if (extension_loaded('pdo_sqlite')) {
      $arguments[0] ??= 'sqlite://localhost/../db.sqlite';
    }
    if ($arguments) {
      $data['commands']['site']['install']['options']['db-url'] = $arguments[0];
    }
    file_put_contents($file, Yaml::encode($data));
  }

}
