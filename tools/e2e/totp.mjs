#!/usr/bin/env node
// RFC 6238 TOTP code generator for e2e testing (no external deps).
// Usage: node tools/e2e/totp.mjs <base32-secret> [timestamp-seconds]

import { createHmac } from 'node:crypto';

const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function base32Decode(input) {
    const clean = input.replace(/=+$/, '').toUpperCase();
    let bits = '';
    for (const char of clean) {
        const value = BASE32_ALPHABET.indexOf(char);
        if (value === -1) {
            throw new Error(`Invalid base32 character: ${char}`);
        }
        bits += value.toString(2).padStart(5, '0');
    }
    const bytes = [];
    for (let i = 0; i + 8 <= bits.length; i += 8) {
        bytes.push(parseInt(bits.slice(i, i + 8), 2));
    }
    return Buffer.from(bytes);
}

export function totp(secret, timestampSeconds = Math.floor(Date.now() / 1000), digits = 6, stepSeconds = 30) {
    const key = base32Decode(secret);
    const counter = Math.floor(timestampSeconds / stepSeconds);

    const counterBuffer = Buffer.alloc(8);
    counterBuffer.writeUInt32BE(Math.floor(counter / 2 ** 32), 0);
    counterBuffer.writeUInt32BE(counter >>> 0, 4);

    const hmac = createHmac('sha1', key).update(counterBuffer).digest();
    const offset = hmac[hmac.length - 1] & 0xf;
    const binary =
        ((hmac[offset] & 0x7f) << 24) |
        ((hmac[offset + 1] & 0xff) << 16) |
        ((hmac[offset + 2] & 0xff) << 8) |
        (hmac[offset + 3] & 0xff);

    return String(binary % 10 ** digits).padStart(digits, '0');
}

if (import.meta.url === `file://${process.argv[1]}`) {
    const [, , secret, timestampArg] = process.argv;
    if (!secret) {
        console.error('Usage: node tools/e2e/totp.mjs <base32-secret> [timestamp-seconds]');
        process.exit(1);
    }
    const timestamp = timestampArg ? Number(timestampArg) : undefined;
    console.log(totp(secret, timestamp));
}
