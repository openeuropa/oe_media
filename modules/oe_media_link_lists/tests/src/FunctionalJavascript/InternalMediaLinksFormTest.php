<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_link_lists\FunctionalJavascript;

use Drupal\Tests\oe_link_lists_manual_source\FunctionalJavascript\ManualLinkListTestBase;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\oe_link_lists\DefaultLink;

/**
 * Tests the manual internal media links.
 */
class InternalMediaLinksFormTest extends ManualLinkListTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'media',
    'oe_media_link_lists',
    'oe_media',
    'oe_media_avportal',
    'oe_media_webtools',
    'oe_media_iframe',
    'composite_reference',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a pdf file.
    $file_path = \Drupal::service('extension.list.module')->getPath('oe_media') . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'sample.pdf';
    \Drupal::service('file_system')->copy($file_path, 'public://sample.pdf');
    $file = File::create(['uri' => 'public://sample.pdf']);
    $file->save();

    // Create a media entity using the pdf.
    $media = Media::create([
      'bundle' => 'document',
      'name' => 'Test document',
      'oe_media_file' => [
        'target_id' => (int) $file->id(),
      ],
      'status' => 1,
    ]);
    $media->save();

    $user = $this->drupalCreateUser([], '', TRUE);
    $this->drupalLogin($user);
  }

  /**
   * Test internal media link using the form.
   */
  public function testInternalMediaLinks(): void {
    $this->drupalGet('link_list/add/manual');
    $this->getSession()->getPage()->fillField('Title', 'Test internal media links');
    $this->getSession()->getPage()->fillField('Administrative title', 'List 1');
    $this->getSession()->getPage()->selectFieldOption('Link display', 'Title');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('No results behaviour', 'Hide');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Create an internal media link.
    $this->getSession()->getPage()->selectFieldOption('links[actions][bundle]', 'internal_media');
    $this->getSession()->getPage()->pressButton('Add new Link');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $links_wrapper = $this->getSession()->getPage()->find('css', '.field--widget-inline-entity-form-complex');
    // By default, the override checkbox is not checked and title and teaser
    // fields are not visible.
    $this->assertSession()->checkboxNotChecked('Override target values', $links_wrapper);
    $this->assertSession()->elementNotExists('css', 'Title', $links_wrapper);
    $this->assertSession()->elementNotExists('css', 'Teaser', $links_wrapper);

    $links_wrapper->fillField('Use existing media', 'Test document');
    $this->getSession()->getPage()->pressButton('Create Link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // The link title is not overridden.
    $this->assertSession()->pageTextContains('Internal media link: Test document');
    $this->getSession()->getPage()->pressButton('Save');

    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $link_list */
    $link_list = $this->getLinkListByTitle('Test internal media links');
    $this->assertCount(1, $link_list->get('links')->getValue());
    /** @var \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface $link */
    $link = $link_list->get('links')->first()->entity;

    // Check the values are stored correctly.
    $this->assertInstanceOf(MediaInterface::class, $link->get('media_target')->entity);
    $this->assertEquals('Internal media link: Test document', $link->label());

    // Edit the link list and override the title and teaser.
    $this->drupalGet($link_list->toUrl('edit-form'));
    $edit = $this->getSession()->getPage()->find('xpath', '(//input[@type="submit" and @value="Edit"])[1]');
    $edit->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links_wrapper->checkField('Override target values');
    $links_wrapper->fillField('Title', 'Internal document');
    $links_wrapper->fillField('Teaser', 'Internal document teaser');
    $this->getSession()->getPage()->pressButton('Update Link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Internal media link: Internal document');
    $this->getSession()->getPage()->pressButton('Save');

    // Assert the values have been overridden.
    $this->assertSession()->linkExistsExact('Internal document');
    $link_list = $this->getLinkListByTitle('Test internal media links', TRUE);
    /** @var \Drupal\oe_link_lists_manual_source\Entity\LinkListLinkInterface $link */
    $link = $link_list->get('links')->first()->entity;
    $this->assertEquals('Internal media link: Internal document', $link->label());

    // Resolve the links.
    $links = $this->getLinksFromList($link_list);
    $this->assertCount(1, $links);
    /** @var \Drupal\oe_link_lists\LinkInterface $link */
    $link = $links[0];
    $this->assertInstanceOf(DefaultLink::class, $link);
    $this->assertEquals(['#markup' => 'Internal document teaser'], $link->getTeaser());
    $this->assertEquals('Internal document', $link->getTitle());
  }

}
