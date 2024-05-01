<?php

declare(strict_types=1);

namespace src\ExistingSite;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * @group starshot
 */
class MetatagsTest extends ExistingSiteBase {

  /**
   * @testWith ["blog"]
   *   ["event"]
   *   ["page"]
   */
  public function testMetaTags(string $content_type): void {
    $random = $this->getRandomGenerator();

    $uri = uniqid('public://') . '.png';
    $image = $random->image($uri, '50x50', '100x100');
    $file = File::create(['uri' => $image]);
    $file->save();
    $this->markEntityForCleanup($file);

    $file_url = $this->container->get(FileUrlGeneratorInterface::class)
      ->generateAbsoluteString($uri);

    $media = Media::create([
      'name' => $file->getFilename(),
      'bundle' => 'image',
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => 'A randomly generated image.',
      ]
    ]);
    $media->save();
    $this->markEntityForCleanup($media);

    $node = $this->createNode([
      'type' => $content_type,
      'moderation_state' => 'published',
      'body' => [
        'summary' => 'Not a random set of paragraphs...',
        'value' => $random->paragraphs(1),
      ],
    ]);
    $has_image = $node->hasField('field_image');

    if ($has_image) {
      $node->set('field_image', $media)->save();
    }
    $this->drupalGet($node->toUrl());

    $this->assertSession()->statusCodeEquals(200);
    $this->assertMetaTag('description', $node->body->summary);
    $this->assertMetaTag('og:description', $node->body->summary);
    $this->assertMetaTag('og:title', $node->getTitle());
    $this->assertMetaTag('og:type', $node->type->entity->label());

    if ($has_image) {
      $this->assertMetaTag('og:image', $file_url);
      $this->assertMetaTag('og:image:alt', $media->field_media_image->alt);
    }
  }

  private function assertMetaTag(string $tag, string $value): void {
    $locator = sprintf(
      'meta[%s="%s"]',
      str_contains($tag, ':') ? 'property' : 'name',
      $tag,
    );
    $this->assertSame($value, $this->assertSession()->elementExists('css', $locator)->getAttribute('content'));
  }

}
