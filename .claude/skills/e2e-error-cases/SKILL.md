---
name: e2e-error-cases
description: End-to-end regression check of cross-cutting error handling — field validation, malformed/brute-forced verification codes, and CSRF — across the storefront Iam forms.
when_to_use: Verifying error paths still render correctly (translated, no raw key, no 500) after touching any Iam form Type/FormData, ConfirmationType/PasswordResetType, or the shared VerificationCodeType.
paths: apps/storefront/src/Form/**, apps/storefront/src/Controller/RegistrationController.php, apps/storefront/src/Controller/ForgotPasswordController.php
allowed-tools: mcp__playwright__browser_navigate, mcp__playwright__browser_snapshot, mcp__playwright__browser_click, mcp__playwright__browser_type, mcp__playwright__browser_fill_form, mcp__playwright__browser_evaluate, Bash(castor cc:*)
effort: low
---

## Field validation (server-side, not just HTML5)

Every text field in these forms carries an HTML5 `required`/`pattern`/`maxlength` constraint that silently blocks a naive Playwright `click()` on an empty/malformed value — that only proves the browser's own native validation, not the app's. To reach the real server-side path, either fill a value that satisfies the HTML5 constraint but violates the business rule (a short-but-non-empty password), or strip the constraint first via `browser_evaluate` (e.g. `document.querySelector('input[name*="code"]').removeAttribute('pattern')`) before typing an invalid value and submitting.

1. **Register** (`/storefront/register`): password < 16 chars → "This value is too short. It should have 16 characters or more." (`ValidPassword`). Password/confirm mismatch → "The passwords must match." Both return HTTP 422, both translated, no raw key.
2. **Confirm code** (`/storefront/register/<id>/confirm`, `pattern="\d{6}"`): strip the `pattern` attribute, type non-digit input → "This value is not valid.", HTTP 422.
3. **Password reset**: same shape as register's password field — verify independently if `PasswordResetType` ever diverges from `RegisterType`'s validation compound.

## Brute force — verification codes (confirm/reset)

`code_challenger.max_attempts` = 3 (`config/services/shared.php`, lowered from an unjustified `5` 2026-09-22 to match `login_throttling`'s own `max_attempts: 3` — no real reason for a 6-digit emailed code to tolerate more guesses than a password). Submit 4 wrong-but-well-formed codes in a row on the same confirm/reset page: attempts 1-3 each show "Incorrect code.", attempt 4 shows "Too many attempts. Request a new code below." — verified live 2026-09-22. Backed by `PredisVerificationCodeStore` (Redis/Valkey, outside the Doctrine transaction), not the old Dbal-backed store — the lockout is real, not a no-op (an earlier, now-stale `TODO.md` concern about a transaction rollback swallowing the attempts counter no longer applies).

## Brute force — sign-in and 2FA challenge (shared counter)

`security.main.login_throttling` (`max_attempts: 3`, `interval: '15 minutes'`) is **one shared counter across the whole authentication attempt, password step AND 2FA challenge pooled together** — not two separate limiters. A wrong password counts toward it; a wrong TOTP/backup code at `/2fa` (or `/a2f`) *also* counts toward the exact same counter. Don't conclude "2FA has no rate limiting" from only 1-2 wrong 2FA submissions on an identity that hasn't failed its password step this session — that undercounts the shared budget. To verify properly: exhaust the full budget (password failures + 2FA failures combined) on one identity in one session; the 3rd/4th cumulative failure shows "Too many failed login attempts, please try again in N minutes." on whichever step you're on when it trips — verified live 2026-09-22, tripped on a 2FA-step submission after one earlier password failure the same session. The correct password is also rejected during the lockout window, with a decreasing minute count.

## CSRF

`RegistrationController::confirmResend()`/`ForgotPasswordController::resetResend()` check the token manually (`isCsrfTokenValid()`), not via a Symfony Form. To test: find the resend link's hidden `_token` input (`document.querySelectorAll('input[type=hidden]')` via `browser_evaluate`), corrupt its value, then click "Resend code" — shows "Something went wrong. Please try again." (`flash_failed`, `verification_code` domain), no new code sent. Verified live 2026-09-22.

## IP/identity rate limit on code issuance (resend + initial request)

Added 2026-09-22 — closes a gap the per-identity 60s `CooldownCalculator` alone didn't: nothing previously stopped an automated client resending every 60s indefinitely. `Storefront\Security\RateLimiter\VerificationCodeRateLimiter` mirrors `Symfony\Component\Security\Http\RateLimiter\DefaultLoginRateLimiter`'s own dual shape (verified against its real source) — a tight per-identity+IP limiter (`verification_code_resend_identity`: 5/`15 minutes`) and a loose global per-IP-only limiter (`verification_code_resend_ip`: 25/`15 minutes`, `5×` the identity limit — the exact same multiplier `LoginThrottlingFactory` uses for its own global limiter), both consulted on every code-issuing action (`RegistrationController::confirmResend()`, `ForgotPasswordController::request()` and `resetResend()` — not the code-guessing actions, already covered by `code_challenger.max_attempts` above). Storage: a dedicated `cache.rate_limiter` pool, Redis-adapter-backed by the same Valkey instance `VerificationCode` already uses (confirmed live via `valkey-cli --scan` — new `nrQ1HGZWFh:*` keys appear per consumed attempt), but a separate connection (Symfony's `RedisAdapter` manages its own, doesn't reuse `shared.valkey.client`'s raw Predis instance). On rejection: "Too many requests. Please try again in N minutes." (`flash_rate_limited`, minutes — not seconds, since the window is 15 minutes, unlike the 60s cooldown's own seconds-based message). Runs *before* the existing per-identity cooldown check — verified live 2026-09-22 that a normal (non-rate-limited) resend still reaches the cooldown message unaffected. Exhausting the identity limit (5 real resends, each still gated by the pre-existing 60s cooldown so this takes several real minutes to reach) is the one sub-case not yet exercised live — config/DI wiring is source-verified instead (container compiles, real Valkey keys observed).
