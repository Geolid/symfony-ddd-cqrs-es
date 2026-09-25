---
name: e2e-2fa-enable
description: End-to-end regression check of the 2FA settings + trusted devices pages — first-time enrollment, backup-code regeneration, disabling, and per-device/all-device revocation.
when_to_use: Verifying apps/storefront's 2FA settings tunnel still works after touching Account/SecurityController, TotpEnroller, BackupCodeRegenerator, Security/Provider/TrustedDeviceManager, or account/security/*.html.twig.
paths: apps/storefront/src/Controller/Account/**, apps/storefront/templates/account/security/**, src/Iam/Authentication/**
allowed-tools: mcp__playwright__browser_navigate, mcp__playwright__browser_snapshot, mcp__playwright__browser_click, mcp__playwright__browser_type, Bash(node:*), Bash(castor cc:*)
effort: low
---

## Prerequisites

Requires an already signed-in session (see `e2e-signin`).

## TOTP code generator

`tools/e2e/totp.mjs` — RFC 6238, no npm deps, reusable across every future e2e session.

```bash
node tools/e2e/totp.mjs <base32-secret>          # code for right now
node tools/e2e/totp.mjs <base32-secret> <epoch>  # code for a specific unix timestamp (debugging only)
```

## Architecture (2026-09-23)

One route (`storefront_account_security_two_factor_settings`, `/account/security/2fa/settings` en / `/compte/connexion-securite/a2f/parametres` fr) serves TWO different pages depending on state — the controller branches on whether a `TotpCredential` already exists, there's no separate "enable" URL:
- **Not yet enrolled**: the enrollment wizard (QR code, manual-entry `<details>`, code field) — same content previously called "issue_totp".
- **Already enrolled**: a settings page with 3 sections — regenerate backup codes, view trusted devices, disable 2FA. "Trusted devices" now lives HERE, not on the general `/account/security` page — it only makes sense in the context of 2FA.

The general `/account/security` page only shows a single "Two-factor authentication" row with a "Configure" button (always the same label, regardless of state) pointing at the same settings URL.

Trusted devices got their own dedicated page (2026-09-25, replacing the old single "Sign out of all devices" action): each device is its own `TrustedDevice` aggregate (identity id, a per-device opaque version int reused as the scheb JWT cookie's own `vsn` claim, user-agent, IP, trusted/revoked timestamps) — not a single per-identity floor timestamp any more. `Storefront\Security\Provider\TrustedDeviceManager` (a custom `Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface`) issues one cookie entry per device and checks incoming cookies against every active device's own version via the vendor's own `scheb_two_factor.trusted_token_storage` service — no custom cookie/JWT code, the vendor's own storage is reused as-is.

Backup codes (the vendor's own term — `BackupCodeCredential`, `backup_code` provider alias — kept in the UI too, since GitHub's own "recovery codes" turned out to be a misleading label for a mechanism that only bypasses 2FA, not a general account-recovery tool) are only ever shown **freshly generated**: at first enrollment, or right after clicking "Generate new backup codes". There is no way to re-view an already-generated set — they're one-way hashed (`NativePasswordHasher`), not reversibly stored, so "view existing codes" is architecturally impossible without weakening storage security.

## Steps — first-time enrollment

1. Navigate to `http://localhost/storefront/account/security`.
2. Click "Configure" next to "Two-factor authentication" → `/storefront/account/security/2fa/settings` (enrollment wizard, since not yet enrolled).
3. Expand "Can't scan the QR code?" and read the secret (base32, shown chunked in groups of 4 for readability — strip spaces before generating a code).
4. Generate the current code: `node tools/e2e/totp.mjs <secret-without-spaces>`.
5. Type it into "Code" (the field's own label is visually hidden — reachable via its accessible name in a snapshot, not visible on screen), click "Verify code and continue".
6. Page shows "Save Your Backup Codes" — read and keep the list of 8-digit codes (shown once, never again).
7. Click "← Back to 2FA settings" → the settings page.

## Steps — settings page once enrolled

1. Navigate to `/storefront/account/security/2fa/settings` directly (or via "Configure") — now renders the 3-section settings page instead of the wizard.
2. **Backup codes section**: shows "Generated on `<date>`. `<N>` codes remaining." (the date shown is `regeneratedAt ?? generatedAt` — always the current pool's own creation date, never both dates at once). Click "Generate new backup codes" → shows "Your New Backup Codes" with a fresh list — the OLD codes stop working immediately (regeneration replaces the whole pool, verify one of the old codes is rejected afterward if testing thoroughly).
3. **Trusted devices section**: a "View" button (never the destructive action directly) → `/2fa/devices`, a dedicated page listing every active device as its own card (user-agent, IP, trusted date), each with its own red "Revoke" button, plus a separate "Revoke all devices" section at the bottom (own `<hr>`, warning icon, same danger-button style as "Disable 2FA"). Revoking a device or all devices redirects back to `/2fa/devices` (not the general security page) on success. Signing back in from a revoked device re-adds it as a brand-new card (new id, new trusted date) — a revoked device is never un-revoked, matching how re-enrolling a TOTP after unenrolling mints a new credential rather than reviving the old one.
4. **Disable section**: red "Disable 2FA" button → dispatches `UnenrollTotp`, redirects to `/storefront/account/security`, flash "Two-factor authentication disabled." Visiting `/2fa/settings` again afterward shows the enrollment wizard again (state reverted to not-enrolled).

## Expected result

`/storefront/account/security` always shows a single "Two-factor authentication" row with "Configure" — never a live enabled/disabled status text on this page (that detail lives on the settings page itself, implicitly: seeing the 3-section layout instead of the wizard IS the "enabled" signal).

## Also check

- A wrong 6-digit code during enrollment re-renders the wizard with a translated inline error, secret unchanged, never a raw key or a 500.
- The secret's clock step is 30s — if a generated code gets rejected as stale, regenerate immediately before submitting (network round-trip can cross a step boundary).
- Re-enrolling after a previous disable, when a backup-code pool already exists from before: skips straight to "Two-factor authentication enabled. Your existing backup codes are still valid." (redirects to `/2fa/settings`, no new codes page) — `TotpEnroller::enrollFor()` only issues a fresh pool when none exists yet for that identity.
