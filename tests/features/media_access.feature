@api
Feature: Media access.
  In order to protect sensitive data
  As a user
  I should only access the correct media items

  @cleanup:media @media-enable-standalone-url @remote-video
  Scenario: Unpublished nodes should be accessible only to users with the correct permission
    Given I am logged in as a user with the "create remote_video media, access media overview, edit own remote_video media, view own unpublished media" permissions
    When I go to "the remote video selection page"
    Then I should see the heading "Add Remote video"
    When I fill in "Remote video URL" with "https://www.youtube.com/watch?v=1-g73ty9v04"
    And I press "Save"

    # Published media are accessible on the overview and on the canonical URL.
    Then I should be on "the media overview page"
    And I should see the link "Energy, let's save it!"
    When I go to the "Energy, let's save it!" media page
    Then I should see the heading "Energy, let's save it!"
    And I should not see "Access denied"

    # Unpublish the media.
    When I click "Edit"
    And I uncheck "Published"
    And I press "Save"
    # The owner can still see it.
    Then I should be on "the media overview page"
    And I should see the link "Energy, let's save it!"
    When I go to the "Energy, let's save it!" media page
    Then I should see the heading "Energy, let's save it!"
    And I should not see "Access denied"

    # Log in with another user with those permissions.
    When I am logged in as a user with the "create remote_video media, access media overview, view own unpublished media" permissions

    # Another user cannot see the media as it is not published.
    And I go to "the media overview page"
    Then I should not see the link "Energy, let's save it!"
    When I go to the "Energy, let's save it!" media page
    Then I should not see "Energy, let's save it!"
    And I should see "Access denied"

    # Log in with user that has the correct permission.
    When I am logged in as a user with the "create remote_video media, access media overview, view any unpublished media" permissions
    And I go to "the media overview page"
    Then I should see the link "Energy, let's save it!"
    When I go to the "Energy, let's save it!" media page
    Then I should see the heading "Energy, let's save it!"
    And I should not see "Access denied"
