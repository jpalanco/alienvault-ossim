/**
 * Create tabs navigation
 *
 * @author Anton Shevchuk (http://anton.shevchuk.name)
 * @copyright (c) 2009 jQuery iPhone UI (http://iphone.hohli.com)
 * @license   Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.
 * 
 * @version 0.1
 */
(function($){
    $.widget('ui.iTabs', {
        _init: function() {
        
			var $this = this.element;
            	$this.addClass('iphoneui')
					 .addClass('itabsui');
				
			$li = $this.find('li');
			
			$li.css({
					width:($this.width() / $li.length)
				});
	        
			$li.find('a').click(function(){
	    		if ($(this).parent().hasClass('active')) return false;
	    		
		    	var current = $this.find('li.active a');
	        	if (current.length) {
	        	    $(current.attr("href")).hide();
	        	}
	    		
	    		$li.removeClass("active");
	    		
	    		$(this).parent().addClass('active');    		
	    		
	    		$($(this).attr('href')).show();
	    		
	    		return false;
	    	});
	    	
	    	if ($this.find('li.active a').length == 0) {
	    	    $this.find('li:first').addClass('active');
	    	}
			
			$li.not('.active').find('a').each(function(){
				$($(this).attr('href')).hide();
			})
			
	    	//$('.tab').not(current.attr("href")).hide();
        }
    });
})(jQuery);