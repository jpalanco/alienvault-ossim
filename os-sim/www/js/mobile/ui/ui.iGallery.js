/**
 * Create image gallery
 *
 * @author Anton Shevchuk (http://anton.shevchuk.name)
 * @copyright (c) 2009 jQuery iPhone UI (http://iphone.hohli.com)
 * @license   Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.
 * 
 * @version 0.2
 */
(function($){
    $.widget('ui.iGallery', {
        _init: function() {
            var _self = this;
            var $this = this.element;
            
			$this.addClass('iphoneui')
			     .addClass('igalleryui');
			
			// FIXME: need refactoring - wrapInner - it's bad
            $this.wrapInner('<div class="frame"></div>');

            this.frame = $this.find('.frame');
 
            
            var length = 0;
            var width  = $this.width();
            
            // filter
            this.frame.children('br,hr').remove();
            
            // each image pack to div 
            this.frame.children().each(function(){
                length++;
                $(this).wrap('<div class="slide"></div>');
                $(this).css({width:width});
            });
            
            this.oldX = 0;
            this.newX = 0;
            this.move = false;
            
            // 20px - it's margin between slides
            this.maxMargin = 0;
            this.minMargin = - ((width+20)*(length - 1)+40);
            this.marginLeft = -20;
            
            // set container width
            this.frame.css({
                width:      (width+20)*length+20,
                marginLeft: this.marginLeft
            });
            
			// simple mouse gestures
            this.frame.mousedown(function(event) {
    		    _self.frame.stop(true, true);
    		    _self.oldX = event.pageX;
                _self.move = true;
                
    		    event.stopPropagation();
    		    event.preventDefault();
            });
            
            $(document).mouseup(function(event){
                if (_self.move) {
                    _self.move = false;
                    var el = Math.round(_self.marginLeft/(width+20));
                    _self.marginLeft = el * (width+20) - 20;
                    _self._scroll();
                }
            });
            
            $(document).mousemove(function(event){
                if (_self.move && _self.oldX != 0) {
                    _self.newX = event.pageX;
                    
                    var diff = _self.marginLeft + (_self.newX - _self.oldX);                    
                    if (diff >= _self.minMargin && diff <= _self.maxMargin) {
                        _self.marginLeft =  _self.marginLeft + (_self.newX - _self.oldX);
                        _self._scroll();
                    }
                    _self.oldX = _self.newX;
                }
            });
        },
        _scroll: function() {
            this.frame.stop(true,true);
            this.frame.animate({'marginLeft':this.marginLeft});
        }
        
    });    
})(jQuery);