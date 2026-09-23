---
name: e2e-2fa-enable
description: End-to-end regression check of enabling TOTP 2FA — scan/enter secret, confirm with a real generated code, receive and record backup codes.
when_to_use: Verifying apps/storefront's 2FA-enable tunnel still works after touching Account/SecurityController, TotpIssuer, or account/security/*.html.twig.
paths: apps/storefront/src/Controller/Account/**, apps/storefront/templates/account/security/**, src/Iam/Authentication/**
allowed-tools: mcp__playwright__browser_navigate, mcp__playwright__browser_snapshot, mcp__playwright__browser_click, mcp__playwright__browser_type, Bash(node:*), Bash(castor cc:*)
effort: low
---

## Prerequisites

Requires an already signed-in session (see `e2e-signin`) with no TOTP credential issued yet.

## TOTP code generator

`tools/e2e/totp.mjs` — RFC 6238, no npm deps, reusable across every future e2e session.

```bash
node tools/e2e/totp.mjs <base32-secret>          # code for right now
node tools/e2e/totp.mjs <base32-secret> <epoch>  # code for a specific unix timestamp (debugging only)
```

## Steps

1. Navigate to `http://localhost/storefront/account/security`.
2. Click "Enable" next to "Two-step verification" → `/storefront/account/security/2sv/enable`.
3. Read the secret from the "Or enter this key manually:" paragraph (a base32 string).
4. Generate the current code: `node tools/e2e/totp.mjs <secret>`.
5. Type it into "Code", click "Confirm".
6. Page shows "Your backup codes" with a flash "Two-factor authentication enabled." — read and keep the list of 8-digit codes (shown once, never again).
7. Click "I've saved my codes" → `/storefront/account`.

## Expected result

`/storefront/account/security` now shows:
- "Two-step verification": "Two-factor authentication enabled."
- "Backup codes": "Issued on `<today's date>`. `<N>` codes remaining." (`N` = the container's `iam.authentication.backup_code_count` parameter).

## Also check

- A wrong 6-digit code at step 5 re-renders the enable form with a translated inline error, secret unchanged, never a raw key or a 500.
- The secret's clock step is 30s — if a generated code gets rejected as stale, regenerate immediately before submitting (network round-trip can cross a step boundary).
