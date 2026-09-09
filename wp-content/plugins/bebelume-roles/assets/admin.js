/**
 * Bebelume Profiles - Admin JS
 */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // Toggle submenu visibility
        $('.bebelume-toggle-children').on('click', function () {
            var $btn = $(this);
            var $card = $btn.closest('.bebelume-menu-card');
            var $submenuList = $card.find('.bebelume-submenu-list');
            var isExpanded = $btn.attr('aria-expanded') === 'true';

            $btn.attr('aria-expanded', !isExpanded);
            $submenuList.slideToggle(200);
        });

        // When a parent menu is checked/unchecked, toggle its card state and children
        $('.bebelume-menu-checkbox').on('change', function () {
            var $checkbox = $(this);
            var $card = $checkbox.closest('.bebelume-menu-card');
            var isChecked = $checkbox.is(':checked');

            $card.toggleClass('is-active', isChecked);

            // Auto-check/uncheck all submenus
            $card.find('.bebelume-submenu-checkbox').prop('checked', isChecked);

            // Auto-expand submenu list if checking
            if (isChecked) {
                var $submenuList = $card.find('.bebelume-submenu-list');
                var $toggle = $card.find('.bebelume-toggle-children');
                if ($submenuList.length && !$submenuList.is(':visible')) {
                    $submenuList.slideDown(200);
                    $toggle.attr('aria-expanded', 'true');
                }
            }
        });

        // When a submenu checkbox changes, check if parent should be auto-checked
        $('.bebelume-submenu-checkbox').on('change', function () {
            var $card = $(this).closest('.bebelume-menu-card');
            var $parentCheckbox = $card.find('.bebelume-menu-checkbox');
            var $subCheckboxes = $card.find('.bebelume-submenu-checkbox');
            var anyChecked = $subCheckboxes.filter(':checked').length > 0;

            if (anyChecked && !$parentCheckbox.is(':checked')) {
                $parentCheckbox.prop('checked', true);
                $card.addClass('is-active');
            }

            // If all unchecked, uncheck parent too
            if (!anyChecked) {
                $parentCheckbox.prop('checked', false);
                $card.removeClass('is-active');
            }
        });

        // Select All
        $('.bebelume-select-all').on('click', function () {
            $('.bebelume-menu-checkbox').prop('checked', true).trigger('change');
        });

        // Deselect All
        $('.bebelume-deselect-all').on('click', function () {
            $('.bebelume-menu-checkbox').prop('checked', false);
            $('.bebelume-submenu-checkbox').prop('checked', false);
            $('.bebelume-menu-card').removeClass('is-active');
        });

    });

})(jQuery);
