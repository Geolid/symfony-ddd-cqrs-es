Feature: Sign in
    As a returning customer
    I want to sign in with my email and password, and a 2FA code when I've enrolled one
    So that I can access my account

    Scenario: Signing in with a confirmed password-only account
        Given I have a confirmed account with a password
        When I sign in with my email and password
        Then I should be signed in

    Scenario: Completing sign-in with a TOTP challenge
        Given I have a confirmed account with a password and TOTP enrolled
        When I sign in with my email and password
        Then I should see the two-factor challenge
        When I enter my current TOTP code
        Then I should be signed in
