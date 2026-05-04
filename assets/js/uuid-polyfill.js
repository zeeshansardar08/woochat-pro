// WooChat Pro – crypto.randomUUID() polyfill.
// Older Safari (< 15.4), Firefox (< 95), Chrome (< 92) ship Web Crypto
// without randomUUID. Some downstream code (queue ids, click trackers,
// chatbot session tokens, embedded checkout flows) assumes the modern
// API; this provides a drop-in shim so those code paths don't blow up
// on otherwise-fine browsers.
//
// Loaded as a real enqueued asset rather than an inline <script> in the
// page head so it works under strict CSP and does not ship to every
// visitor when the chatbot / cart-recovery features are off.

(function () {
    'use strict';

    if (typeof window === 'undefined') return;

    if (typeof window.crypto === 'undefined') {
        window.crypto = {};
    }

    if (typeof window.crypto.randomUUID === 'function') return;

    window.crypto.randomUUID = function () {
        var bytes = new Uint8Array(16);
        var getRandomValues = (window.crypto && window.crypto.getRandomValues)
            ? window.crypto.getRandomValues.bind(window.crypto)
            : null;

        if (getRandomValues) {
            getRandomValues(bytes);
            // RFC 4122 v4: set version + variant bits.
            bytes[6] = (bytes[6] & 0x0f) | 0x40;
            bytes[8] = (bytes[8] & 0x3f) | 0x80;
            var hex = [];
            for (var i = 0; i < 16; i++) {
                hex.push(bytes[i].toString(16).padStart(2, '0'));
            }
            return hex[0] + hex[1] + hex[2] + hex[3] + '-' +
                   hex[4] + hex[5] + '-' +
                   hex[6] + hex[7] + '-' +
                   hex[8] + hex[9] + '-' +
                   hex[10] + hex[11] + hex[12] + hex[13] + hex[14] + hex[15];
        }

        // Fallback when getRandomValues is unavailable. NOT cryptographically
        // secure — only reachable on environments without Web Crypto at all.
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    };
})();
