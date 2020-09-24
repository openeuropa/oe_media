@api
Feature: Image media entities.
  In order to show images on the website
  As a site editor
  I want to be able to upload images.

  @cleanup:node @cleanup:media @media-enable-standalone-url
  Scenario: Images can be uploaded and attached to nodes.
    Given I am logged in as a user with the "create oe_media_demo content, create image media" permissions
    When I go to "the image creation page"
    Then I should see the heading "Add Image"
    When I fill in "Name" with "My Image 1"
    And I attach the file "example_1.jpeg" to "Image"
    And I press "Upload"
    And I fill in "Alternative text" with "Image Alt Text 1"
    And I press "Save"
    Then I should see the heading "My Image 1"

    When I go to "the demo content creation page"
    Then I should see the heading "Create OpenEuropa Media Demo"
    When I fill in "Title" with "My Node"
    And I fill in the image reference field with "My Image 1"
    And I press "Save"
    Then I should see the heading "My Node"
    And I should see the image "example_1.jpeg"

  @javascript @cleanup:node @cleanup:media
  Scenario: Images can be added and referenced through the entity browser modal window.
    Given I am logged in as a user with the "create oe_media_demo content, create image media, access media_entity_browser entity browser pages" permissions
    When I go to "the demo content creation page"
    Then I should see the heading "Create OpenEuropa Media Demo"

    Given I fill in "Title" with "OpenEuropa at SymfonyCon Lisbon"
    And I click the fieldset "Media browser field"
    When I press "Select entities"
    Then I should see entity browser modal window
    And I wait for AJAX to finish

    When I click "Add Image"
    And I wait for AJAX to finish
    And I fill in "Name" with "OpenEuropa team members at Symfonycon Lisbon"
    And I attach the file "example_1.jpeg" to "Image"
    And I wait for AJAX to finish
    And I fill in "Alternative text" with "Symfonycon Lisbon"
    And I press "Save entity"
    And I wait for AJAX to finish
    Then I should see the text "OpenEuropa team members at Symfonycon Lisbon"
    And I should see the button "Remove"
    When I press "Save"
    Then I should see the heading "OpenEuropa at SymfonyCon Lisbon"
    And I should see the image "example_1.jpeg"

    # Reuse the existing image media in another node.
    When I go to "the demo content creation page"
    Then I should see the heading "Create OpenEuropa Media Demo"
    When I fill in "Title" with "OpenEuropa around Europe"
    And I click the fieldset "Media browser field"
    And I press "Select entities"
    Then I should see entity browser modal window
    And I wait for AJAX to finish

    When I select the "OpenEuropa team members at Symfonycon Lisbon" media in the entity browser modal window
    And I press "Select entities"
    And I wait for AJAX to finish
    Then I should see the text "OpenEuropa team members at Symfonycon Lisbon"
    And I should see the button "Remove"
    When I press "Save"
    Then I should see the heading "OpenEuropa around Europe"
    And I should see the image "example_1.jpeg"
