---
name: e2e-signin
description: End-to-end regression check of Iam sign-in — identify by email, password, and the branch to "unknown email"/wrong password.
when_to_use: Verifying the storefront sign-in tunnel still works after touching SignInController, PasswordCredentialAuthenticator, or security/*.html.twig.
paths: apps/storefront/src/Controller/SignInController.php, apps/storefront/src/Security/**, apps/storefront/templates/security/**
allowed-tools: mcp__playwright__browser_navigate, mcp__playwright__browser_snapshot, mcp__playwright__browser_click, mcp__playwright__browser_type, Bash(castor cc:*)
effort: low
---

## Prerequisites

Requires an already-registered, confirmed account (see `e2e-register`) with a known email/password.

## Steps — happy path

1. Navigate to `http://localhost/storefront/signin`.
2. Type the email into "Enter your email address", click "Continue" → `/storefront/signin/verify`.
3. Type the password into "Password", click "Sign in".
4. If no 2FA credential is issued: lands on `/storefront/account` ("Your account").
   If TOTP/backup codes are issued: lands on `/storefront/2sv` (see `e2e-2fa-challenge`).

## Steps — edge cases

- **Unknown email**: step 2 with an email that never registered shows "This email is new to us" (register branch), not an error.
- **Wrong password**: step 3 with a wrong password re-renders `/storefront/signin/verify` with "Invalid credentials." (never a raw translation key, never a 500) and the email preserved.
- **Unconfirmed account, normal flow**: identifying with an email that registered but never confirmed redirects straight to `/storefront/register/<id>/confirm` — the password step is never reached at all.
- **Unconfirmed account, direct POST**: navigating straight to `/storefront/signin/verify` (bypassing the identify redirect) and submitting any password hits `PasswordUserChecker`'s own defense-in-depth check — "Your account is not confirmed." (not a generic "invalid credentials", not a 500).
- **Login throttling**: 3 consecutive wrong passwords on the same identity lock it out — "Too many failed login attempts, please try again in 15 minutes." (`security.main.login_throttling`, `max_attempts: 3`). The CORRECT password is also rejected during that window, with the same message and a decreasing minute count — confirms it's a real lock, not cosmetic.
- **Logout**: `http://localhost/storefront/logout` must redirect to `/storefront/signin` (not 500 — this route name has drifted before, see git history on `security.php`'s `logout.target`).

## Expected result

Every branch renders a real page (no 500), every flash/error message is a real translated sentence (no raw key like `code_invalid` visible in the DOM).
