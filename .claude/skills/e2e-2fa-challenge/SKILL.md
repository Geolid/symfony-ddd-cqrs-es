---
name: e2e-2fa-challenge
description: End-to-end regression check of the 2FA challenge at sign-in — TOTP, backup code, switching between them, invalid code, and "remember this device".
when_to_use: Verifying apps/storefront's two_factor/*.html.twig templates and BackupCodeTwoFactorProvider/TotpTwoFactorProvider still work together.
paths: apps/storefront/templates/two_factor/**, apps/storefront/src/Security/Provider/**
allowed-tools: mcp__playwright__browser_navigate, mcp__playwright__browser_snapshot, mcp__playwright__browser_click, mcp__playwright__browser_type, mcp__playwright__browser_run_code_unsafe, Bash(node:*), Bash(castor cc:*)
effort: low
---

## Prerequisites

Requires an account with TOTP + backup codes already issued (see `e2e-2fa-enable`), signed in past the password step, currently on `/storefront/2sv`.

## Steps — TOTP path

1. If the page shows "Backup code", click "Use my authenticator app" → `?preferProvider=totp`.
2. Generate the current code: `node tools/e2e/totp.mjs <secret>` (same secret as `e2e-2fa-enable`).
3. Type it into "Authentication code", click "Verify" → lands on `/storefront/account` (or wherever `default_target_path` points).

## Steps — backup code path

1. If the page shows "Authentication code", click "Use a backup code" → `?preferProvider=backup_code`.
2. Type one never-used 8-digit backup code into "Backup code", click "Verify".
3. Re-check `/storefront/account/security` — "N codes remaining" must have decremented by exactly 1. Re-submitting the SAME backup code afterward must be rejected (already consumed).

## Steps — remember this device

1. On either path, check "Remember this device for 30 days" before clicking "Verify".
2. Log out (`/storefront/logout`), sign in again with email+password — must land directly past `/storefront/2sv` with no challenge shown.
3. On `/storefront/account/security`, click "Sign out of all devices" — flash "You've been signed out of all remembered devices...".
4. Log out, sign in again — the 2FA challenge must reappear.

**Don't stop at step 2 as proof.** Staying in the same Playwright browser context the whole time doesn't by itself prove the *trusted-device cookie* is what's skipping the challenge — cookies persist naturally across `browser_navigate` calls regardless. To actually isolate the cause: after confirming step 2's skip, clear cookies for real (including httpOnly ones, which `document.cookie` can't touch) via `browser_run_code_unsafe`:
```js
async (page) => { await page.context().clearCookies(); }
```
then sign in again with the same identity — the 2FA challenge must reappear (verified live 2026-09-22: it does). That's the actual proof the cookie, not some other session artifact, is the mechanism. Also worth remembering: `APP_SECRET` signs the trusted-device cookie — rotating it (`.env.local`) invalidates every previously-issued one, so a "device no longer remembered" symptom right after touching that secret is expected, not a regression.

## Steps — invalid code

1. Type an obviously wrong code (`000000` for TOTP, `INVALIDCD` for backup code), click "Verify".
2. Page re-renders the same challenge with a translated inline error (scheb's own `SchebTwoFactorBundle` translation domain — `code_invalid` → "The verification code is not valid."). Never a raw key, never a 500.

## Steps — brute force

1. Submit several wrong codes in a row (TOTP or backup code, either counts — same shared counter as the password step's `login_throttling`, cumulative across the whole login attempt, not a separate 2FA-only limiter). A couple of wrong 2FA codes alone may not be enough to trip it if the password step itself already succeeded on the first try — the threshold (`max_attempts: 3`, `security.main.login_throttling`) counts every failure since the last success, password and 2FA failures pooled together.
2. Eventually shows the same "Too many failed login attempts, please try again in N minutes." as the password-step lockout — verified live 2026-09-22 (4th cumulative failure on a fresh identity). Don't conclude "no rate limiting" from only 1-2 wrong 2FA attempts — that undercounts the shared budget.

## Expected result

Every combination above renders correctly; the switch link always names the OTHER provider, never the current one; the field's `pattern`/format matches the provider actually shown (6 digits for TOTP, 8 for backup code).

## Locale

The route is locale-aware: `/2sv` (en) / `/v2e` (fr) — matching the app's standardized terminology ("Two-step verification" / "Vérification en deux étapes", 2026-09-22), not the older "authentification à deux facteurs" wording the previous `/2fa`/`/a2f` paths were based on. Distinct path text per locale is what makes the FR/EN switcher actually work here (Symfony's sticky-locale URL generation needs the path text to genuinely differ per locale; an identical path for both locales silently collapses to whichever locale was declared first, and a bare `?_locale=` query string on a route with no locale-aware path does nothing at all — both were tried and confirmed broken before landing on distinct paths). Signing in through the French tunnel (`/connexion` → `/connexion/verifier`) must redirect to `/v2e`, not `/2sv`.

