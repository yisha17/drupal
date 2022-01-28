(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.ioBuilderTooltip = {
    attach: function (context, settings) {
      tippy(".has-tooltip", {
        theme: "io-builder",
        animation: "shift-away-subtle",
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
