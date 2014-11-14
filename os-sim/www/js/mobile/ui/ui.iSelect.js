/**
 * Replace select form element
 *
 * @author Anton Shevchuk (http://anton.shevchuk.name)
 * @copyright (c) 2009 jQuery iPhone UI (http://iphone.hohli.com)
 * @license   Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.
 * 
 * @version 0.1
 */
(function($){
    $.widget('ui.iSelect', {
        _init: function(){
            var _self = this;
            
            // 74 - it's margin for set select of first element
			this._marginTop = 74;
			this._liHeight  = 44;
			
            this.width     = this.element.outerWidth();
							 
            this.iselect   = this.element.wrap('<div class="iphoneui iselectui"></div>').parent();
            this.iselect.append('<div class="ioverflow"></div>')
						.append('<div class="ioverflow-left"></div>')
						.append('<div class="ioverflow-right"></div>')
						.append('<div class="ioptions"><ul></ul></div>');
            
            this.iselect.css({width:this.width});
            
            this.select   = this.iselect.find('select');            
            this.selectUl = this.iselect.find('ul');
            
            this.overflow  = this.iselect.find('.ioverflow');
			// 34 - it's left+right side size
            this.overflow.css({width:this.width - 34});
            
            this.offset = this.selectUl.offset();
            this.marginTop = this._marginTop;
            this.selectEl  = 1;
            this.maxEl  = 0;

            this.oldY = 0;
            this.newY = 0;
            this.move = false;
//            this.TO = null;
            
    	    this.select.find('option').each(function(i){
    	        var title = $(this).html()||$(this).val();
    	        _self.selectUl.append('<li>'+title+'</li>');
    	        
    	        if ($(this).attr("selected")) {
    	            _self.selectEl = i+1;
    	        }
    	        _self.maxEl++;
    	    });
            
    	    // 44 - it's one element hight
            this.maxMargin = this._marginTop;
            this.minMargin = this._marginTop + this._liHeight - this.selectUl.height();
            
            
            this._select(this.selectEl);
    	
        	this.iselect.mousewheel(function(event, direction){
        	    // TODO: use direction as increment argument
        	    if ( direction < 0 ){
        	        if (_self.marginTop > _self.minMargin) {
        	            _self.selectEl ++;
        	            _self._select(_self.selectEl);
        	        }
    		    } else {		        
        	        if (_self.marginTop < _self.maxMargin) {
        	            _self.selectEl --;
        	            _self._select(_self.selectEl);
        	        }
    		    }
    		    event.stopPropagation();
    		    event.preventDefault();
        	});
        	
        	this.overflow.mousedown(function(event) {
    		    _self.selectUl.stop();
    		    _self.oldY = event.pageY;
                _self.move = true;
                
    		    event.stopPropagation();
    		    event.preventDefault();
            });
            
            $(document).mouseup(function(event){
                if (_self.move) {
                    _self.move = false;
                    _self.selectEl = Math.round(-(_self.marginTop - _self._marginTop)/_self._liHeight) + 1;
                    _self._select(_self.selectEl);
                }
            });
            
            $(document).mousemove(function(event){
                if (_self.move && _self.oldY != 0) {
                    _self.newY = event.pageY;
                    
                    var diff = _self.marginTop + (_self.newY - _self.oldY);
                    if (diff >= _self.minMargin && diff <= _self.maxMargin) {
                        _self.marginTop =  _self.marginTop + (_self.newY - _self.oldY);
                        _self._scroll(_self.marginTop);
                    }
                    
                    _self.oldY = _self.newY;
                }
            });
            
            
        },
        _scroll: function(margin) {
            this.selectUl.stop(true,true);
            this.selectUl.animate({'marginTop':margin});
        },
        _select: function(index) {
            this.selectUl.stop(true,true);
            this.marginTop = 74 - (44 * (index-1));
            this.select.find("option:selected").removeAttr("selected");
            this.select.find("option:nth-child("+index+")").attr("selected", "selected");
            this.selectUl.animate({'marginTop':this.marginTop});
        }
    });    
})(jQuery);