<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Database\Database;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;

/**
 * Implements hook_install_tasks().
 */
function starshot_installer_install_tasks(): array {
  return [
    'starshot_installer_apply_recipes' => [
      'type' => 'batch',
      'display_name' => t('Apply recipes'),
    ],
    'starshot_installer_uninstall_myself' => [
      // As a final task, this profile should uninstall itself.
    ],
  ];
}

/**
 * Implements hook_form_alter() for install_settings_form.
 *
 * @see \Drupal\Core\Installer\Form\SiteSettingsForm
 */
function starshot_installer_form_install_settings_form_alter(array &$form): void {
  $connection_info = Database::getAllConnectionInfo();
  // If there's already a connection defined, don't interfere.
  if ($connection_info) {
    return;
  }

  // Default to SQLite, if available, because it doesn't require any additional
  // configuration.
  $sqlite = 'Drupal\sqlite\Driver\Database\sqlite';
  if (array_key_exists($sqlite, $form['driver']['#options']) && extension_loaded('pdo_sqlite')) {
    $form['driver']['#default_value'] = $sqlite;
  }
}

/**
 * Runs a batch job that applies all of the Starshot recipes.
 *
 * @return array
 *   The batch job definition.
 */
function starshot_installer_apply_recipes(): array {
  $batch = new BatchBuilder();
  $batch->setTitle(t('Applying recipes'));

  $project_root = InstalledVersions::getRootPackage();
  $recipe = Recipe::createFromDirectory($project_root['install_path'] . '/recipes/starshot');

  foreach (RecipeRunner::toBatchOperations($recipe) as [$callback, $arguments]) {
    $batch->addOperation($callback, $arguments);
  }
  return $batch->toArray();
}

/**
 * Uninstalls this install profile, as a final step.
 */
function starshot_installer_uninstall_myself(): void {
  \Drupal::service(ModuleInstallerInterface::class)->uninstall([
    'starshot_installer',
  ]);
}
