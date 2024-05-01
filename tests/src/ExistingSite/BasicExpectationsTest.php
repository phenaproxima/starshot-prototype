<?php

declare(strict_types=1);

namespace Drupal\Tests\starshot\ExistingSite;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
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

    $random = $this->getRandomGenerator();

    // If we create a page, all the expected meta tags should be there.
    $node = $this->createNode([
      'type' => 'page',
      'body' => [
        'summary' => 'Not a random summary...',
        'value' => $random->paragraphs(1),
      ],
      'moderation_state' => 'published',
    ]);
    $this->drupalGet($node->toUrl());
    $assert_session->statusCodeEquals(200);
    $this->assertMetaTag('description', 'Not a random summary...');
    $this->assertMetaTag('og:description', 'Not a random summary...');
    $this->assertMetaTag('og:title', $node->getTitle());
    $this->assertMetaTag('og:type', 'Basic page');

    // Blog posts and events expose their image URLs in their meta tags, so
    // create an image media entity to test with.
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

    $node = $this->createNode([
      'type' => 'blog',
      'body' => [
        'summary' => 'In which I summarize my blog post.',
        'value' => $random->paragraphs(1),
      ],
      'field_image' => $media,
      'moderation_state' => 'published',
    ]);
    $this->drupalGet($node->toUrl());
    $assert_session->statusCodeEquals(200);
    $this->assertMetaTag('description', 'In which I summarize my blog post.');
    $this->assertMetaTag('image_src', $file_url, 'link');
    $this->assertMetaTag('og:description', 'In which I summarize my blog post.');
    $this->assertMetaTag('og:image', $file_url);
    $this->assertMetaTag('og:image:alt', 'Not random alt text...');
    $this->assertMetaTag('og:title', $node->getTitle());
    $this->assertMetaTag('og:type', 'Blog post');

    $node = $this->createNode([
      'type' => 'event',
      'body' => [
        'summary' => 'This party is gonna rock.',
        'value' => $random->paragraphs(1),
      ],
      'field_image' => $media,
      'field_location' => [
        'country_code' => 'US',
        'administrative_area' => 'DC',
        'locality' => 'Washington',
        'postal_code' => 20560,
        'address_line1' => '1000 Jefferson Dr SW',
      ],
      'moderation_state' => 'published',
    ]);
    $this->drupalGet($node->toUrl());
    $assert_session->statusCodeEquals(200);
    $this->assertMetaTag('description', 'This party is gonna rock.');
    $this->assertMetaTag('image_src', $file_url, 'link');
    $this->assertMetaTag('og:country_name', 'United States');
    $this->assertMetaTag('og:description', 'This party is gonna rock.');
    $this->assertMetaTag('og:image', $file_url);
    $this->assertMetaTag('og:image:alt', 'Not random alt text...');
    $this->assertMetaTag('og:locality', 'Washington');
    $this->assertMetaTag('og:postal_code', '20560');
    $this->assertMetaTag('og:region', 'DC');
    $this->assertMetaTag('og:street_address', '1000 Jefferson Dr SW');
    $this->assertMetaTag('og:title', $node->getTitle());
    $this->assertMetaTag('og:type', 'Event');
  }

  private function assertMetaTag(string $name, string $expected_value, string $tag_name = 'meta'): void {
    $name_attribute = match ($tag_name) {
      'meta' => str_contains($name, ':') ? 'property' : 'name',
      'link' => 'rel',
    };

    $actual_value = $this->assertSession()
      ->elementExists('css', $tag_name . '[' . $name_attribute . '="' . $name . '"]')
      ->getAttribute(match ($tag_name) {
        'meta' => 'content',
        'link' => 'href',
      });

    $this->assertSame($expected_value, $actual_value);
  }

}
