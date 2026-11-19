<?php
/**
 * Plugin Name: tapp cookie
 * Plugin URI: https://tappcookie.cz
 * Description: Cookie lišta tapp cookie (consent management TAPP Studio s.r.o.): vloží skript do hlavičky jako první, blokuje měřicí kódy před souhlasem, generuje tabulku cookies a propojuje WP Consent API.
 * Version: 0.2.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: TAPP Studio s.r.o.
 * Author URI: https://tapp-studio.cz
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tapp-cookie
 * Domain Path: /languages
 * Update URI: https://cmp.tappcookie.cz/wordpress/tapp-cookie.json
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TAPP_COOKIE_VERSION', '0.2.0' );
define( 'TAPP_COOKIE_DEFAULT_CMP', 'https://cmp.tappcookie.cz' );
if ( ! defined( 'TAPP_COOKIE_UPDATE_URL' ) ) {
	define( 'TAPP_COOKIE_UPDATE_URL', 'https://cmp.tappcookie.cz/wordpress/tapp-cookie.json' );
}

/*
 * Aktualizace mimo WordPress.org (ADR-0037): knihovna plugin-update-checker 5.7 (MIT, vendorovana
 * v vendor/plugin-update-checker) cte JSON z TAPP_COOKIE_UPDATE_URL zhruba dvakrat denne.
 * Bez knihovny (soubor chybi) plugin dal funguje, jen se sam neaktualizuje.
 */
if ( file_exists( __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php';
	\YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker( TAPP_COOKIE_UPDATE_URL, __FILE__, 'tapp-cookie' );
}

/**
 * Inline Consent Mode default: presne tentyz text jako `renderStandaloneDefaultSnippet()`
 * v @tapp/cookie-core (vcetne developer ID a casti pro Meta `fbq("consent","revoke")`).
 * Shodu hlida infra/test/consistency.test.ts; po zmene v core nahradit obsah nowdocu
 * vystupem teto funkce (jedina radka mezi znackami, bez uvozovek navic).
 */
function tapp_cookie_consent_default_snippet(): string {
	return <<<'TAPPSNIPPET'
window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("set","developer_id.dMjg5OD",true);gtag("consent","default",{"ad_storage":"denied","ad_user_data":"denied","ad_personalization":"denied","analytics_storage":"denied","functionality_storage":"denied","personalization_storage":"denied","security_storage":"granted","wait_for_update":500});gtag("set","ads_data_redaction",true);(function(w){try{var f,d=0;Object.defineProperty(w,"fbq",{configurable:true,get:function(){if(f&&!d){d=1;try{f("consent","revoke")}catch(e){}}return f},set:function(v){f=v}})}catch(e){}})(window);
TAPPSNIPPET;
}

/**
 * Nastaveni pluginu s vychozimi hodnotami.
 *
 * @return array{site_id:string,tenant_id:string,cmp_url:string,locale:string,blocking:string,admins:bool}
 */
function tapp_cookie_settings(): array {
	$defaults = array(
		'site_id'   => '',
		'tenant_id' => '',
		'cmp_url'   => TAPP_COOKIE_DEFAULT_CMP,
		'locale'    => '',
		'blocking'  => 'auto',
		'admins'    => true,
	);
	$stored   = get_option( 'tapp_cookie_settings', array() );
	return array_merge( $defaults, is_array( $stored ) ? $stored : array() );
}

/**
 * Sanitizace nastaveni (register_setting callback).
 *
 * @param mixed $input Surova data z formulare.
 * @return array
 */
function tapp_cookie_sanitize( $input ): array {
	$input = is_array( $input ) ? $input : array();
	$id    = static function ( $v ): string {
		$v = strtolower( trim( (string) $v ) );
		return preg_match( '/^[a-z0-9][a-z0-9-]{1,48}$/', $v ) ? $v : '';
	};
	$cmp   = esc_url_raw( trim( (string) ( $input['cmp_url'] ?? '' ) ) );
	if ( '' === $cmp || 0 !== strpos( $cmp, 'https://' ) ) {
		$cmp = TAPP_COOKIE_DEFAULT_CMP;
	}
	$locale = strtolower( trim( (string) ( $input['locale'] ?? '' ) ) );
	if ( '' !== $locale && ! preg_match( '/^[a-z]{2}(-[a-z]{2})?$/', $locale ) ) {
		$locale = '';
	}
	return array(
		'site_id'   => $id( $input['site_id'] ?? '' ),
		'tenant_id' => $id( $input['tenant_id'] ?? '' ),
		'cmp_url'   => rtrim( $cmp, '/' ),
		'locale'    => $locale,
		'blocking'  => ( isset( $input['blocking'] ) && 'manual' === $input['blocking'] ) ? 'manual' : 'auto',
		'admins'    => ! empty( $input['admins'] ),
	);
}

add_action(
	'init',
	static function (): void {
		load_plugin_textdomain( 'tapp-cookie', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		register_setting(
			'tapp_cookie',
			'tapp_cookie_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'tapp_cookie_sanitize',
				'default'           => array(),
			)
		);
		if ( function_exists( 'register_block_type' ) ) {
			register_block_type( __DIR__ . '/blocks/declaration' );
		}
	}
);

/**
 * Skript do <head> jako prvni (priorita 0). Synchronne: auto-blocking musi bezet pred ostatnimi skripty.
 */
add_action(
	'wp_head',
	static function (): void {
		$s = tapp_cookie_settings();
		if ( '' === $s['site_id'] || '' === $s['tenant_id'] ) {
			return;
		}
		if ( ! $s['admins'] && current_user_can( 'manage_options' ) ) {
			return;
		}
		$attrs = array(
			'src'         => $s['cmp_url'] . '/tappcookie.js',
			'data-site'   => $s['site_id'],
			'data-tenant' => $s['tenant_id'],
			'data-cmp'    => $s['cmp_url'],
		);
		if ( '' !== $s['locale'] ) {
			$attrs['data-locale'] = $s['locale'];
		}
		if ( 'manual' === $s['blocking'] ) {
			$attrs['data-blocking'] = 'manual';
		}
		/*
		 * Consent Mode default MUSI byt inline, jeste pred externim skriptem: kdyz blokator
		 * reklam nebo vypadek CDN zabrani nacteni tappcookie.js, jinak by se nenastavil zadny
		 * default a merici kody by odesilaly data bez souhlasu (audit kolo 4, nalez K-1).
		 * Text je v tapp_cookie_consent_default_snippet() (zdroj pravdy: core, shodu hlida test).
		 */
		$html  = '<script>' . tapp_cookie_consent_default_snippet() . '</script>' . "\n";
		$html .= '<script';
		foreach ( $attrs as $k => $v ) {
			$html .= ' ' . $k . '="' . ( 'src' === $k || 'data-cmp' === $k ? esc_url( $v ) : esc_attr( $v ) ) . '"';
		}
		$html .= '></script>' . "\n";
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- staticky text + atributy escapovane vyse
	},
	0
);

/**
 * Skript pluginu (bez inline kodu, aby fungoval i pod prisnou CSP): odkaz na nastaveni,
 * vykresleni deklarace a most na WP Consent API. Registruje se vzdy, zarazuje jen kdyz je treba.
 */
function tapp_cookie_enqueue_front_script(): void {
	if ( ! wp_script_is( 'tapp-cookie', 'registered' ) ) {
		return;
	}
	wp_enqueue_script( 'tapp-cookie' );
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_register_script(
			'tapp-cookie',
			plugins_url( 'assets/tapp-cookie.js', __FILE__ ),
			array(),
			TAPP_COOKIE_VERSION,
			true
		);
	},
	1
);

/**
 * [tapp_cookie_declaration locale="cs" styles="false"] - tabulka nastroju a cookies z deklarace
 * (TappCookie.renderDeclaration). `styles="false"` vypne vychozi vzhled; tabulku pak stylujete
 * sami pres tridy tc-decl*.
 */
add_shortcode(
	'tapp_cookie_declaration',
	static function ( $atts ): string {
		$atts = shortcode_atts(
			array(
				'locale' => '',
				'styles' => 'true',
			),
			$atts,
			'tapp_cookie_declaration'
		);
		$id   = 'tapp-cookie-declaration-' . wp_unique_id();
		$opts = array();
		if ( '' !== $atts['locale'] ) {
			$opts['locale'] = sanitize_text_field( $atts['locale'] );
		}
		if ( ! wp_validate_boolean( $atts['styles'] ) ) {
			$opts['styles'] = false;
		}
		$json = array() === $opts ? '{}' : wp_json_encode( $opts );
		tapp_cookie_enqueue_front_script();
		return '<div id="' . esc_attr( $id ) . '" class="tapp-cookie-declaration" aria-busy="true"'
			. ' data-tapp-cookie-declaration="1" data-tapp-options="' . esc_attr( $json ) . '"></div>';
	}
);

/** [tapp_cookie_settings_link text="Nastavení soukromí"] - odkaz do paticky, otevre druhou vrstvu listy. */
add_shortcode(
	'tapp_cookie_settings_link',
	static function ( $atts ): string {
		$atts = shortcode_atts( array( 'text' => __( 'Nastavení soukromí', 'tapp-cookie' ), 'class' => '' ), $atts, 'tapp_cookie_settings_link' );
		tapp_cookie_enqueue_front_script();
		return '<a href="#tapp-cookie-settings" class="' . esc_attr( $atts['class'] ) . '" data-tapp-cookie-settings="1">' . esc_html( $atts['text'] ) . '</a>';
	}
);

/**
 * WP Consent API (plugin wp-consent-api): prohlasime se za CMP a prepisujeme kategorie
 * pri kazde udalosti `tappcookie:consent`. Mapovani: statistics <- analytics, marketing <- marketing,
 * preferences + functional <- preferences.
 */
add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );
add_filter(
	'wp_get_consent_type',
	static function () {
		return 'optin';
	}
);
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( function_exists( 'wp_has_consent' ) ) {
			// most na WP Consent API je soucasti stejneho skriptu (bez inline kodu kvuli CSP)
			tapp_cookie_enqueue_front_script();
		}
	},
	99
);

// ---------- Administrace ----------

add_action(
	'admin_menu',
	static function (): void {
		add_options_page(
			__( 'tapp cookie', 'tapp-cookie' ),
			__( 'tapp cookie', 'tapp-cookie' ),
			'manage_options',
			'tapp-cookie',
			'tapp_cookie_settings_page'
		);
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=tapp-cookie' ) ) . '">' . esc_html__( 'Nastavení', 'tapp-cookie' ) . '</a>' );
		return $links;
	}
);

function tapp_cookie_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = tapp_cookie_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'tapp cookie', 'tapp-cookie' ); ?></h1>
		<p><?php esc_html_e( 'Údaje najdete v administraci tapp cookie v záložce Instalace u vašeho webu. Lišta se vloží do hlavičky jako první skript.', 'tapp-cookie' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'tapp_cookie' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="tc-site"><?php esc_html_e( 'Site ID', 'tapp-cookie' ); ?></label></th>
					<td><input id="tc-site" name="tapp_cookie_settings[site_id]" type="text" class="regular-text" value="<?php echo esc_attr( $s['site_id'] ); ?>" pattern="[a-z0-9][a-z0-9-]{1,48}" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="tc-tenant"><?php esc_html_e( 'Tenant ID (klient)', 'tapp-cookie' ); ?></label></th>
					<td><input id="tc-tenant" name="tapp_cookie_settings[tenant_id]" type="text" class="regular-text" value="<?php echo esc_attr( $s['tenant_id'] ); ?>" pattern="[a-z0-9][a-z0-9-]{1,48}" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="tc-cmp"><?php esc_html_e( 'CMP URL', 'tapp-cookie' ); ?></label></th>
					<td><input id="tc-cmp" name="tapp_cookie_settings[cmp_url]" type="url" class="regular-text" value="<?php echo esc_attr( $s['cmp_url'] ); ?>"><p class="description"><?php esc_html_e( 'Nechte výchozí, pokud vám TAPP neřekl jinak.', 'tapp-cookie' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="tc-locale"><?php esc_html_e( 'Jazyk lišty', 'tapp-cookie' ); ?></label></th>
					<td><input id="tc-locale" name="tapp_cookie_settings[locale]" type="text" class="small-text" value="<?php echo esc_attr( $s['locale'] ); ?>" placeholder="auto"><p class="description"><?php esc_html_e( 'Prázdné = podle jazyka webu. Jinak cs, en, sk, de.', 'tapp-cookie' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Blokování měřicích kódů', 'tapp-cookie' ); ?></th>
					<td>
						<label><input type="radio" name="tapp_cookie_settings[blocking]" value="auto" <?php checked( 'auto', $s['blocking'] ); ?>> <?php esc_html_e( 'Automaticky (doporučeno) - známé nástroje se spustí až po souhlasu', 'tapp-cookie' ); ?></label><br>
						<label><input type="radio" name="tapp_cookie_settings[blocking]" value="manual" <?php checked( 'manual', $s['blocking'] ); ?>> <?php esc_html_e( 'Ručně - skripty si označíte sami (type="text/plain" data-tapp-consent="...")', 'tapp-cookie' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Přihlášení správci', 'tapp-cookie' ); ?></th>
					<td><label><input type="checkbox" name="tapp_cookie_settings[admins]" value="1" <?php checked( $s['admins'] ); ?>> <?php esc_html_e( 'Zobrazovat lištu i přihlášeným správcům webu', 'tapp-cookie' ); ?></label></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2><?php esc_html_e( 'Použití', 'tapp-cookie' ); ?></h2>
		<ul>
			<li><code>[tapp_cookie_declaration]</code> - <?php esc_html_e( 'tabulka nástrojů a cookies na stránku Zásady cookies (nebo blok „Tabulka cookies").', 'tapp-cookie' ); ?>
				<?php esc_html_e( 'Volitelné atributy: locale="cs" (jazyk tabulky) a styles="false" (vypne výchozí vzhled, tabulku pak stylujete sami přes třídy tc-decl*).', 'tapp-cookie' ); ?></li>
			<li><code>[tapp_cookie_settings_link text="Nastavení soukromí"]</code> - <?php esc_html_e( 'odkaz do patičky pro změnu nebo odvolání souhlasu.', 'tapp-cookie' ); ?></li>
		</ul>
	</div>
	<?php
}
