<?php
/**
 * WhatsApp approved message templates (HSM).
 *
 * Meta's Cloud API rejects free-form, business-initiated messages sent
 * outside the 24-hour customer-service window — cart recovery, follow-ups
 * and bulk campaigns therefore have to go out as a *pre-approved template*
 * (a.k.a. HSM) or the sender number's quality rating drops and it is
 * eventually blocked. This module lets an admin map each automated message
 * type to an approved template name + language + an ordered list of body
 * variables, and exposes a single helper the dispatcher consults at send
 * time.
 *
 * Storage: option `zignites_chat_wa_templates` (autoload off). Shape:
 *
 *   [
 *     'order' => [
 *       'enabled'   => 'yes'|'no',
 *       'name'      => 'order_confirmation',   // exact approved template name
 *       'language'  => 'en_US',
 *       'variables' => ['{name}', 'Order #{order_id}', '{total} {currency_symbol}'],
 *     ],
 *     'cart_recovery' => [ ... ],
 *     ...
 *   ]
 *
 * Each `variables` entry is a small template string that is placeholder-
 * substituted at send time (using the same {placeholder} values the free-
 * form message uses) to produce the body parameters {{1}}, {{2}}, … in
 * order. Free-form text is still rendered alongside — it remains the
 * fallback for Twilio and the source of the analytics/log preview.
 *
 * Templates only take effect when: the active provider is Cloud, the store
 * is Pro, and the per-type entry is enabled with a non-empty name.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Message types that support templates, with the {placeholders} each type
 * can resolve in its variable mappings. Mirrors what the corresponding
 * dispatcher substitutes for free-form text.
 *
 * @return array<string, array{label:string, placeholders: string[]}>
 */
function zignites_chat_wa_template_types() {
	$types = array(
		'order'         => array(
			'label'        => __( 'Order confirmation', 'zignites-chat' ),
			'placeholders' => array( '{name}', '{order_id}', '{total}', '{currency_symbol}' ),
		),
		'cart_recovery' => array(
			'label'        => __( 'Cart recovery', 'zignites-chat' ),
			'placeholders' => array( '{items}', '{total}', '{currency_symbol}', '{cart_url}' ),
		),
		'followup'      => array(
			'label'        => __( 'Post-order follow-up', 'zignites-chat' ),
			'placeholders' => array( '{name}', '{order_id}', '{total}', '{status}', '{date}', '{currency_symbol}' ),
		),
		'bulk'          => array(
			'label'        => __( 'Bulk campaign', 'zignites-chat' ),
			'placeholders' => array( '{name}', '{site}', '{currency_symbol}' ),
		),
	);

	/**
	 * Filter the template-capable message types.
	 *
	 * @param array $types Type key => label + placeholders.
	 */
	return (array) apply_filters( 'zignites_chat_wa_template_types', $types );
}

/**
 * Default (disabled) entry for a single message type.
 *
 * @return array{enabled:string, name:string, language:string, variables:array}
 */
function zignites_chat_wa_template_default_entry() {
	return array(
		'enabled'   => 'no',
		'name'      => '',
		'language'  => 'en_US',
		'variables' => array(),
	);
}

/**
 * Read the full template map, with a default entry filled in for every
 * known type so callers never have to null-check.
 *
 * @return array<string, array{enabled:string, name:string, language:string, variables:array}>
 */
function zignites_chat_get_wa_templates() {
	$stored = get_option( 'zignites_chat_wa_templates', array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	$out = array();
	foreach ( array_keys( zignites_chat_wa_template_types() ) as $type ) {
		$entry      = isset( $stored[ $type ] ) && is_array( $stored[ $type ] ) ? $stored[ $type ] : array();
		$out[ $type ] = array_merge( zignites_chat_wa_template_default_entry(), $entry );
	}
	return $out;
}

/**
 * Read a single message type's template entry.
 *
 * @param string $type One of the keys from zignites_chat_wa_template_types().
 * @return array{enabled:string, name:string, language:string, variables:array}
 */
function zignites_chat_get_wa_template( $type ) {
	$all = zignites_chat_get_wa_templates();
	return $all[ $type ] ?? zignites_chat_wa_template_default_entry();
}

/**
 * Whether a template should actually be used for the given message type on
 * this request.
 *
 * Requires all of: Pro active, the Cloud provider selected (templates are a
 * Cloud-API concept in this release — Twilio content templates are a later
 * task), and a per-type entry that is enabled with a non-empty name.
 *
 * @param string $type Message type key.
 * @return bool
 */
function zignites_chat_wa_template_is_active( $type ) {
	if ( ! function_exists( 'zignites_chat_is_pro_active' ) || ! zignites_chat_is_pro_active() ) {
		return false;
	}
	if ( get_option( 'zignites_chat_api_provider', 'twilio' ) !== 'cloud' ) {
		return false;
	}
	$entry = zignites_chat_get_wa_template( $type );
	return ( ( $entry['enabled'] ?? 'no' ) === 'yes' ) && trim( (string) ( $entry['name'] ?? '' ) ) !== '';
}

/**
 * Resolve a list of variable expressions against a placeholder => value map.
 *
 * Pure: each expression has every {placeholder} replaced by its value; the
 * result is returned in the same order so it maps cleanly to {{1}}, {{2}}, …
 * WhatsApp body parameters cannot contain newlines or 4+ consecutive
 * spaces, so those are collapsed defensively.
 *
 * @param string[]              $expressions Ordered variable templates.
 * @param array<string, scalar> $values      Placeholder => replacement value.
 * @return string[] Resolved, order-preserving body parameter values.
 */
function zignites_chat_wa_template_resolve_values( $expressions, $values ) {
	if ( ! is_array( $expressions ) ) {
		return array();
	}

	$search  = array();
	$replace = array();
	foreach ( (array) $values as $needle => $val ) {
		$search[]  = $needle;
		$replace[] = (string) $val;
	}

	$out = array();
	foreach ( $expressions as $expr ) {
		$resolved = str_replace( $search, $replace, (string) $expr );
		// Cloud API rejects body params containing newlines or runs of 4+
		// spaces — normalise whitespace to a single space.
		$resolved = trim( preg_replace( '/\s{2,}/', ' ', str_replace( array( "\r", "\n", "\t" ), ' ', $resolved ) ) );
		$out[]    = $resolved;
	}
	return $out;
}

/**
 * Build the Cloud API `components` array from resolved body values.
 *
 * Pure. Returns an empty array when there are no variables (a template with
 * no body parameters is valid — the components key is simply omitted).
 *
 * @param string[] $resolved_values Ordered body parameter values.
 * @return array Components array for the template envelope.
 */
function zignites_chat_wa_template_build_components( $resolved_values ) {
	if ( ! is_array( $resolved_values ) || empty( $resolved_values ) ) {
		return array();
	}

	$parameters = array();
	foreach ( $resolved_values as $value ) {
		$parameters[] = array(
			'type' => 'text',
			'text' => (string) $value,
		);
	}

	return array(
		array(
			'type'       => 'body',
			'parameters' => $parameters,
		),
	);
}

/**
 * If a template is active for $type, augment $context with a `template`
 * descriptor the dispatcher will send instead of free-form text. A no-op
 * (returns $context unchanged) when no template applies, so callers can
 * wrap every send unconditionally.
 *
 * @param string                $type    Message type key.
 * @param array<string, scalar> $values  Placeholder => value map (same one
 *                                        used to render the free-form body).
 * @param array                 $context Existing dispatcher context.
 * @return array Context, possibly with a 'template' key added.
 */
function zignites_chat_maybe_apply_template( $type, $values, $context = array() ) {
	if ( ! zignites_chat_wa_template_is_active( $type ) ) {
		return $context;
	}

	$entry    = zignites_chat_get_wa_template( $type );
	$resolved = zignites_chat_wa_template_resolve_values( $entry['variables'], $values );

	$context['template'] = array(
		'name'       => trim( (string) $entry['name'] ),
		'language'   => trim( (string) $entry['language'] ) !== '' ? trim( (string) $entry['language'] ) : 'en_US',
		'components' => zignites_chat_wa_template_build_components( $resolved ),
	);
	return $context;
}

/**
 * Sanitize callback for the `zignites_chat_wa_templates` option.
 *
 * Drops unknown types, coerces enabled to yes/no, trims name/language, and
 * caps the variable list (Cloud API allows a generous but finite number of
 * body params; 15 is comfortably above any sane template).
 *
 * @param mixed $raw Raw submitted value.
 * @return array Clean template map.
 */
function zignites_chat_sanitize_wa_templates( $raw ) {
	$clean = array();
	$types = zignites_chat_wa_template_types();

	if ( ! is_array( $raw ) ) {
		return $clean;
	}

	foreach ( $types as $type => $_meta ) {
		$entry = isset( $raw[ $type ] ) && is_array( $raw[ $type ] ) ? $raw[ $type ] : array();

		$variables = array();
		if ( isset( $entry['variables'] ) && is_array( $entry['variables'] ) ) {
			foreach ( $entry['variables'] as $expr ) {
				$expr = sanitize_text_field( (string) $expr );
				if ( $expr !== '' ) {
					$variables[] = $expr;
				}
				if ( count( $variables ) >= 15 ) {
					break;
				}
			}
		}

		$clean[ $type ] = array(
			'enabled'   => ( isset( $entry['enabled'] ) && $entry['enabled'] === 'yes' ) ? 'yes' : 'no',
			'name'      => sanitize_text_field( (string) ( $entry['name'] ?? '' ) ),
			'language'  => sanitize_text_field( (string) ( $entry['language'] ?? 'en_US' ) ),
			'variables' => $variables,
		);
	}

	return $clean;
}
