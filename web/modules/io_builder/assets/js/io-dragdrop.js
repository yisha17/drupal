(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.ioBuilderDragDrop = {
    attach: function (context, settings) {
      var elements = document.querySelectorAll('.io-builder--sort-wrapper');

      if (elements) {
        elements.forEach(function (el) {
          var sortable = Sortable.create(el, {
            animation: 150,
            ghostClass: 'drag-highlighted-ghost',
            swapThreshold: 0.72,
            handle: '.io-builder-action__move',
            onEnd: function(evt) {
              var $parent = $(evt.from);

              if ($parent.length < 0) {
                return;
              }

              var parentData = Drupal.behaviors.ioBuilder.getParentData($parent);

              if (!parentData) {
                return;
              }

              var field = $parent.data('io-builder-field');

              var indexSwitch = {
                from: evt.oldIndex,
                to: evt.newIndex,
              };

              var loadAjax = Drupal.ajax({
                url: Drupal.url('io-builder/drag-drop'),
                type: "POST",
                submit: {
                  'io_builder_context_tree': {
                    'parent': parentData
                  },
                  'field': field,
                  'index_switch': indexSwitch,
                },
              });

              loadAjax.execute().done(function() {
                Drupal.attachBehaviors();
              });
            }
          });
        })
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
