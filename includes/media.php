<?php
/**
 * Media attachments for outbound WhatsApp messages.
 *
 * Both providers can attach a single image or document to a message by
 * pointing at a publicly fetchable URL (Cloud API `link`, Twilio
 * `MediaUrl`). To avoid turning the store into an SSRF proxy, only URLs on
 * the site's own host are accepted — i.e. media uploaded to the WordPress
 * media library.
 *
 * A media descriptor is the shape the dispatcher and providers consume:
 *   [ 'url' => string, 'type' => 'image'|'document', 'caption' => string,
 *     'filename' => string ]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map a MIME type to the WhatsApp media message type. Pure.
 *
 * Images send as 'image'; everything else (PDF, docs, audio…) as 'document'.
 *
 * @param string $mime
 * @return string 'image'|'document'
 */
function zignites_chat_normalize_media_type( $mime ) {
	return ( strpos( (string) $mime, 'image/' ) === 0 ) ? 'image' : 'document';
}

/**
 * Whether a media URL is http(s) and its host is in the allowlist. Pure.
 *
 * @param string   $url
 * @param string[] $allowed_hosts
 * @return bool
 */
function zignites_chat_media_url_host_allowed( $url, $allowed_hosts ) {
	if ( ! is_string( $url ) || $url === '' ) {
		return false;
	}
	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
		return false;
	}
	if ( ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
		return false;
	}
	$allowed = array_map( 'strtolower', array_filter( (array) $allowed_hosts ) );
	return in_array( strtolower( $parts['host'] ), $allowed, true );
}

/**
 * Validate a media URL: must be http(s) and on this site (providers fetch it
 * server-side, so only our own uploads are allowed).
 *
 * @param string $url
 * @return string The URL on success, '' otherwise.
 */
function zignites_chat_validate_media_url( $url ) {
	$url = is_string( $url ) ? trim( $url ) : '';
	if ( $url === '' ) {
		return '';
	}

	$allowed = array_filter( array(
		wp_parse_url( home_url(), PHP_URL_HOST ),
		wp_parse_url( site_url(), PHP_URL_HOST ),
	) );

	/**
	 * Filter the host allowlist for outbound media URLs. Useful for stores
	 * that serve uploads from a CDN / sister domain.
	 *
	 * @param string[] $allowed Default home/site hosts.
	 * @param string   $url     The URL being validated.
	 */
	$allowed = (array) apply_filters( 'zignites_chat_media_allowed_hosts', $allowed, $url );

	return zignites_chat_media_url_host_allowed( $url, $allowed ) ? $url : '';
}

/**
 * Build a validated media descriptor for the dispatcher, or null when the
 * URL is missing or not allowed.
 *
 * @param string $url      Public media URL (must be on this site).
 * @param string $mime     MIME type used to pick image vs document.
 * @param string $caption  Optional caption / message body.
 * @param string $filename Optional filename (documents); derived from the URL
 *                         when blank.
 * @return array{url:string,type:string,caption:string,filename:string}|null
 */
function zignites_chat_build_media_descriptor( $url, $mime = '', $caption = '', $filename = '' ) {
	$safe = zignites_chat_validate_media_url( $url );
	if ( $safe === '' ) {
		return null;
	}

	$filename = (string) $filename;
	if ( $filename === '' ) {
		$path     = (string) wp_parse_url( $safe, PHP_URL_PATH );
		$filename = $path !== '' ? basename( $path ) : 'attachment';
	}

	return array(
		'url'      => $safe,
		'type'     => zignites_chat_normalize_media_type( $mime ),
		'caption'  => (string) $caption,
		'filename' => $filename,
	);
}
