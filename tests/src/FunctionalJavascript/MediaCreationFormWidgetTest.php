<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Tests the media creation form entity browser widget.
 */
class MediaCreationFormWidgetTest extends WebDriverTestBase {

  use TestFileCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'system',
    'oe_media',
    'oe_media_demo',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The currently logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $permissions = [
      'access media_entity_browser entity browser pages',
      'create oe_media_demo content',
      'view the administration theme',
    ];
    $this->currentUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->currentUser);
  }

  /**
   * Tests the media creation form entity browser widget.
   */
  public function testMediaCreationForm(): void {
    $this->drupalGet('node/add/oe_media_demo');

    $this->getSession()->getPage()->pressButton('Media browser field');
    $media_browser_field = $this->getSession()->getPage()->find('css', 'div.field--name-field-oe-demo-media-browser');
    $media_browser_field->pressButton('Select entities');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
    // The user doesn't have access to create any of the media bundles so the
    // tab should not be available.
    $this->assertFalse($this->getSession()->getPage()->hasLink('Media creation form'));

    // Grant authenticated role permission to create Iframe media.
    $role = Role::load('authenticated');
    $this->grantPermissions($role, ['create iframe media']);
    $this->getSession()->reload();
    $this->getSession()->getPage()->pressButton('Media browser field');
    $media_browser_field->pressButton('Select entities');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
    // The user has access to create Iframe media so the tab is visible but no
    // bundles can be created.
    $this->getSession()->getPage()->clickLink('Media creation form');
    $this->assertFalse($this->getSession()->getPage()->hasSelect('Bundle'));
    $this->assertSession()->pageTextContains('You cannot create any of the media bundles available in the current field.');

    // Grant the authenticated role additional permissions to create media
    // bundles.
    $permissions = [
      'create av_portal_photo media',
      'create av_portal_video media',
      'create document media',
      'create image media',
      'create remote_video media',
      'edit own image media',
    ];
    $this->grantPermissions($role, $permissions);
    $this->getSession()->reload();
    $this->getSession()->getPage()->pressButton('Media browser field');
    $media_browser_field->pressButton('Select entities');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
    $this->getSession()->getPage()->clickLink('Media creation form');
    // Assert that the bundle select field exists and contains only the allowed
    // target bundles (Note: Iframe should not be available).
    $this->assertSession()->selectExists('Bundle');
    $select_field = $this->getSession()->getPage()->findField('Bundle');
    $this->assertEquals([
      'av_portal_photo' => 'AV Portal Photo',
      'av_portal_video' => 'AV Portal Video',
      'document' => 'Document',
      'image' => 'Image',
      'remote_video' => 'Remote video',
      '_none' => '- Select -',
    ], $this->getOptions($select_field));
    // Assert that the bundle field is required.
    $this->assertSession()->elementAttributeContains('css', 'select#edit-media-bundle', 'required', 'required');
    $this->assertSession()->buttonExists('Save media');

    // Assert that only the allowed target bundles are present on a different
    // field.
    $this->getSession()->reload();
    $this->getSession()->getPage()->pressButton('Images browser field');
    $image_field = $this->getSession()->getPage()->find('css', 'div.field--name-field-oe-demo-images-browser');
    $image_field->pressButton('Select entities');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
    $this->getSession()->getPage()->clickLink('Media creation form');
    $select_field = $this->getSession()->getPage()->findField('Bundle');
    $this->assertEquals([
      '_none' => '- Select -',
      'av_portal_photo' => 'AV Portal Photo',
      'image' => 'Image',
    ], $this->getOptions($select_field));
    $this->getSession()->getPage()->selectFieldOption('Bundle', 'AV Portal Photo');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldExists('Media AV Portal Photo');
    // Change the bundle and assert the form is updated.
    $this->getSession()->getPage()->selectFieldOption('Bundle', 'Image');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldExists('Name');
    $this->assertSession()->fieldExists('Image');
    $this->assertSession()->fieldNotExists('Media AV Portal Photo');

    // Toggle the bundle field none option.
    $this->getSession()->getPage()->selectFieldOption('Bundle', '- Select -');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldNotExists('Name');
    $this->assertSession()->fieldNotExists('Image');

    // Toggle tabs, assert the bundle remains the same.
    $this->getSession()->getPage()->selectFieldOption('Bundle', 'Image');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldExists('Name');
    $this->assertSession()->fieldExists('Image');
    $this->getSession()->getPage()->clickLink('Register AV Portal video');
    $this->assertSession()->fieldNotExists('Bundle');
    $this->getSession()->getPage()->clickLink('Media creation form');
    $this->assertSession()->fieldValueEquals('Bundle', 'Image');

    // Create a file for image media.
    $this->getSession()->getPage()->fillField('Name', 'Test image');
    $file = current($this->getTestFiles('image'));
    $image_file_path = \Drupal::service('file_system')->realpath($file->uri);
    $this->getSession()->getPage()->attachFileToField('Image', $image_file_path);
    $this->assertSession()->waitForField('Alternative text');
    $this->getSession()->getPage()->fillField('Alternative text', 'img alt');
    $this->getSession()->getPage()->pressButton('Save media');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Test image');
    $this->assertSession()->buttonExists('Remove');
    $this->assertSession()->buttonExists('Edit');

    // Fill in the rest of the fields.
    $this->getSession()->getPage()->fillField('Title', 'The node title');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('The node title');
    $this->assertSession()->elementAttributeContains('css', 'img', 'src', $file->filename);
  }

}
