<?php
/**
 * Two-way team inbox — inbound capture (increment I2).
 *
 * Records incoming WhatsApp messages from Meta Cloud and Twilio into
 * conversation threads (see includes/inbox.php for the storage layer).
 *
 * Both providers already deliver to the shared, signature-verified
 * `zignites-chat/v1/optout` endpoint; this file adds a friendlier
 * `zignites-chat/v1/inbound` alias to the same handler and hooks the capture
 * step into it. Opt-out keyword handling is untouched — a message that is
 * also an opt-out keyword is recorded in the thread first, then opted out.
 *
 * The payload→message normalizers are pure and unit-tested; the capture
 * orchestrator + dedupe touch the database.
 *
 * @package Zignites_Chat
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    // Dedicated inbound alias. Reuses the opt-out handler (same signature /
    // token verification, receipt ingestion, and — now — inbox capture), so
    // a Twilio number or Meta app can point its "incoming message" webhook
    // here instead of at /optout.
    register_rest_route('zignites-chat/v1', '/inbound', [
        'methods'             => 'POST',
        'callback'            => 'zignites_chat_optout_webhook_handler',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('zignites-chat/v1', '/inbound', [
        'methods'             => 'GET',
        'callback'            => 'zignites_chat_meta_webhook_verify',
        'permission_callback' => '__return_true',
    ]);
});

/* -------------------------------------------------------------------------
 * Pure normalizers (no DB, no globals) — unit-tested
 * ----------------------------------------------------------------------- */

/**
 * Normalize a Twilio inbound webhook form payload into a message record.
 *
 * Twilio posts WhatsApp inbound messages as form params: From ("whatsapp:+E164"),
 * Body, MessageSid, and (for WhatsApp) ProfileName. Returns null when the
 * payload carries no usable from/body pair.
 *
 * @param array $params Twilio form body params.
 * @return array|null Normalized message for zignites_chat_inbox_record_message(), or null.
 */
function zignites_chat_inbox_normalize_twilio_inbound($params) {
    if (!is_array($params)) {
        return null;
    }
    $from = isset($params['From']) ? (string) $params['From'] : '';
    $body = isset($params['Body']) ? (string) $params['Body'] : '';
    $phone = zignites_chat_normalize_phone($from);
    if ($phone === '' || trim($body) === '') {
        return null;
    }
    return [
        'phone'         => $phone,
        'direction'     => 'in',
        'body'          => $body,
        'provider'      => 'twilio',
        'message_id'    => isset($params['MessageSid']) ? (string) $params['MessageSid'] : '',
        'status'        => 'received',
        'customer_name' => isset($params['ProfileName']) ? (string) $params['ProfileName'] : '',
    ];
}

/**
 * Extract a human-readable body from a single Meta Cloud message object.
 *
 * Handles the common inbound types: text, button replies, interactive
 * button/list replies, and media (caption when present, else a bracketed
 * type placeholder so the thread shows something meaningful).
 *
 * @param array $message One element of value.messages[].
 * @return string Best-effort message body.
 */
function zignites_chat_inbox_extract_meta_message_body($message) {
    if (!is_array($message)) {
        return '';
    }
    $type = isset($message['type']) ? (string) $message['type'] : 'text';

    switch ($type) {
        case 'text':
            return isset($message['text']['body']) ? (string) $message['text']['body'] : '';
        case 'button':
            return isset($message['button']['text']) ? (string) $message['button']['text'] : '';
        case 'interactive':
            $interactive = $message['interactive'] ?? [];
            if (isset($interactive['button_reply']['title'])) {
                return (string) $interactive['button_reply']['title'];
            }
            if (isset($interactive['list_reply']['title'])) {
                return (string) $interactive['list_reply']['title'];
            }
            return '';
        case 'image':
        case 'document':
        case 'audio':
        case 'video':
        case 'sticker':
            $caption = isset($message[$type]['caption']) ? (string) $message[$type]['caption'] : '';
            return $caption !== '' ? $caption : '[' . $type . ']';
        case 'location':
            return '[location]';
        default:
            return '[' . $type . ']';
    }
}

/**
 * Normalize a Meta Cloud webhook payload into a list of inbound messages.
 *
 * Walks entry[].changes[].value.messages[], pulling the sender phone, body
 * (via the type-aware extractor), provider message id, and display name from
 * the matching value.contacts[] entry. Status-only payloads (delivery/read
 * receipts) carry no messages[] and yield an empty list.
 *
 * @param array $payload Decoded Meta webhook body.
 * @return array<int, array> Normalized messages for zignites_chat_inbox_record_message().
 */
function zignites_chat_inbox_normalize_meta_messages($payload) {
    if (!is_array($payload) || empty($payload['entry']) || !is_array($payload['entry'])) {
        return [];
    }

    $out = [];
    foreach ($payload['entry'] as $entry) {
        if (!is_array($entry) || empty($entry['changes']) || !is_array($entry['changes'])) {
            continue;
        }
        foreach ($entry['changes'] as $change) {
            $value = (is_array($change) && isset($change['value']) && is_array($change['value'])) ? $change['value'] : [];
            if (empty($value['messages']) || !is_array($value['messages'])) {
                continue;
            }

            // Map wa_id => profile name from the contacts block.
            $names = [];
            if (!empty($value['contacts']) && is_array($value['contacts'])) {
                foreach ($value['contacts'] as $contact) {
                    if (!is_array($contact)) continue;
                    $wa_id = isset($contact['wa_id']) ? zignites_chat_normalize_phone($contact['wa_id']) : '';
                    $name  = isset($contact['profile']['name']) ? (string) $contact['profile']['name'] : '';
                    if ($wa_id !== '' && $name !== '') {
                        $names[$wa_id] = $name;
                    }
                }
            }

            foreach ($value['messages'] as $message) {
                if (!is_array($message)) continue;
                $phone = isset($message['from']) ? zignites_chat_normalize_phone($message['from']) : '';
                if ($phone === '') continue;

                $body = zignites_chat_inbox_extract_meta_message_body($message);
                $out[] = [
                    'phone'         => $phone,
                    'direction'     => 'in',
                    'body'          => $body,
                    'provider'      => 'cloud',
                    'message_id'    => isset($message['id']) ? (string) $message['id'] : '',
                    'status'        => 'received',
                    'customer_name' => $names[$phone] ?? '',
                ];
            }
        }
    }
    return $out;
}

/* -------------------------------------------------------------------------
 * Capture orchestrator (DB) — called from the webhook handler
 * ----------------------------------------------------------------------- */

/**
 * Record any inbound messages carried by a verified webhook request.
 *
 * Detects a Twilio form payload (From param) vs a Meta JSON payload, normalizes
 * the inbound message(s), and records each into its thread — deduping on the
 * provider message id so webhook retries don't create duplicate rows.
 *
 * Called from zignites_chat_optout_webhook_handler() AFTER signature/token
 * verification, so this function performs no auth of its own. Gated on Pro
 * since the inbox is a Pro feature.
 *
 * @param WP_REST_Request $request Verified webhook request.
 * @return int Number of messages recorded.
 */
function zignites_chat_inbox_capture_request($request) {
    if (function_exists('zignites_chat_is_pro_active') && !zignites_chat_is_pro_active()) {
        return 0;
    }

    $messages = [];

    // Twilio inbound arrives as form params (From / Body / MessageSid).
    $params = $request->get_body_params();
    if (is_array($params) && !empty($params['From'])) {
        $msg = zignites_chat_inbox_normalize_twilio_inbound($params);
        if ($msg !== null) {
            $messages[] = $msg;
        }
    } else {
        // Otherwise try a Meta Cloud JSON payload.
        $raw  = (string) $request->get_body();
        $json = $raw !== '' ? json_decode($raw, true) : null;
        if (is_array($json)) {
            $messages = zignites_chat_inbox_normalize_meta_messages($json);
        }
    }

    $recorded = 0;
    foreach ($messages as $msg) {
        if (!empty($msg['message_id']) && zignites_chat_inbox_inbound_exists($msg['message_id'])) {
            continue; // Duplicate webhook delivery.
        }
        $result = zignites_chat_inbox_record_message($msg);
        if (!is_wp_error($result)) {
            $recorded++;
        }
    }
    return $recorded;
}
