# tapp cookie for WordPress

WordPress plugin for [tapp cookie](https://tappcookie.cz), the consent management platform by TAPP Studio s.r.o.
Current version: 0.2.1.

## What it does

- Prints the Google Consent Mode default (all denied, `wait_for_update` 500 ms, `ads_data_redaction`) and the
  Google developer ID `dMjg5OD` as the very first output of `wp_head` (priority 0), followed by the tapp cookie
  banner script.
- Blocks known tracking scripts until the visitor consents (`blocking: auto`), or lets you mark scripts manually.
- Integrates with the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/): the plugin registers itself
  (`wp_consent_api_registered_tapp-cookie/tapp-cookie.php`), uses the `optin` consent type and passes every choice
  to `wp_set_consent()` for `statistics`, `marketing`, `preferences` and `functional`. Plugins that read the WP
  Consent API, for example Site Kit by Google with consent mode, follow the visitor's choice.
- Cookie declaration block, `[tapp_cookie_declaration]` shortcode and `[tapp_cookie_settings_link]` shortcode for a
  "Privacy settings" link.
- Updates from `https://cmp.tappcookie.cz/wordpress/tapp-cookie.json` (Plugin Update Checker).

## Install

1. Download [tapp-cookie-0.2.1.zip](https://cmp.tappcookie.cz/wordpress/tapp-cookie-0.2.1.zip) and upload it in
   Plugins → Add New → Upload Plugin, then activate it.
2. Install and activate the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) plugin.
3. Settings → tapp cookie: enter the Site ID and Tenant ID from the tapp cookie admin (https://tappcookie.cz/app).

## Test site

https://wp.google-audit.tappcookie.cz is WordPress with this plugin, the WP Consent API and Site Kit by Google
(Google Analytics 4, consent mode enabled). The banner shows in every region.

- Before the choice the Google tag sends nothing.
- Accept all: `wp_has_consent("statistics")` and `wp_has_consent("marketing")` return `true`, the four consent
  types are updated to granted and Google Analytics 4 requests carry `gcs=G111`.
- Reject all: the WP Consent API categories stay denied and nothing is sent to Google.

Demo video: https://d2mjjteeye1yva.cloudfront.net/demo/2026-11-19/04-wordpress-wp-consent-api.mp4

## Documentation and support

- Google Consent Mode with tapp cookie: https://tappcookie.cz/en/docs/consent-mode
- Support: info@tapp-studio.cz, business days 9–17 CET, first response within 1 business day.

## License

GPL-2.0-or-later, see [LICENSE](LICENSE). The bundled Plugin Update Checker (`vendor/plugin-update-checker`) by
Jānis Elsts is MIT licensed, see its `license.txt`.

This repository mirrors the plugin as distributed. Development happens in the tapp cookie monorepo.
