<?php
/**
 * Zavislosti a verze editoru bloku (WordPress je nacte misto automatickeho seznamu z buildu).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
	'version'      => '0.2.0',
);
