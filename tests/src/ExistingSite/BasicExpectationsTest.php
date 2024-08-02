<?php

declare(strict_types=1);

namespace Drupal\Tests\starshot\ExistingSite;

use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * @group starshot
 */
class BasicExpectationsTest extends ExistingSiteBase {

  /**
   * Tests basic expectations of a successful Starshot install.
   */
  public function testBasicExpectations(): void {
    $this->drupalGet('/');

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $assert_session->elementAttributeContains('css', 'meta[name="Generator"]', 'content', 'Drupal');

    // The installation profile should not be installed.
    $this->assertFalse($this->container->getParameter('install_profile'));

    // Ensure that there are non-core extensions installed, which proves that
    // recipes were applied during site installation.
    $this->assertContribInstalled($this->container->get(ModuleExtensionList::class));
    $this->assertContribInstalled($this->container->get(ThemeExtensionList::class));
  }

  /**
   * Asserts that any number of contributed extensions are installed.
   *
   * @param \Drupal\Core\Extension\ExtensionList $list
   *   An extension list.
   */
  private function assertContribInstalled(ExtensionList $list): void {
    $core_dir = $this->container->getParameter('app.root') . '/core';

    foreach ($list->getList() as $info) {
      // If this extension isn't part of core, great! We're done.
      if (!str_starts_with($info->getPath(), $core_dir)) {
        return;
      }
    }
    $this->fail('No contributed extensions are installed.');
  }

}
