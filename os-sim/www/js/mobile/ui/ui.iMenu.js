/**
 * Change style for menu
 *
 * @author Anton Shevchuk (http://anton.shevchuk.name)
 * @copyright (c) 2009 jQuery iPhone UI (http://iphone.hohli.com)
 * @license   Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.
 * 
 * @version 0.1
 */
(function($){
    $.widget('ui.iMenu', {
        _init: function() {
			var $this = this.element;
            	$this.addClass('iphoneui')
					 .addClass('imenuui');
            
            $this.find("li:has(a)", this.element[0]).hover(function(){
                $(this).addClass('active');
            }, function(){
                $(this).removeClass('active');
            });	
            
            $this.find("li:first-child", this.element[0]).addClass("first");
            $this.find("li:last-child", this.element[0]).addClass("last");
            $this.find("li:only-child", this.element[0]).addClass("single");
        }
    });
})(jQuery);