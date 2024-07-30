<?php

declare(strict_types=1);

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Implements hook_install_tasks_alter().
 */
function starshot_installer_install_tasks_alter(&$tasks, $install_state) {
  $recipe_tasks = [
    'starshot_installer_apply_recipes' => [
      'run' => INSTALL_TASK_RUN_IF_REACHED,
      'display_name' => t('Apply recipes'),
    ]
  ];
  $key = array_search('install_select_profile', array_keys($tasks), TRUE);
  $tasks = array_slice($tasks, 0, $key, TRUE) +
    $recipe_tasks +
    array_slice($tasks, $key, NULL, TRUE);

  $recipe_tasks = [
    'starshot_installer_uninstall_myself' => [
      // As a final task, this profile should uninstall itself.
    ],
  ];
  $key = array_search('install_finished', array_keys($tasks), TRUE);
  $tasks = array_slice($tasks, 0, $key, TRUE) +
    $recipe_tasks +
    array_slice($tasks, $key, NULL, TRUE);
}

/**
 * Implements hook_form_alter() for install_settings_form.
 *
 * @see \Drupal\Core\Installer\Form\SiteSettingsForm
 */
function starshot_installer_form_install_settings_form_alter(array &$form): void {
  // Default to SQLite, if available, because it doesn't require any additional
  // configuration.
  $sqlite = 'Drupal\sqlite\Driver\Database\sqlite';
  if (array_key_exists($sqlite, $form['driver']['#options']) && extension_loaded('pdo_sqlite')) {
    $form['driver']['#default_value'] = $sqlite;
  }
}

/**
 * Implements hook_form_alter() for install_configure_form.
 */
function starshot_installer_form_install_configure_form_alter(array &$form): void {
  ['composer' => $composer, 'rsync' => $rsync] = Drupal::configFactory()
    ->get('package_manager.settings')
    ->get('executables');

  $finder = new ExecutableFinder();
  $finder->addSuffix('.phar');
  $composer ??= $finder->find('composer');
  $rsync ??= $finder->find('rsync');

  $form['package_manager'] = [
    '#type' => 'fieldset',
    '#title' => t('Package Manager settings (advanced)'),
    '#description' => t("To install extensions in the administrative interface, Drupal needs to know where Composer and <code>rsync</code> are. This will be auto-detected if possible. If you leave these blank, you can still browse for extensions but you'll need to use the command line to install them."),
  ];
  $form['package_manager']['composer'] = [
    '#type' => 'textfield',
    '#title' => t('Full path to <code>composer</code> or <code>composer.phar</code>'),
    '#default_value' => $composer,
  ];
  $form['package_manager']['rsync'] = [
    '#type' => 'textfield',
    '#title' => t('Full path to <code>rsync</code>'),
    '#default_value' => $rsync,
  ];
  $form['#submit'][] = '_starshot_installer_install_configure_form_submit';
}

/**
 * Submit callback for install_configure_form.
 *
 * Sets the full paths to Composer and rsync, if available, and enables
 * installing projects via the Project Browser UI.
 */
function _starshot_installer_install_configure_form_submit(array &$form, FormStateInterface $form_state): void {
  $composer = $form_state->getValue('composer');
  $rsync = $form_state->getValue('rsync');

  if ($composer && $rsync) {
    Drupal::configFactory()
      ->getEditable('package_manager.settings')
      ->set('executables', [
        'composer' => $composer,
        'rsync' => $rsync,
      ])
      ->save();
  }
}

/**
 * Apply Starshot recipes.
 */
function starshot_installer_apply_recipes(&$install_state) {
  $install_state['parameters']['recipe'] = 'recipes/starshot';
}

/**
 * Uninstalls this install profile, as a final step.
 */
function starshot_installer_uninstall_myself(): void {
  Drupal::service(ModuleInstallerInterface::class)->uninstall([
    'starshot_installer',
  ]);
}
