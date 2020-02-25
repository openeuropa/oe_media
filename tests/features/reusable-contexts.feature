@api
Feature: Reusable Behat context
  In order to reuse this project's Behat contexts
  As a developer
  I want to make sure that they work as expected.

  Scenario: I can create media using Behat steps
    Given am on homepage
    And the following AV Portal photos:
      | url                                                         |
      | https://audiovisual.ec.europa.eu/en/photo/P-038924~2F00-15  |
      | https://audiovisual.ec.europa.eu/en/photo/P-039321~2F00-04  |
    And the following documents:
      | name       | file       |
      | Document 1 | sample.pdf |
      | Document 2 | sample.pdf |
    And the following images:
      | name    | file           | alt                |
      | Image 1 | example_1.jpeg | Alternative text 1 |
      | Image 2 | example_1.jpeg | Alternative text 2 |

    When I am logged in as a user with the "administer media, access media overview" permissions
    And I go to "the media overview page"
    Then I should see "Euro with miniature figurines"
    And I should see "Visit by Federica Mogherini"
    And I should see "Document 1"
    And I should see "Document 2"
    And I should see "Image 1"
    And I should see "Image 2"

    When I click Edit in the "Euro with miniature figurines" row
    Then the "Media AV Portal Photo" field should contain "https://audiovisual.ec.europa.eu/en/photo/P-038924~2F00-15"

    Given I go to "the media overview page"
    When I click Edit in the "Document 1" row
    Then the "Name" field should contain "Document 1"
    And I should see the link "sample.pdf"

    Given I go to "the media overview page"
    When I click Edit in the "Image 1" row
    Then the "Name" field should contain "Image 1"
    And the "Alternative text" field should contain "Alternative text 1"
    And I should see the link "example_1.jpeg"

    Given I go to "the media overview page"
    When I click Edit in the "Image 2" row
    Then the "Name" field should contain "Image 2"
    And the "Alternative text" field should contain "Alternative text 2"
    And I should see the link "example_1.jpeg"
