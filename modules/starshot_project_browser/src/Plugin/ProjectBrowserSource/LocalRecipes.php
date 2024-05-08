<?php

declare(strict_types=1);

namespace Drupal\starshot_project_browser\Plugin\ProjectBrowserSource;

use Drupal\Component\Serialization\Yaml;
use Drupal\project_browser\Plugin\ProjectBrowserSourceBase;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @ProjectBrowserSource(
 *   id = "local_recipes",
 *   label = @Translation("Recipes"),
 *   description = @Translation("Shows featured recipes in the local file system."),
 * )
 */
final class LocalRecipes extends ProjectBrowserSourceBase {

  public function __construct(
    private readonly string $appRoot,
    mixed ...$arguments,
  ) {
    parent::__construct(...$arguments);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->getParameter('app.root'),
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects(array $query = []): ProjectsResultsPage {
    $list = [];

    $finder = Finder::create()
      ->in($this->appRoot . '/../recipes')
      ->files()
      ->name('recipe.yml')
      ->depth(1);

    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($finder as $file) {
      $id = basename($file->getPath());

      if ($id === 'starshot_multilingual') {
        $recipe = Yaml::decode($file->getContents());

        $list[] = new Project(
          id: $id,
          logo: [],
          isCompatible: TRUE,
          isMaintained: TRUE,
          isCovered: TRUE,
          isActive: TRUE,
          starUserCount: 0,
          projectUsageTotal: 0,
          machineName: $id,
          body: [
            'value' => $recipe['description'] ?? '',
          ],
          title: $recipe['name'],
          status: 1,
          changed: 0,
          created: 0,
          author: [],
          composerNamespace: 'drupal/' . $id,
          type: 'drupal-recipe',
          commands: $this->createCommands($file),
        );
      }
    }
    return new ProjectsResultsPage(0, $list, 'Recipes', $this->getPluginId(), FALSE);
  }

  private function createCommands(SplFileInfo $file): string {
    $message = '<p>' . $this->t('To apply this recipe, run the following at the command line:') . '</p>';
    $message .= '<code>';
    $message .= sprintf('php %s/core/scripts/drupal recipe %s', $this->appRoot, $file->getRealPath());
    $message .= '</code>';

    return (string) $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories(): array {
    return [];
  }

}
