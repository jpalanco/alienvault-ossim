/**
 * Replace checkbox form element
 *
 * @author Anton Shevchuk (http://anton.shevchuk.name)
 * @copyright (c) 2009 jQuery iPhone UI (http://iphone.hohli.com)
 * @license   Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.
 * 
 * @version 0.1
 */
(function($){
    $.widget('ui.iCheckBox', {
         _init: function(){
             var _self = this;
             this.visualElement = $('<div>&nbsp;</div>').addClass('iphoneui')
					 						   .addClass('icheckboxui')
                                               .bind('mouseenter.iCheckBox', function(){
                                                    $(this).addClass("active");
                                               })
                                               .bind('mouseleave.iCheckBox', function(){
                                                    $(this).removeClass("active");
                                               })
                                               .bind('click.iCheckBox', function(e){
                                				   _self.toggle();                            					
                                				   _self.element.click();
                                				   return false;
                                			   });
											   
             if (!this.element.is(':checked')) {
                 this.visualElement.addClass('off');
             }
			 
			 $('label[for='+this.element.attr('id')+']').each(function(){
			     $(this).addClass('ilabelui');
				 // change status by label click
				 $(this).click(function(){
				 	 _self.toggle();
				 });
			 });
			 
			 this.element.before(this.visualElement);
			 this.element.hide();
         },
		 toggle:function() {
			if (typeof $.fx.step.backgroundPosition == 'function') {
				if (this.element.is(':checked')) {
					this.visualElement.css({backgroundPosition:'0% 100%'}); // need for opera
					this.visualElement.animate({backgroundPosition:'(100% 100%)'},
					               300,
					               function(){
					                   $(this).css({backgroundPosition:'100% 0%'});
					               });
				} else {
					this.visualElement.css({backgroundPosition:'100% 100%'}); // need for opera
					this.visualElement.animate({backgroundPosition:'(0% 100%)'},
					               300,
					               function(){
					                   $(this).css({backgroundPosition:'0% 0%'});
					               });
				}
			} else {
				this.visualElement.toggleClass("off");
			}                    
		 }
        
    });
})(jQuery);