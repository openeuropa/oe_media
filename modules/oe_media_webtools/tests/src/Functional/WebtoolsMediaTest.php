<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_webtools\Functional;

use Drupal\Tests\oe_media\Functional\MediaFeatureTestBase;

/**
 * Tests the creation and the referencing of the Webtools media types.
 *
 * @group oe_media_webtools
 * @group batch3
 */
class WebtoolsMediaTest extends MediaFeatureTestBase {

  /**
   * The description shown under all the Webtools snippet fields.
   */
  protected const SNIPPET_DESCRIPTION = 'Enter the snippet without the script tag. Snippets can be generated in Webtools wizard or in the newer WCLOUD wizard.';

  /**
   * The Webtools media types that share the "snippet" creation flow.
   *
   * @var array[]
   */
  protected const SNIPPET_WIDGETS = [
    'webtools_chart' => [
      'type_label' => 'Webtools chart',
      'field_label' => 'Webtools chart snippet',
      'name' => 'Basic chart',
      'invalid_snippet' => '{"service": "map"}',
      'invalid_message' => 'Invalid Webtools Chart snippet.',
      'snippet' => '{"service":"charts","data":{"series":[{"name":"Y","data":[{"name":"1","y":0.5}]}]},"provider":"highcharts"}',
      'reference_field' => 'field_oe_demo_webtools_chart',
    ],
    'webtools_countdown' => [
      'type_label' => 'Webtools countdown',
      'field_label' => 'Webtools countdown snippet',
      'name' => 'Event Countdown',
      'invalid_snippet' => '{"service": "map"}',
      'invalid_message' => 'Invalid Webtools Countdown snippet.',
      'snippet' => '{"service":"cdown","date":"30/04/2052","timezone":"Etc/Universal","title":"Event countdown","end":true,"show":{"day":true,"time":true}}',
      'reference_field' => 'field_oe_demo_webtools_countdown',
    ],
    'webtools_generic' => [
      'type_label' => 'Webtools generic',
      'field_label' => 'Webtools snippet',
      'name' => 'Share button',
      'invalid_snippet' => '{"service": "map"}',
      'invalid_message' => 'This service is supported by a dedicated asset type or feature, please use that instead.',
      'snippet' => '{"service": "share","icon": true,"selection": false,"shortenurl": true}',
      'reference_field' => 'field_oe_demo_webtools_generic',
    ],
    'webtools_map' => [
      'type_label' => 'Webtools map',
      'field_label' => 'Webtools map snippet',
      'name' => 'World map',
      'invalid_snippet' => '{"service": "charts"}',
      'invalid_message' => 'Invalid Webtools Map snippet.',
      'snippet' => '{"service": "map"}',
      'reference_field' => 'field_oe_demo_webtools_map',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
  }

  /**
   * Tests the Webtools media types that are created from a JSON snippet.
   */
  public function testSnippetBasedWebtoolsMedia(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    foreach (self::SNIPPET_WIDGETS as $bundle => $widget) {
      $this->drupalGet('media/add/' . $bundle);
      $assert_session->pageTextContains(self::SNIPPET_DESCRIPTION);
      $assert_session->pageTextContains('Please keep in mind that acceptance-level Webtools widgets can only be viewed if you are connected to the EC network.');

      // Both the name and the snippet are required.
      $page->pressButton('Save');
      $assert_session->pageTextContains('Name field is required');
      $assert_session->pageTextContains($widget['field_label'] . ' field is required');

      // A snippet of the wrong service is rejected.
      $page->fillField('Name', $widget['name']);
      $page->fillField($widget['field_label'], $widget['invalid_snippet']);
      $page->pressButton('Save');
      $assert_session->pageTextContains($widget['invalid_message']);

      $page->fillField($widget['field_label'], $widget['snippet']);
      $page->pressButton('Save');
      $assert_session->pageTextContains(sprintf('%s %s has been created.', $widget['type_label'], $widget['name']));
    }

    // Create the OP publication list media, which is created from an ID.
    $this->drupalGet('media/add/webtools_op_publication_list');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Name field is required');
    $assert_session->pageTextContains('Webtools OP Publication list ID field is required');

    $page->fillField('Name', 'Basic OP publication list');
    $page->fillField('Webtools OP Publication list ID', 'ID');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Webtools OP Publication list ID must be a number.');

    $page->fillField('Webtools OP Publication list ID', '12.34');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Webtools OP Publication list ID is not a valid number.');

    $page->fillField('Webtools OP Publication list ID', '1234');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Webtools op publication list Basic OP publication list has been created.');

    // The stored JSON is turned back into the plain ID on the edit form.
    $this->drupalGet($this->getMediaByName('Basic OP publication list')->toUrl('edit-form'));
    $assert_session->fieldValueEquals('Webtools OP Publication list ID', '1234');

    // Reference all of the created media on a single demo node and assert that
    // each of the snippets is rendered.
    $this->drupalGet('node/add/oe_media_demo');
    $page->fillField('Title', 'My demo node');
    foreach (self::SNIPPET_WIDGETS as $widget) {
      $page->fillField($widget['reference_field'] . '[0][target_id]', $widget['name']);
    }
    $page->fillField('field_oe_demo_webtools_op[0][target_id]', 'Basic OP publication list');
    $page->pressButton('Save');
    $assert_session->pageTextContains('OpenEuropa Media Demo My demo node has been created.');

    foreach (self::SNIPPET_WIDGETS as $bundle => $widget) {
      $this->assertWebtoolsSnippetRendered($bundle, $widget['name']);
    }
    $this->assertWebtoolsSnippetRendered('webtools_op_publication_list', 'Basic OP publication list');
  }

  /**
   * Tests that the deprecated Webtools social feed media cannot be saved.
   */
  public function testWebtoolsSocialFeedMedia(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('media/add/webtools_social_feed');
    $assert_session->pageTextContains('Add Webtools social feed - Deprecated');
    $assert_session->pageTextContains(self::SNIPPET_DESCRIPTION);

    $page->pressButton('Save');
    $assert_session->pageTextContains('Name field is required');
    $assert_session->pageTextContains('Webtools social feed snippet field is required');

    // The service is no longer supported, no snippet is accepted.
    $page->fillField('Name', 'Spokepersons');
    $page->fillField('Webtools social feed snippet', '{"service": "charts"}');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The service "social_feed" is no longer supported.');

    $page->fillField('Webtools social feed snippet', '{"service":"smk","type":"list","slug":"ec-spokespersons"}');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The service "social_feed" is no longer supported.');
  }

}
