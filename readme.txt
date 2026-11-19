=== tapp cookie ===
Contributors: tappstudio
Tags: cookies, gdpr, consent, cookie banner, consent mode
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cookie lišta tapp cookie: správné signály pro Google, Microsoft, Meta a Seznam, důkaz souhlasu, blokování měřicích kódů před souhlasem.

== Description ==

Plugin vloží skript tapp cookie (consent management platform společnosti TAPP Studio s.r.o.) do hlavičky webu jako první skript, takže měřicí kódy (GA4, Google Ads, Microsoft UET, Meta Pixel, Sklik) se spustí až po souhlasu návštěvníka. Lišta a texty se spravují v administraci tapp cookie, plugin jen potřebuje Site ID a Tenant ID.

* Google Consent Mode v2, Microsoft UET Consent Mode, Clarity, Meta, Seznam SEM
* automatické blokování známých nástrojů před souhlasem (lze vypnout a označit skripty ručně)
* shortcode a blok „Tabulka cookies" pro stránku Zásady cookies (výchozí vzhled lze vypnout a tabulku nastylovat po svém)
* automatické aktualizace pluginu z cmp.tappcookie.cz (mimo WordPress.org)
* odkaz „Nastavení soukromí" do patičky
* WP Consent API (pokud je nainstalováno)

Služba tapp cookie je dostupná klientům TAPP studia; plugin bez účtu nefunguje.

== Installation ==

1. Nahrajte složku `tapp-cookie` do `/wp-content/plugins/` nebo nainstalujte ZIP.
2. Aktivujte plugin.
3. Nastavení -> tapp cookie: vyplňte Site ID a Tenant ID z administrace tapp cookie.
4. Na stránku Zásady cookies vložte blok „Tabulka cookies" nebo `[tapp_cookie_declaration]`.
5. Do patičky vložte `[tapp_cookie_settings_link]`.

Tabulku cookies lze upravit atributy shortcodu `[tapp_cookie_declaration]`: `locale="cs"` (jazyk tabulky) a `styles="false"` (vypne výchozí vzhled tapp cookie, tabulku pak nastylujete sami přes třídy `tc-decl*`). Blok „Tabulka cookies" má stejné volby v postranním panelu.

== External service ==

Tento plugin propojuje váš web se službou tapp cookie (consent management platform), kterou provozuje TAPP Studio s.r.o., IČO 23839074, Národní třída 3687/42, 695 01 Hodonín, Česká republika. Bez této služby plugin nefunguje.

Co plugin načítá z domény vaší instance tapp cookie (výchozí `https://cmp.tappcookie.cz`, lze změnit v nastavení):

* `/tappcookie.js` - skript lišty (JavaScript),
* `/config\/<site-id>\/current.json` - nastavení lišty, seznam nástrojů a texty.

Aktualizace pluginu (mimo WordPress.org):

* `GET https://cmp.tappcookie.cz/wordpress/tapp-cookie.json` (vždy z této adresy, nezávisle na adrese CMP v nastavení): WordPress (kontrolu provádí vestavěná knihovna plugin-update-checker, MIT) ji spouští plánovačem WP-Cron zhruba dvakrát denně a při otevření stránky Aktualizace v administraci. Požadavek nese verzi pluginu, verzi PHP a jazyk WordPressu (parametry `installed_version`, `php`, `locale`); WordPress k němu přidá hlavičku User-Agent se svou verzí a adresou webu. Žádné údaje o návštěvnících webu. Odpověď obsahuje číslo nejnovější verze a odkaz na ZIP.
* `GET https://cmp.tappcookie.cz/wordpress/tapp-cookie-<verze>.zip` se stáhne jen tehdy, když správce webu aktualizaci pluginu spustí.

Co se ze stránky odesílá:

* `POST \/v1\/consent` po rozhodnutí návštěvníka: identifikátor webu a klienta, náhodné ID souhlasu (UUID), čas, druh volby (přijmout vše, odmítnout, vlastní, změna, odvolání, obnovení, odmítnutí signálem GPC, implicitní stav) s důvodem (první, obnova, změna) a zdrojem (lišta, nastavení, patička, API), zvolené kategorie, verze a otisk deklarace, verze textů, jazyk, cesta stránky bez parametrů, právní režim a země či stát, podle nichž se lišta zobrazila (např. CZ, US-CA, bez přesné polohy), pořadí použitého pravidla, zda prohlížeč poslal signál Global Privacy Control, verze skriptu. Server k záznamu doplní zemi z hlavičky sítě CDN, zkrácený identifikátor prohlížeče a (jen pokud to správce webu zapne) nevratný otisk IP adresy. IP adresa se v čitelné podobě neukládá.
* `POST \/v1\/ping` při prvním načtení v relaci: identifikátor webu, klienta a verze skriptu (žádné údaje o návštěvníkovi).
* `GET \/v1\/geo` u nového návštěvníka: bez těla, server vrátí jen zemi a případně stát či oblast (např. US-CA) pro určení právního režimu.

Data jsou uložena v Evropské unii (AWS, region eu-central-1, Frankfurt nad Mohanem).

* Podmínky služby a zpracovatelská smlouva: https://tappcookie.cz/pro-klienty
* Zásady ochrany osobních údajů: https://tappcookie.cz/ochrana-osobnich-udaju
* Zásady cookies: https://tappcookie.cz/zasady-cookies

== Frequently Asked Questions ==

= Proč se lišta nezobrazuje? =
Zkontrolujte Site ID a Tenant ID a že je web v administraci tapp cookie zveřejněný. Přihlášeným správcům se lišta zobrazuje jen se zapnutou volbou v nastavení.

= Kam se odkaz „Nastavení soukromí" napojuje? =
Odkaz nese atribut `data-tapp-cookie-settings` a obsluhuje ho skript pluginu; nepoužíváme inline JavaScript, takže funguje i na webech s přísnou Content Security Policy.

= Blokuje plugin i skripty vložené jinými pluginy? =
Automatické blokování zachytí známé nástroje (Google, Microsoft, Meta, Seznam, LinkedIn, TikTok, Hotjar, YouTube...). Skripty vložené serverem před tapp cookie nebo first-party proxy zachytit nelze; označte je ručně nebo je podmiňte v Google Tag Manageru.

== Changelog ==

= 0.2.0 =
* Plugin se aktualizuje sám z cmp.tappcookie.cz (mimo WordPress.org).
* Záložní Consent Mode výchozí nastavení v hlavičce je nově stejné jako v jádru tapp cookie a doplňuje odvolání souhlasu pro Meta Pixel.
* Nový atribut `styles` (shortcode `[tapp_cookie_declaration]`) a přepínač „Výchozí vzhled tapp cookie" v bloku: vypne výchozí vzhled tabulky cookies.

= 0.1.1 =
* Developer ID tapp cookie jako partnera Google Consent Mode.

= 0.1.0 =
* První verze.
