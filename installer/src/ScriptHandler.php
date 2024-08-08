<?php

declare(strict_types=1);

namespace Drupal\drupal_cms_installer;

use Drupal\Component\Serialization\Yaml;

/**
 * Contains Composer scripts used during Starshot installation.
 */
final class ScriptHandler {

  /**
   * Writes Drush configuration for installing Starshot.
   */
  public static function configureDrush(): void {
    // If DDEV is managing site settings, it's probably already set up the
    // database, so we don't need to do anything else here.
    if (getenv('IS_DDEV_PROJECT') && file_exists('web/sites/default/settings.ddev.php')) {
      return;
    }
    $data = [];
    $db_url = getenv('DB');

    // If SQLite is available, use a SQLite database by default. Otherwise,
    // Drush will prompt for a database URL during installation.
    if (extension_loaded('pdo_sqlite') && empty($db_url)) {
      $db_url = 'sqlite://db.sqlite';
    }
    if ($db_url) {
      $data['command']['site']['install']['options']['db-url'] = $db_url;
    }
    file_put_contents('drush-install.yml', Yaml::encode($data));
  }

}
