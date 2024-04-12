<?php

namespace Drupal\starshot\Plugin\ConfigAction;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[ConfigAction(
  id: 'editor:addPlugin',
  admin_label: new TranslatableMarkup('Add a plugin to a CKEditor 5 editor'),
  entity_types: ['editor'],
)]
final class AddCKEditor5Plugin implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    private readonly CKEditor5PluginManagerInterface $pluginManager,
    private readonly string $pluginId,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get(ConfigManagerInterface::class),
      $container->get(CKEditor5PluginManagerInterface::class),
      $plugin_id,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    $editor = $this->configManager->loadConfigEntityByName($configName);
    assert($editor instanceof EditorInterface);

    if ($editor->getEditor() !== 'ckeditor5') {
      throw new ConfigActionException("The $this->pluginId config action only works with CKEditor 5 editors.");
    }

    $plugin_id = $value['id'];
    $plugin = $this->pluginManager->getPlugin($plugin_id, $editor);
    if ($plugin instanceof CKEditor5PluginConfigurableInterface) {
      $plugin->setConfiguration($value['configuration']);

      $settings = $editor->getSettings();
      $settings['plugins'][$plugin_id] = $plugin->getConfiguration();
      $editor->setSettings($settings)->save();
    }
    else {
      throw new ConfigActionException("The '$plugin_id' plugin is not configurable.");
    }
  }

}
