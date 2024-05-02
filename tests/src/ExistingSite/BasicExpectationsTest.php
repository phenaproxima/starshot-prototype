<?php

declare(strict_types=1);

namespace Drupal\Tests\starshot\ExistingSite;

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
  }

}
