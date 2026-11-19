/* Minimalni editor blok bez build kroku (wp.blocks + wp.element). */
(function (blocks, element, blockEditor, components, i18n) {
  var el = element.createElement;
  var __ = i18n.__;
  blocks.registerBlockType("tapp-cookie/declaration", {
    edit: function (props) {
      var blockProps = blockEditor.useBlockProps({
        style: { padding: "16px", border: "1px dashed #8c8f94", borderRadius: "6px" },
      });
      return el(
        "div",
        blockProps,
        el("strong", null, __("Tabulka cookies (tapp cookie)", "tapp-cookie")),
        el(
          "p",
          { style: { margin: "6px 0 0", color: "#646970" } },
          __(
            "Na webu se zde vykreslí nástroje a cookies z vaší deklarace. Náhled je v administraci tapp cookie.",
            "tapp-cookie",
          ),
        ),
        el(components.TextControl, {
          label: __("Jazyk (prázdné = podle webu)", "tapp-cookie"),
          value: props.attributes.locale,
          onChange: function (v) {
            props.setAttributes({ locale: v });
          },
        }),
        el(components.ToggleControl, {
          label: __("Výchozí vzhled tapp cookie", "tapp-cookie"),
          help: __(
            "Vypněte, pokud tabulku stylujete sami (třídy tc-decl*).",
            "tapp-cookie",
          ),
          checked: props.attributes.styles !== false,
          onChange: function (v) {
            props.setAttributes({ styles: !!v });
          },
        }),
      );
    },
    save: function () {
      return null;
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.i18n,
);
