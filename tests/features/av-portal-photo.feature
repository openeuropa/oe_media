@api
Feature: AV Portal photo.
  In order to be able to showcase AV Portal photos
  As a site editor
  I want to create and reference AV Portal photo media entities.

  @av_portal @cleanup:media
  Scenario: Create and reference an AV Portal photo
    Given I am logged in as a user with the "create oe_media_demo content,create av_portal_photo media" permission
    When I visit "the AV Portal photo creation page"
    And I fill in "Media AV Portal Photo" with "https://ec.europa.eu/avservices/photo/photoDetails.cfm?sitelang=en&ref=038924#14"
    And I press "Save"
    And I visit "the demo content creation page"
    And I fill in "Title" with "My demo node"
    And I reference the AV Portal photo "Euro with miniature figurines"
    And I press "Save"
    Then I should see the AV Portal photo "Euro with miniature figurines" with source "//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/4/P038924-352937.jpg"

  @javascript @av_portal @cleanup:media
  Scenario: The node adding form should contain an entity browser widget with the possibility to add new and reuse existing AV Portal photos.
    Given I am logged in as a user with the "create oe_media_demo content,create av_portal_photo media,access media_entity_browser entity browser pages" permission
    When I visit "the demo content creation page"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    And I press the "Select entities" button
    Then I should see entity browser modal window
    When I click "Add AV Portal Photo"
    And I fill in "Media AV Portal Photo" with "https://ec.europa.eu/avservices/photo/photoDetails.cfm?sitelang=en&ref=038924#14"
    And I press the "Save entity" button
    And I press the "Save" button
    Then I should see the AV Portal photo "Euro with miniature figurines" with source "//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/4/P038924-352937.jpg"

    When I visit "the demo content creation page"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    When I press the "Select entities" button
    Then I should see entity browser modal window
    When I click "View"
    And I select the "Euro with miniature figurines" media entity in the entity browser modal window
    And I press the "Select entities" button
    And I press the "Save" button
    Then I should see the AV Portal photo "Euro with miniature figurines" with source "//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/4/P038924-352937.jpg"

  @javascript @av_portal @hernani @cleanup:media
  Scenario: The entity browser should contain a widget that allows to search for photos in AV Portal.
    Given I am logged in as a user with the "create oe_media_demo content,create av_portal_video media,access media_entity_browser entity browser pages" permission
    When I visit "the demo content creation page"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    When I press the "Select entities" button
    Then I should see entity browser modal window
    When I click "Search photos in AV Portal"
    Then I should see "Visit by Federica Mogherini, Vice-President of the EC, and Johannes Hahn, Member of the EC, to Romania"
    When I select the avportal item with the title 'Visit by Federica Mogherini, Vice-President of the EC, and Johannes Hahn, Member of the EC, to Romania'
    And I press the "Select entities" button
    And I press the "Save" button
    Then I should see the AV Portal photo 'Visit by Federica Mogherini, Vice-President of the EC, and Johannes Hahn, Member of the EC, to Romania' with source "//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/1/P039321-615406.jpg"
