<?php

namespace Drupal\starshot\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[ConfigAction(
  id: 'delete',
  admin_label: new TranslatableMarkup('Delete a config entity'),
)]
final class Delete implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(private readonly ConfigManagerInterface $configManager) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get(ConfigManagerInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    $this->configManager->loadConfigEntityByName($configName)?->delete();
  }

}
