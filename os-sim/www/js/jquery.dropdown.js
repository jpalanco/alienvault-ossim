/*
 * jQuery dropdown: A simple dropdown plugin
 *
 * Copyright 2013 Cory LaViska for A Beautiful Site, LLC. (http://abeautifulsite.net/)
 *
 * Licensed under the MIT license: http://opensource.org/licenses/MIT
 *
*/
if (jQuery) (function ($) {

    $.extend($.fn, {
        dropdown: function (method, data) {

            switch (method) {
                case 'show':
                    show(null, $(this));
                    return $(this);
                case 'hide':
                    hide();
                    return $(this);
                case 'attach':
                    return $(this).attr('data-dropdown', data);
                case 'detach':
                    hide();
                    return $(this).removeAttr('data-dropdown');
                case 'disable':
                    return $(this).addClass('dropdown-disabled');
                case 'enable':
                    hide();
                    return $(this).removeClass('dropdown-disabled');
            }

        }
    });

    function show(event, object) {

        var trigger = event ? $(this) : object,
            dropdown = $(trigger.attr('data-dropdown')),
            isOpen = trigger.hasClass('dropdown-open');

        // In some cases we don't want to show it
        if (event) {
            if ($(event.target).hasClass('dropdown-ignore')) return;

            event.preventDefault();
            event.stopPropagation();
        } else {
            if (trigger !== object.target && $(object.target).hasClass('dropdown-ignore')) return;
        }
        hide();

        if (isOpen || trigger.hasClass('dropdown-disabled')) return;

        // Show it
        trigger.addClass('dropdown-open');
        dropdown
            .data('dropdown-trigger', trigger)
            .show();

        // Position it
        position(this);

        // Trigger the show callback
        dropdown
            .trigger('show', {
                dropdown: dropdown,
                trigger: trigger
            });
    }

    function hide(event) {

        // In some cases we don't hide them
        var targetGroup = event ? $(event.target).parents('.dropdown') : null;
        
        // Are we clicking anywhere in a dropdown?
        if (targetGroup && targetGroup.is('.dropdown') && !targetGroup.is('.dropdown-close')) 
        {
            // Is it a dropdown menu?
            if (targetGroup.is('.dropdown-menu')) 
            {
                // Did we click on an option? If so close it.
                if (!targetGroup.is('A')) return;
            } 
            else 
            {
                // Nope, it's a panel. Leave it open.
                return;
            }
        }

        // Hide any dropdown that may be showing
        $(document).find('.dropdown:visible').each(function () {
            var dropdown = $(this);

            dropdown
                .hide()
                .removeData('dropdown-trigger')
                .trigger('hide', { dropdown: dropdown });
        });

        // Remove all dropdown-open classes
        $(document).find('.dropdown-open').removeClass('dropdown-open');
    }

    function position(object) {

        var dropdown = $('.dropdown:visible').eq(0),
            trigger  = dropdown.data('dropdown-trigger'),
            hOffset  = trigger ? parseInt(trigger.attr('data-horizontal-offset') || 0, 10) : null,
            vOffset  = trigger ? parseInt(trigger.attr('data-vertical-offset') || 0, 10) : null,
            d_height = dropdown.height();

        if (dropdown.length === 0 || !trigger) return;
        
        if (typeof object != 'undefined')
        {
            var min_w = $(object).outerWidth(true);

            dropdown.css('min-width', min_w + 'px');
        }

        // Position the dropdown relative-to-parent...
        if (dropdown.hasClass('dropdown-relative'))
        {
            var left = dropdown.hasClass('dropdown-anchor-right') ?
                    trigger.position().left - (dropdown.outerWidth(true) - trigger.outerWidth(true)) - parseInt(trigger.css('margin-right'), 10) + hOffset :
                    trigger.position().left + parseInt(trigger.css('margin-left'), 10) + hOffset;

            var top = trigger.position().top + trigger.outerHeight(true) - parseInt(trigger.css('margin-top'), 10) + vOffset


            //Check the menu is not higher than the window size
            var top_offset = trigger.offset().top + trigger.outerHeight() + vOffset;
            if ((top_offset + d_height) > $(window).height())
            {
                top = trigger.position().top - vOffset - d_height + 1;
            }
        }
        else // ...or relative to document
        {
            var left = 0;
            var top  = trigger.offset().top + trigger.outerHeight() + vOffset; //Getting vertical position

            //Getting horizontal position
            if (dropdown.hasClass('dropdown-anchor-right'))
            {
                left = trigger.offset().left - (dropdown.outerWidth() - trigger.outerWidth()) + hOffset
            }
            else
            {
                left = trigger.offset().left + hOffset
            }

            //Check the menu is not higher than the window size
            if ((top + d_height) > $(window).height())
            {
                top = trigger.offset().top - vOffset - d_height
            }
        }

        dropdown.css(
        {
            left: Math.floor(left),
            top: Math.floor(top)
        });
    }

    $(document).on('click.dropdown', '[data-dropdown]', show);
    $(document).on('click.dropdown', hide);
    $(window).on('resize', position);

})(jQuery);