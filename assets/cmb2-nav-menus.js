(function(window, document, $) {
    $(document).ready(function() {
        // Toggle the fields when we first load the menu
        toggleFields();

        // Make sure to update visible fields on menu changes
        MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

        var observer = new MutationObserver(function(mutations, observer) {
            for (var mutation of mutations) {
                if (mutation.type == 'childList' && mutation.addedNodes) {
                    for (var node of mutation.addedNodes) {
                        if ($(node).hasClass('menu-item')) {
                            toggleFields(node);
                        }
                    }
                }
            }
        });

        observer.observe($('#menu-to-edit').get('0'), {
            childList: true,
            subtree: true,
            attributes: false,
            characterData: false,
        });
    });

    // Toggle visible fields when we update the menu
    function toggleFields(element) {
        if (! element) {
            element = $('#update-nav-menu');
        }

        // Toggle depth dependent fields
        $(element).find('.cmb2-wrap *[data-depth]').each(function() {
            let element = $(this);
            let depthDef = JSON.parse(element.attr('data-depth'));
            let item = element.closest('.menu-item');
            let classList = item.attr('class').split(/\s+/);
            let depth = -1;

            for (i = 0; i < classList.length; i++) {
                if (classList[i].length > 0) {
                    if (classList[i].indexOf('menu-item-depth') == 0) {
                        depth = parseInt(classList[i].replace('menu-item-depth-', ''));
                    }
                }
            }

            if (depth >= 0 && depthDef) {
                let row = element.closest('.cmb-row');

                if (depthDef.includes(depth)) {
                    row.show();
                }
                else {
                    row.hide();
                }
            }
        });
    }
})(window, document, jQuery);
