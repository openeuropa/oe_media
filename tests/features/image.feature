@api
Feature: Image media entities.
  In order to show images on the website
  As a site editor
  I want to be able to upload images.

  @cleanup:node @cleanup:media
  Scenario: Documents can be uploaded and attached to nodes.
    Given I am logged in as a user with the "create oe_media_demo content, create image media" permissions
    And I go to "media/add/image"
    Then I should see the heading "Add Image"
    When I fill in "Name" with "My Image 1"
    And I attach the file "example_1.jpeg" to "Image"
    And I press "Upload"
    And I fill in "Alternative text" with "Image Alt Text 1"
    And I press "Save"
    Then I should see the heading "My Image 1"

    When I go to "node/add/oe_media_demo"
    Then I should see the heading "Create OpenEuropa Media Demo"
    When I fill in "Title" with "My Node"
    And I fill in the image reference field with "My Image 1"
    And I press "Save"
    Then I should see the heading "My Node"
    And I should see the image "example_1.jpeg"
