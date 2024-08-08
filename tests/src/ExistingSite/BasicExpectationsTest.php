<?php

declare(strict_types=1);

namespace Drupal\Tests\drupal_cms\ExistingSite;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * @group drupal_cms
 */
class BasicExpectationsTest extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Disable Antibot because it prevents non-JS functional tests from logging
    // in.
    $this->container->get(ConfigFactoryInterface::class)
      ->getEditable('antibot.settings')
      ->set('form_ids', [])
      ->save();
  }

  /**
   * Tests basic expectations of a successful Drupal CMS install.
   */
  public function testBasicExpectations(): void {
    $this->drupalGet('/');

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $assert_session->elementAttributeContains('css', 'meta[name="Generator"]', 'content', 'Drupal');

    // The installer should have uninstalled itself.
    $this->assertFalse($this->container->getParameter('install_profile'));

    // Ensure that there are non-core extensions installed, which proves that
    // recipes were applied during site installation.
    $this->assertContribInstalled($this->container->get(ModuleExtensionList::class));
    $this->assertContribInstalled($this->container->get(ThemeExtensionList::class));
  }

  /**
   * @testWith ["page"]
   *   ["blog"]
   *   ["event"]
   */
  public function testContentTypeBasics(string $type): void {
    $node = $this->createNode(['type' => $type]);
    $url = $node->toUrl();

    // All content types should have pretty URLs.
    $this->assertNotSame('/node/' . $node->id(), $url->toString());

    // Content editors should be able to clone all content types.
    $editor = $this->createUser();
    $editor->addRole('content_editor')->save();
    $this->drupalLogin($editor);

    $this->drupalGet($url);
    $this->getSession()->getPage()->clickLink('Clone');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Asserts that any number of contributed extensions are installed.
   *
   * @param \Drupal\Core\Extension\ExtensionList $list
   *   An extension list.
   */
  private function assertContribInstalled(ExtensionList $list): void {
    $core_dir = $this->container->getParameter('app.root') . '/core';

    foreach (array_keys($list->getAllInstalledInfo()) as $name) {
      // If the extension isn't part of core, great! We're done.
      if (!str_starts_with($list->getPath($name), $core_dir)) {
        return;
      }
    }
    $this->fail('No contributed extensions are installed.');
  }

}
