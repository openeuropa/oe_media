<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Traits;

/**
 * Helper methods to interact with the Media entity browser in JS tests.
 */
trait EntityBrowserTrait {

  /**
   * Opens the media entity browser modal of a given field and enters it.
   *
   * @param string $fieldset
   *   The label of the fieldset wrapping the entity browser element.
   * @param string $field_class
   *   The class of the field wrapper.
   */
  protected function openEntityBrowser(string $fieldset = 'Media browser field', string $field_class = 'field--name-field-oe-demo-media-browser'): void {
    $page = $this->getSession()->getPage();
    $page->pressButton($fieldset);
    $field = $page->find('css', 'div.' . $field_class);
    $this->assertNotNull($field, sprintf('The "%s" field wrapper was not found.', $field_class));
    $field->pressButton('Select entities');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->enterEntityBrowser();
  }

  /**
   * Clicks one of the entity browser tabs.
   *
   * @param string $label
   *   The label of the widget tab.
   */
  protected function clickEntityBrowserTab(string $label): void {
    $this->getSession()->getPage()->clickLink($label);
  }

  /**
   * Switches into the entity browser iframe.
   */
  protected function enterEntityBrowser(): void {
    $this->assertSession()->waitForElementVisible('css', 'iframe.entity-browser-modal-iframe');
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
  }

  /**
   * Switches back to the main document.
   */
  protected function leaveEntityBrowser(): void {
    $this->getSession()->switchToWindow($this->getSession()->getWindowName());
  }

  /**
   * Checks the checkbox of a media entity listed in the entity browser view.
   *
   * @param string $name
   *   The media name.
   */
  protected function selectMediaInEntityBrowser(string $name): void {
    $this->selectEntityBrowserRow('media:' . $this->getMediaByName($name)->id());
  }

  /**
   * Checks the checkbox of a row of the current entity browser view widget.
   *
   * @param string $row_id
   *   The row ID the widget uses to identify the selection.
   */
  protected function selectEntityBrowserRow(string $row_id): void {
    // The sticky form actions can overlay the checkbox and intercept the click.
    $this->getSession()->executeScript("document.querySelector('.entity-browser-form > .form-actions').style.position = 'static';");
    $this->getSession()->getPage()->checkField('entity_browser_select[' . $row_id . ']');
  }

  /**
   * Waits for the entity browser modal to close, back in the main document.
   */
  protected function waitForEntityBrowserToClose(): void {
    $this->leaveEntityBrowser();
    $page = $this->getSession()->getPage();
    $closed = $page->waitFor(10, function () use ($page): bool {
      return $page->find('css', '.entity-browser-modal-iframe') === NULL;
    });

    if (!$closed) {
      $messages = [];
      try {
        $this->enterEntityBrowser();
        foreach ($this->getSession()->getPage()->findAll('css', '.messages-list__item, .messages') as $item) {
          $text = trim($item->getText());
          if ($text !== '') {
            $messages[] = preg_replace('/\s+/', ' ', $text);
          }
        }
      }
      finally {
        $this->leaveEntityBrowser();
      }

      $this->fail(sprintf(
        'The entity browser modal window did not close.%s',
        $messages ? ' Reported errors: ' . implode(' | ', $messages) . '.' : ''
      ));
    }

    $this->assertSession()->assertWaitOnAjaxRequest();
  }

}
