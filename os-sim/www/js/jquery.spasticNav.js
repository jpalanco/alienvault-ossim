(function($) 
{
	$.fn.spasticNav = function(options) 
	{
	
		options = $.extend({
			overlap : 0,
			speed : 500,
			reset : 500,
			color : '#615d5b',
			easing : 'easeOutExpo',
			action_click: function(val){}
		}, options);
	
		return this.each(function() 
		{
		 	var nav = $(this),
		 		currentPageItem = $('.active', nav),
		 		blob,
		 		reset;
		 		
		 	$('<li id="blob"></li>').css({
		 		width : currentPageItem.outerWidth(),
		 		height : currentPageItem.outerHeight() + options.overlap,
		 		left : currentPageItem.position().left,
		 		top : currentPageItem.position().top - options.overlap / 2,
		 		backgroundColor : options.color
		 	}).appendTo(this);
		 	
		 	blob = $('#blob', nav);

			$('li:not(#blob)', nav).click(function() 
			{
				currentPageItem = $('.active', nav);

				blob.animate(
					{
						left : $(this).position().left,
						width : $(this).width()
					},
					{
						duration : options.speed,
						easing : options.easing,
						queue : false
					}
				);
			});
		 
		}); // end each
	
	};

})(jQuery);