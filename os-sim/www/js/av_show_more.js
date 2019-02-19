/**
 * av.show_more plugin
 */
$.widget('av.show_more', 
{
    /**
     * Options
     */
    options: 
    {
        items_to_show: 10, 
        display_button: 'outside',
        open_class: 'sm_full_list'
    },


    /**
     * Create plugin instance
     *
     * @private
     */
    _create: function ()
    {
        this.items = [];
        this.hidden_items = [];

        this.button = $('<a>', 
        {
            class: 'av_show_more_button'
        });

        this.wrapper = $('<div>', 
        {
            class: 'av_show_more_wrapper'
        });

        this.hidden_list = $('<div>', 
        {
            class: 'av_show_more_hidden_list'
        });
    },


    /**
     * Initialize plugin instance
     *
     * @private
     */
    _init: function ()
    {
        this._get_items();

        if (this.items.length > this.options.items_to_show)
        {
            this._add_wrapper();
            this._add_button();
        }
        else
        {
            this._remove_button();
        }
    },


    /**
     * Set option
     *
     * @param option
     * @param value
     * @private
     */
    _setOption: function (option, value)
    {
        if (option == 'items_to_show')
        {
            if (value >= 0)
            {
                this.options.items_to_show = value;
                this.reload();
            }
        }
    },
    

    /**
     * Get items from element
     *
     * @private
     */
    _get_items: function ()
    {
        this.items = this.element.children();
        this.hidden_items = this.items.slice(this.options.items_to_show);
    },


    /**
     * Add button to list
     *
     * @private
     */
    _add_button: function ()
    {
        var that = this;

        this.button.text('Show More...');

        if (this.options.display_button != 'outside')
        {
            this.button.appendTo(this.wrapper);
        }
        else
        {
            this.button.appendTo(this.element);
        }

        this.button.on('click', function ()
        {
            that._show_hide_items();
        });
    },


    /**
     * Remove button from list
     *
     * @private
     */
    _remove_button: function ()
    {
        this.button.remove();
    },
    

    _add_wrapper: function ()
    {
        this.items.appendTo(this.wrapper);
        this.hidden_items.appendTo(this.hidden_list);
        this.hidden_list.appendTo(this.wrapper);
        this.hidden_list.hide();
        this.wrapper.appendTo(this.element);  
    },


    _remove_wrapper: function ()
    {
        this.wrapper.children().appendTo(this.element);
        this.wrapper.empty().remove();

        this.hidden_list.children().appendTo(this.element);
        this.hidden_list.empty().remove();
    },


    _show_hide_items: function ()
    {
        if (this.hidden_list.is(':hidden'))
        {
            this.button.text('Show Less...');
            this.hidden_list.show();
            this.wrapper.addClass(this.options.open_class);
            
        }
        else
        {
            this.hidden_list.hide();
            this.button.text('Show More...');
            this.wrapper.removeClass(this.options.open_class);
        }

        if (this.options.display_button != 'outside')
        {
            this.button.appendTo(this.wrapper);
        }
    },


    /**
     * Destroy plugin instance
     */
    destroy: function ()
    {
        this._remove_button();
        this._remove_wrapper();

        $.Widget.prototype.destroy.call(this);
    },


    /**
     * Reload plugin instance
     */
    reload: function ()
    {
        this._remove_button();
        this._remove_wrapper();

        this._init();
    }
});
