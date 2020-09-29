<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_iframe\Functional;

use Behat\Mink\Exception\ExpectationException;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\oe_media\Traits\MediaTypeCreationTrait;

/**
 * Test the Media Iframe text formats.
 *
 * @group oe_media_iframe
 */
class MediaIframeFormatTest extends MediaFunctionalTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_media_iframe',
  ];

  /**
   * The media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $mediaType;

  /**
   * The source field.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $sourceField;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);
    $this->container->get('router.builder')->rebuild();

    $media_type = $this->createMediaType('oe_media_iframe', [
      'id' => 'test_iframe',
      'label' => 'Test iframe source',
      'source' => 'oe_media_iframe',
    ]);
    $view_display = \Drupal::service('entity_display.repository')->getViewDisplay('media', $media_type->id());
    $source = $media_type->getSource();
    $source->prepareViewDisplay($media_type, $view_display);
    $view_display->save();

    $this->sourceField = $source->getConfiguration()['source_field'];
    $this->mediaType = $media_type;

    // Create user with permission for using oe_media_iframe text format.
    $this->adminUser = $this->createUser(array_merge(
      static::$adminUserPermissions,
      [FilterFormat::load('oe_media_iframe')->getPermissionName()]
    ));
  }

  /**
   * Test text formats selected for Media source.
   */
  public function testMediaSourceTextFormats(): void {
    foreach ($this->getFixtures() as $case_name => $test_data) {
      $media = $this->storage->create([
        'bundle' => $this->mediaType->id(),
        'name' => 'Test iframe media',
        $this->sourceField => $test_data['html'],
        'status' => TRUE,
      ]);
      $media->save();
      foreach ($test_data['formats_output'] as $format_name => $expected) {
        $this->drupalLogin($this->adminUser);
        $this->drupalGet('admin/structure/media/manage/' . $this->mediaType->id());
        $this->getSession()->getPage()->selectFieldOption('Text format', $format_name);
        $this->getSession()->getPage()->pressButton('Save');
        $this->drupalLogout();
        $this->drupalGet('media/' . $media->id());
        $this->assertSession()->responseContains($expected);
      }
    }

  }

  /**
   * Test text formats selected for Media source.
   */
  public function testMediaSourceWidgetConfiguration(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/media/manage/' . $this->mediaType->id() . '/form-display');
    $this->assertTrue($this->assertSession()->optionExists('fields[' . $this->sourceField->getName() . '][region]', 'content')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists('fields[' . $this->sourceField->getName() . '][type]', 'oe_media_iframe_textarea')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists('fields[' . $this->sourceField->getName() . '][type]', 'oe_media_iframe_textarea')->isSelected());

    $this->drupalPostForm(NULL, [], $this->sourceField->getName() . '_settings_edit');
    $this->assertText('Widget settings: Media iframe text area');
    $this->assertSession()->pageTextContains('Rows');
    $this->assertSession()->pageTextContains('Placeholder');
    $this->drupalGet('media/add/' . $this->mediaType->id());
    $this->assertSession()->fieldExists('Iframe');
    $this->assertSession()->pageTextContains('Allowed HTML tags: <iframe allowfullscreen height importance loading name referrerpolicy sandbox src width mozallowfullscreen webkitAllowFullScreen scrolling frameborder>');
  }

  /**
   * Array of data for testing text formats.
   *
   * @return array
   *   The test data.
   */
  protected function getFixtures(): array {
    return [
      'test standard iframe with 2 formats' => [
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0"><a href="#">invalid</a></iframe><script type="text/javascript">alert(\'no js\')</script>',
        'formats_output' => [
          'oe_media_iframe' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0">invalid</iframe>alert(\'no js\')',
          'plain_text' => '&lt;iframe src=&quot;<a href="http://web:8080/tests/fixtures/example.html&amp;quot">http://web:8080/tests/fixtures/example.html&amp;quot</a>; width=&quot;800&quot; height=&quot;600&quot; frameborder=&quot;0&quot;&gt;&lt;a href=&quot;#&quot;&gt;invalid&lt;/a&gt;&lt;/iframe&gt;&lt;script type=&quot;text/javascript&quot;&gt;alert(&#039;no js&#039;)&lt;/script&gt;',
        ],
      ],
      'test iframe with used attributes for EbS Live' => [
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" allow allowfullscreen allowpaymentrequest csp importance loading name referrerpolicy sandbox srcdoc mozallowfullscreen webkitAllowFullScreen scrolling><a href="#">invalid</a></iframe><script type="text/javascript">alert(\'no js\')</script>',
        'formats_output' => [
          'oe_media_iframe' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" allowfullscreen="" importance="" loading="" name="" referrerpolicy="" sandbox="" mozallowfullscreen="" webkitallowfullscreen="" scrolling="" id="">invalid</iframe>alert(\'no js\')',
        ],
      ],
      'test iframe with all allowed attributes' => [
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" allow allowfullscreen allowpaymentrequest csp importance loading name referrerpolicy sandbox srcdoc mozallowfullscreen webkitAllowFullScreen scrolling accesskey autocapitalize class contenteditable data-test data-test2 dir draggable dropzone exportparts hidden id inputmode is itemid itemprop itemref itemscope itemtype lang part slot spellcheck style tabindex title translate><a href="#">invalid</a></iframe><script type="text/javascript">alert(\'no js\')</script>',
        'formats_output' => [
          'oe_media_iframe' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" allowfullscreen="" importance="" loading="" name="" referrerpolicy="" sandbox="" mozallowfullscreen="" webkitallowfullscreen="" scrolling="" lang="" id="" xml:lang="">invalid</iframe>alert(\'no js\')',
        ],
      ],
      'test iframe with not allowed attribute' => [
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" invalid-attribute="with random value"></iframe>',
        'formats_output' => [
          'oe_media_iframe' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0"></iframe>',
        ],
      ],
    ];
  }

}
