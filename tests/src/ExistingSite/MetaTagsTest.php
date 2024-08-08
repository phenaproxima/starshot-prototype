<?php

declare(strict_types=1);

namespace Drupal\Tests\drupal_cms\ExistingSite;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * @group drupal_cms
 */
class MetaTagsTest extends ExistingSiteBase {

  /**
   * Tests the front page title.
   */
  public function testFrontPageMetaTags(): void
  {
    // Create a random title expectation.
    $expected_title = $this->getRandomGenerator()->name();

    // Set the site name.
    $config_factory = $this->container->get('config.factory');
    $config_factory->getEditable('system.site')
      ->set('name', $expected_title)
      ->save();

    // Get the front page title.
    $this->drupalGet('<front>');
    $title_tag = $this->getSession()
      ->getPage()
      ->find('xpath', '/head/title');
    assert($title_tag instanceof NodeElement, 'Ensure that the "<title>" tag is found.');
    $actual_title = $title_tag->getText();

    $this->assertEquals($expected_title, $actual_title, 'Page title matches expected.');
  }

  /**
   * @testWith ["page"]
   *   ["blog"]
   *   ["event", {"field_location": {"country_code": "US", "administrative_area": "DC", "locality": "Washington", "postal_code": 20560, "address_line1": "1000 Jefferson Dr SW"}}, {"og:country_name": "United States", "og:locality": "Washington", "og:region": "DC", "og:postal_code":"20560", "og:street_address": "1000 Jefferson Dr SW"}]
   */
  public function testMetaTags(string $content_type, array $values = [], array $additional_meta_tags = []): void {
    $random = $this->getRandomGenerator();

    // If we create a page, all the expected meta tags should be there.
    $node = $this->createNode($values + [
      'type' => $content_type,
      'body' => [
        'summary' => 'Not a random summary...',
        'value' => $random->paragraphs(1),
      ],
      'moderation_state' => 'published',
    ]);
    $this->drupalGet($node->toUrl());
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $this->assertMetaTag('description', 'Not a random summary...');
    $this->assertMetaTag('og:description', 'Not a random summary...');
    $this->assertMetaTag('og:title', $node->getTitle());
    $this->assertMetaTag('og:type', $node->type->entity->label());

    if ($node->hasField('field_image')) {
      $file_uri = uniqid('public://') . '.png';
      $file_uri = $random->image($file_uri, '100x100', '200x200');
      $this->assertFileExists($file_uri);
      $file = File::create(['uri' => $file_uri]);
      $file->save();
      $this->markEntityForCleanup($file);
      $file_url = $this->container->get(FileUrlGeneratorInterface::class)
        ->generateAbsoluteString($file_uri);

      $media = Media::create([
        'name' => $random->word(16),
        'bundle' => 'image',
        'field_media_image' => [
          'target_id' => $file->id(),
          'alt' => 'Not random alt text...',
        ],
      ]);
      $media->save();
      $this->markEntityForCleanup($media);

      $node->set('field_image', $media)->save();
      $this->getSession()->reload();
      $this->assertMetaTag('image_src', $file_url, 'link');
      $this->assertMetaTag('og:image', $file_url);
      $this->assertMetaTag('og:image:alt', 'Not random alt text...');
    }

    foreach ($additional_meta_tags as $name => $value) {
      $this->assertMetaTag($name, $value);
    }
  }

  private function assertMetaTag(string $name, string $expected_value, string $tag_name = 'meta', bool $exact_match = TRUE): void {
    $name_attribute = match ($tag_name) {
      'meta' => str_contains($name, ':') ? 'property' : 'name',
      'link' => 'rel',
    };

    $actual_value = $this->assertSession()
      ->elementExists('css', $tag_name . '[' . $name_attribute . '="' . $name . '"]')
      ->getAttribute(match ($tag_name) { 'meta' => 'content', 'link' => 'href'});

    if ($exact_match) {
      $this->assertSame($expected_value, $actual_value);
    }
    else {
      $this->assertStringContainsString($expected_value, $actual_value);
    }
  }

}
