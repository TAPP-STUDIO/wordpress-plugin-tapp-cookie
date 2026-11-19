<?php
/**
 * Odinstalace: smaze nastaveni pluginu (v multisite na vsech webech). Souhlasy navstevniku jsou
 * v prohlizeci (cookie) a v consent logu tapp cookie - ty se odinstalaci pluginu nemeni.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/** Klice, ktere plugin zaklada (vcetne starych z kostry a stavu kontroly aktualizaci). */
function tapp_cookie_delete_options(): void {
	delete_option( 'tapp_cookie_settings' );
	// stav plugin-update-checker (posledni kontrola, nabizena verze; ADR-0037)
	delete_option( 'external_updates-tapp-cookie' );
	foreach ( array( 'tapp_cookie_site_id', 'tapp_cookie_tenant_id', 'tapp_cookie_cmp_url', 'tapp_cookie_blocking' ) as $key ) {
		delete_option( $key );
	}
}

if ( is_multisite() ) {
	$sites = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $sites as $site_id ) {
		switch_to_blog( (int) $site_id );
		tapp_cookie_delete_options();
		restore_current_blog();
	}
	delete_site_option( 'tapp_cookie_settings' );
	delete_site_option( 'external_updates-tapp-cookie' );
} else {
	tapp_cookie_delete_options();
}
