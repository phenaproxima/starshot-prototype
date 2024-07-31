<?php

declare(strict_types=1);

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Recipe\InputCollector;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Implements hook_install_tasks().
 */
function starshot_installer_install_tasks(): array {
  return [
    // 'starshot_installer_choose_add_on_recipes' => [
      // We don't currently have the ability to present add-on recipes, so for
      // now this task doesn't do anything and is hidden from users.
      // @todo Fill this in after https://www.drupal.org/i/3450629 is fixed.
      // 'display_name' => t('Choose add-ons'),
    // ],
    'starshot_installer_uninstall_myself' => [
      // As a final task, this profile should uninstall itself.
    ],
  ];
}

/**
 * Implements hook_install_tasks_alter().
 */
function starshot_installer_install_tasks_alter(array &$tasks): void {
  $insert_before = function (string $key, array $additions) use (&$tasks): void {
    $key = array_search($key, array_keys($tasks), TRUE);
    if ($key === FALSE) {
      return;
    }
    // This isn't very clean, but it's the only way to positionally splice into
    // an associative (and therefore by definition unordered) array.
    $tasks_before = array_slice($tasks, 0, $key, TRUE);
    $tasks_after = array_slice($tasks, $key, NULL, TRUE);
    $tasks = $tasks_before + $additions + $tasks_after;
  };
  $insert_before('install_settings_form', [
    'starshot_installer_choose_template' => [
      // Because the choice of template is currently hard-coded, this should
      // not be presented to the user.
      // 'display_name' => t('Choose template'),
    ],
  ]);

  // Wrap the install_profile_modules() function, which returns a batch job, and
  // add all the necessary operations to apply the chosen template recipe.
  $tasks['install_profile_modules']['function'] = 'starshot_installer_apply_template';
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

    Drupal::configFactory()
      ->getEditable('project_browser.admin_settings')
      ->set('allow_ui_install', TRUE)
      ->save();
  }
}

/**
 * Presents the user which a choice of which template should set up the site.
 *
 * @param array $install_state
 *   An array of information about the current installation state.
 *
 * @see starshot_installer_apply_template()
 */
function starshot_installer_choose_template(array &$install_state): void {
  // For now, hard-code the choice to the main Starshot recipe. When more
  // choices are available, this should present a form whose submit handler
  // should set the `template` install parameter for
  // starshot_installer_apply_template() to act upon.
  $install_state['parameters']['template'] = Drupal::root() . '/recipes/starshot';
}

/**
 * Runs a batch job that applies the template recipe.
 *
 * @param array $install_state
 *   An array of information about the current installation state.
 *
 * @return array
 *   The batch job definition.
 */
function starshot_installer_apply_template(array &$install_state): array {
  $batch = install_profile_modules($install_state);

  $recipe = Recipe::createFromDirectory($install_state['parameters']['template']);
  Drupal::classResolver(InputCollector::class)->prepare($recipe);

  foreach (RecipeRunner::toBatchOperations($recipe) as $operation) {
    $batch['operations'][] = $operation;
  }
  return $batch;
}

/**
 * Uninstalls this install profile, as a final step.
 */
function starshot_installer_uninstall_myself(): void {
  Drupal::service(ModuleInstallerInterface::class)->uninstall([
    'starshot_installer',
  ]);
}
