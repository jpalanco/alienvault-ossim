/**
 * Replace radio button form element
 *
 * @author Anton Shevchuk (http://anton.shevchuk.name)
 * @copyright (c) 2009 jQuery iPhone UI (http://iphone.hohli.com)
 * @license   Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.
 * 
 * @version 0.1
 */
(function($){
    $.widget('ui.iRadioButton', {
         _init:function(){
             var _self = this;
             this.visualElement = $('<div>&nbsp;</div>').addClass('iphoneui')
					 						   .addClass('iradiobuttonui')
                                               .bind('mouseenter.iRadioButton', function(){
                                                    $(this).addClass("active");
                                               })
                                               .bind('mouseleave.iRadioButton', function(){
                                                    $(this).removeClass("active");
                                               })
                                               .bind('click.iRadioButton', function(e){
                                				   $(this).toggleClass("on");
                                				   _self.element.click();
												   _self._toggle();
                                				   return false;
                                			   });
                                               
            if (this.element.is(':checked')) {
                this.visualElement.addClass('on');
            }
			 
			if ($('label[for='+this.element.attr('id')+']').length > 0) {
			 	$('label[for='+this.element.attr('id')+']').addClass('ilabelui');
			} 

            this.element.change(function(){
                _self._toggle();
                _self.element.trigger('on');
            });
            
			this.element.bind('on', function(){
				_self.visualElement.addClass("on");
			});
            
			this.element.bind('off', function(){
				_self.visualElement.removeClass("on");
			});
			
			this.element.before(this.visualElement);
			this.element.hide();
         },
        _toggle:function() {
			this.element
				.parent('form')
				.find(':radio[name='+this.element.attr('name')+']').not(this.element)
				.trigger('off');
		}
    });
})(jQuery);