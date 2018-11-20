@api
Feature: Media document bundle.
  In order to be able to showcase the media and entity browser features for managing files
  As a site editor
  I want to see the entity browser widget for adding and reusing document media entities.

  @javascript
  Scenario: The node adding form should contain entity browser widget with possibility to add new and reuse existing files.
    Given I am logged in as a user with the "create oe_media_demo content,create document media,access media_entity_browser entity browser pages" permissions
    
    When I visit "node/add/oe_media_demo"
    And I fill in "Title" with "Media demo"
    And I click fieldset "Media browser field" of entity browser widget
    And I press the "Select entities" button
    Then I should see entity browser modal window
    When I click "Add File"
    And I fill in "Name" with "Media document"
    And I attach the file "sample.pdf" to "File"
    And I press the "Save entity" button
    And I press the "Save" button
    Then I should see "sample.pdf"

    When I visit "node/add/oe_media_demo"
    And I fill in "Title" with "Media demo"
    And I click fieldset "Media browser field" of entity browser widget
    And I press the "Select entities" button
    Then I should see entity browser modal window
    When I click "View"
    And I select the "Media document" media entity in the entity browser modal window
    And I press the "Select entities" button
    And I press the "Save" button
    Then I should see "sample.pdf"
