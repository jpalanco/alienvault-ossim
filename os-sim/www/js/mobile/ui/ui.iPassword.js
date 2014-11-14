/**
 * Replace password form element
 *
 * @author Anton Shevchuk (http://anton.shevchuk.name)
 * @copyright (c) 2009 jQuery iPhone UI (http://iphone.hohli.com)
 * @license   Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.
 * 
 * @version 0.1
 */
(function($){
    $.widget('ui.iPassword', {
         _init: function(){
             this.element.wrap($('<span/>').addClass('iphoneui').addClass('ipasswordui'));
             
             if (typeof $.fn.dPassword == 'function') {
                 this.element.dPassword();
             }
			 
			 if ($('label[for='+this.element.attr('id')+']').length > 0) {
			 	 $('label[for='+this.element.attr('id')+']').addClass('ilabelui');
			 }
         }
    });
})(jQuery);