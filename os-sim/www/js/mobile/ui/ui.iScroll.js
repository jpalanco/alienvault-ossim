/**
 * Replace default scroller
 *
 * @author Anton Shevchuk (http://anton.shevchuk.name)
 * @copyright (c) 2009 jQuery iPhone UI (http://iphone.hohli.com)
 * @license   Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.
 * 
 * @version 0.1
 */
(function($){
    $.widget('ui.iScroll', {
		 options:{
		 	height:null
		 },
         _init: function(){
             var _self = this;
             
			 if (!this.options.height) {
			 	this.height = this.element.outerHeight();
			 } else {
			 	this.height = this.options.height;
			 }
        	 
			 
             // build in dom
			 this.element.addClass('iphoneui');
             this.element.wrap($('<div />').addClass('iscrollui')); 
             this.element.after('<div class="iscrollbar"><div></div></div>');
             
             this.container = this.element.parent('.iscrollui');

             this.container.css({height: this.height, overflow:'hidden'});
             this.element.css({height:'auto'});
			 
             // "global" variables
    		 this.difference = this.element.height() - this.height;
    		 if (this.difference <= 0) return;
    		 
    		 this.ratio = this.height/this.element.height();
    		 this.marginTop = 0;
             this.oldY = 0;
             this.newY = 0;
             this.move = false;
             this.TO = null;
             
        	 // scrollbar
        	 this.scrollbar  = this.container.find('.iscrollbar');
        	 this.scroller   = this.container.find('.iscrollbar div');
             
             // change height of scrollbar  
        	 this.scrollbar.height(this.height);
    		 this.scroller.height(this.ratio*this.container.height());
    		 

             // http://www.jdempster.com/category/jquery/disabletextselect/
             if (typeof $.fn.disableTextSelect == 'function') {
                this.container.disableTextSelect();
             }
             
			 if (typeof $.fn.mousewheel != 'function') {
			 	 console.error('Mousewheel jQuery plugin is required for iPhone.iScroll widget. More information on http://iphone.hohli.com');
				 return;
			 }
			 
             this.container.mousewheel( function (event, direction)  {                
                if ( _self.marginTop <= 0  && ( _self.marginTop + _self.difference >= 0 ) ) {
        		        _self.element.stop();
        		            			
        			    if ( direction < 0 ){
        			        _self.marginTop -= 60;
        			        _self.element.animate({'marginTop':_self.marginTop}, 
                                function(){
                                    if( _self.difference + _self.marginTop < 0 ) {
                                        _self.marginTop = -_self.difference;
                                        _self.element.animate({'marginTop':_self.marginTop});
                                    }
                                });
        			    } else {
        				    _self.marginTop += 60;
                            _self.element
                                .animate({'marginTop':_self.marginTop}, 
                                    function(){
                                        if( _self.marginTop > 0 ) {
                                            _self.marginTop = 0;
                                            _self.element.animate({'marginTop':_self.marginTop});
                                        }
                                    });
        			    }
        			    _self._scroll();
        			}
        		    event.stopPropagation();
        		    event.preventDefault();
    		 });
    		
    		this.container.mousedown(function(event) {
    		    _self.element.stop();
    		    _self.oldY = event.pageY;
                _self.move = true;
                /*_self._showScroll();*/
            });
            
            $(document).mouseup(function(event){
                _self.move = false;
                _self.oldY = 0;
                if (_self.marginTop > 0) {
                    _self.marginTop = 0;
                    _self.element.animate({'marginTop':_self.marginTop});
                } else if( _self.difference + _self.marginTop < 0 ) {
                    _self.marginTop = -_self.difference;
                    _self.element.animate({'marginTop':_self.marginTop});
                }
            });
            $(document).mousemove(function(event){
                if (_self.move && _self.oldY!=0) {
                    _self.newY = event.pageY;
                    _self.marginTop = _self.marginTop + _self.newY - _self.oldY;
                    _self.element.css({ 'marginTop' : _self.marginTop});
                    _self.oldY = _self.newY;
                    
                    _self._scroll();
                }
            });
        },
        _scroll:function() {
             this._showScroll();
             this.scroller.stop(true,true);
		     if ( this.marginTop <= 0  && ( this.marginTop + this.difference >= 0 ) ) {
		         this.scroller.animate({'marginTop':Math.abs(this.ratio*this.marginTop)},100);
		     } else if (this.marginTop > 0) {
		         this.scroller.animate({'marginTop':0},100);
		     } else if ( this.marginTop + this.difference <= 0  ) {
		         this.scroller.animate({'marginTop':Math.abs(this.ratio*this.difference)},100);
		     }
        },
        _showScroll:function() {
            var _self = this;
            clearTimeout(this.TO);
            this.scrollbar.show();
            this.TO = setTimeout(function() {_self._hideScroll();}, 1000);
        },
        _hideScroll:function() {
            this.scrollbar.hide();
        }
    });
})(jQuery);