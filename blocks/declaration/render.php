<?php
/**
 * Server render bloku: stejny vystup jako shortcode [tapp_cookie_declaration].
 *
 * @var array $attributes
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$locale = isset( $attributes['locale'] ) ? sanitize_text_field( (string) $attributes['locale'] ) : '';
$styles = ! isset( $attributes['styles'] ) || $attributes['styles'] ? 'true' : 'false';
echo do_shortcode( '[tapp_cookie_declaration locale="' . esc_attr( $locale ) . '" styles="' . $styles . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode escapuje sam
