---
name: e2e-forgot-password
description: End-to-end regression check of Iam password reset — request, receive code via Mailpit, resend (throttled), reset, sign in with the new password.
when_to_use: Verifying the storefront password-reset tunnel still works after touching ForgotPasswordController or forgot_password/*.html.twig.
paths: apps/storefront/src/Controller/ForgotPasswordController.php, apps/storefront/templates/forgot_password/**
allowed-tools: mcp__playwright__browser_navigate, mcp__playwright__browser_snapshot, mcp__playwright__browser_click, mcp__playwright__browser_type, mcp__playwright__browser_fill_form, Bash(curl:*), Bash(castor cc:*)
effort: low
---

## Prerequisites

Requires an already-registered, confirmed account (see `e2e-register`).

## Steps

1. Navigate to `http://localhost/storefront/forgot-password`.
2. Type the account's email into "Email address", click "Continue" → `/storefront/forgot-password/<id>/reset`.
3. Fetch the newest "Reset your password" message for that address from Mailpit (same pattern as `e2e-register`'s confirmation-code fetch, different subject) — body is `Your password reset code is: XXXXXX`.
4. Fill "Enter the security code", "New password (at least 16 characters)", "Confirm your new password" (16+ chars, matching, different from the old one), click "Reset".

## Expected result

Redirect to `/storefront/signin` with flash "Password reset, you can now sign in." Signing in afterward with the OLD password must fail; with the NEW password must succeed (through 2FA if enabled).

## Also check

- "Resend code" (`<twig:ui:SubmitLink>`) immediately after the initial request is throttled — "Please wait N seconds before requesting another code." (same `CooldownCalculator` mechanism as `e2e-register`'s confirmation resend, N shown live not hardcoded). Waiting and retrying issues a genuinely new code (verify via Mailpit — new message, new code, old code no longer accepted).
- A malformed/expired code on step 4 re-renders the reset form with a translated inline error, never a raw key or a 500.
