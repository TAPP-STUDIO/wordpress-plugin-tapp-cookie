/**
 * Chovani na strane webu bez inline skriptu (funguje i pod prisnou CSP, docs/13 S-27):
 * - odkaz [tapp_cookie_settings_link] otevre nastaveni listy,
 * - [tapp_cookie_declaration] a Gutenberg blok vykresli tabulku nastroju,
 * - most na WP Consent API (wp_set_consent), pokud je plugin wp-consent-api aktivni.
 */
(function () {
  "use strict";

  function onReady(fn) {
    if (window.TappCookie) {
      fn();
      return;
    }
    window.addEventListener("tappcookie:ready", fn, { once: true });
  }

  document.addEventListener("click", function (e) {
    var el =
      e.target && e.target.closest
        ? e.target.closest("[data-tapp-cookie-settings]")
        : null;
    if (!el) return;
    e.preventDefault();
    if (window.TappCookie) window.TappCookie.show();
  });

  function renderDeclarations() {
    var nodes = document.querySelectorAll("[data-tapp-cookie-declaration]");
    Array.prototype.forEach.call(nodes, function (node) {
      if (node.getAttribute("data-tapp-rendered") === "1") return;
      node.setAttribute("data-tapp-rendered", "1");
      var opts = {};
      var raw = node.getAttribute("data-tapp-options");
      if (raw) {
        try {
          opts = JSON.parse(raw);
        } catch (err) {
          opts = {};
        }
      }
      var done = function () {
        node.removeAttribute("aria-busy");
      };
      var res = window.TappCookie.renderDeclaration(node, opts);
      if (res && typeof res.then === "function") res.then(done, done);
      else done();
    });
  }

  function pushConsent(choices) {
    if (typeof window.wp_set_consent !== "function" || !choices) return;
    window.wp_set_consent("statistics", choices.analytics ? "allow" : "deny");
    window.wp_set_consent("marketing", choices.marketing ? "allow" : "deny");
    window.wp_set_consent("preferences", choices.preferences ? "allow" : "deny");
    window.wp_set_consent("functional", choices.preferences ? "allow" : "deny");
  }

  window.addEventListener("tappcookie:consent", function (e) {
    if (e.detail && e.detail.choices) pushConsent(e.detail.choices);
  });

  onReady(function () {
    renderDeclarations();
    var state = window.TappCookie.consent();
    if (state) pushConsent(state.choices);
  });
})();
