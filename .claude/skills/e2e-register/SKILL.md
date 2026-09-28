---
name: e2e-register
description: End-to-end regression check of Iam.Identity registration — register, receive confirmation code via Mailpit, confirm.
when_to_use: Verifying the storefront registration tunnel still works after touching Identity/RegistrationController or its templates.
paths: apps/storefront/src/Controller/RegistrationController.php, apps/storefront/templates/registration/**
allowed-tools: mcp__playwright__browser_navigate, mcp__playwright__browser_snapshot, mcp__playwright__browser_click, mcp__playwright__browser_type, mcp__playwright__browser_fill_form, Bash(curl:*), Bash(castor cc:*)
effort: low
---

## Prerequisites

- `docker compose ps` shows `app`/`db`/`nginx`/`mailer` up.
- Base URL: `http://localhost/storefront/`. Mailpit API: `http://localhost:8025/api/v1/`.
- Use a fresh, never-used email per run (e.g. `e2e.register.<random>@example.test`) — a second registration attempt on an already-confirmed email hits the "email known" branch instead.

## Steps

1. Navigate to `http://localhost/storefront/signin`.
2. Type the fresh email into the "Enter your email address" textbox, click "Continue".
3. Page shows "This email is new to us" — click "Create your account".
4. On `/storefront/register`, fill "Your name", "Password (at least 16 characters)", "Type your password again" (16+ chars, matching), click "Continue".
5. Redirects to `/storefront/register/<id>/confirm`. Fetch the code:
   ```bash
   curl -s http://localhost:8025/api/v1/messages | python3 -c "
   import json,sys
   data = json.load(sys.stdin)
   for m in data['messages']:
       if m['To'][0]['Address'] == '<the-email>' and m['Subject'] == 'Confirm your email address':
           print(m['ID']); break
   "
   ```
   then
   ```bash
   curl -s http://localhost:8025/api/v1/message/<id> | python3 -c "import json,sys; print(json.load(sys.stdin)['Text'])"
   ```
   — body is `Your confirmation code is: XXXXXX`.
6. Type the code into "Enter the security code", click "Create your account".

## Expected result

Redirect to `/storefront/signin` with flash "Account created, you can now sign in." — no 500, no raw untranslated key, no PHP warning in the page.

## Also check

- "Resend code" link (a `<twig:ui:SubmitLink>` component, not a real `<a href>`) issues a second code and shows "A code has been sent." unless throttled — "Please wait N seconds before requesting another code." (N computed live from `ConfirmationRequestedTooRecentlyException::$retryAt`, a 60s cooldown by default — `Shared\Domain\Service\CooldownCalculator`).
- A wrong/expired code shows a translated inline error, never a raw translation key or a 500.
