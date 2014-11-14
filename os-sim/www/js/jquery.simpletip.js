var keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZ" + //all caps
"abcdefghijklmnopqrstuvwxyz" + //all lowercase
"0123456789+/="; // all numbers plus +/=

function base64_decode(inp)
{
var out = ""; //This is the output
var chr1, chr2, chr3 = ""; //These are the 3 decoded bytes
var enc1, enc2, enc3, enc4 = ""; //These are the 4 bytes to be decoded
var i = 0; //Position counter
// remove all characters that are not A-Z, a-z, 0-9, +, /, or =
var base64test = /[^A-Za-z0-9\+\/\=]/g;
if (base64test.exec(inp)) { //Do some error checking
alert("There were invalid base64 characters in the input text.\n" +
"Valid base64 characters are A-Z, a-z, 0-9, ?+?, ?/?, and ?=?\n" +
"Expect errors in decoding.");
}
inp = inp.replace(/[^A-Za-z0-9\+\/\=]/g, "");
do { //Here’s the decode loop.
//Grab 4 bytes of encoded content.
enc1 = keyStr.indexOf(inp.charAt(i++));
enc2 = keyStr.indexOf(inp.charAt(i++));
enc3 = keyStr.indexOf(inp.charAt(i++));
enc4 = keyStr.indexOf(inp.charAt(i++));
//Heres the decode part. There’s really only one way to do it.
chr1 = (enc1 << 2) | (enc2 >> 4);
chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
chr3 = ((enc3 & 3) << 6) | enc4;
//Start to output decoded content
out = out + String.fromCharCode(chr1);
if (enc3 != 64) {
out = out + String.fromCharCode(chr2);
}
if (enc4 != 64) {
out = out + String.fromCharCode(chr3);
}
//now clean out the variables used
chr1 = chr2 = chr3 = "";
enc1 = enc2 = enc3 = enc4 = "";
} while (i < inp.length); //finish off the loop
//Now return the decoded values.
return out;
}


(function($){
   function Simpletip(elem, conf)
   {
      var self = this;
      elem = jQuery(elem);
      
      var tooltip = jQuery(document.createElement('div'))
                     .addClass(conf.baseClass)
                     .addClass( (conf.fixed) ? conf.fixedClass : '' )
                     .addClass( (conf.persistent) ? conf.persistentClass : '' )
                     .html(conf.content)
                     .appendTo(elem);
      
      if(!conf.hidden) tooltip.show();
      else tooltip.hide();
      
      if(!conf.persistent)
      {
         elem.hover(
            function(event){ self.show(event) },
            function(){ self.hide() }
         );
         
         if(!conf.fixed)
         {
            elem.mousemove( function(event){ 
               if(tooltip.css('display') !== 'none') self.updatePos(event); 
            });
         };
      }
      else
      {
         elem.click(function(event)
         {
            if(event.target === elem.get(0))
            {
               if(tooltip.css('display') !== 'none')
                  self.hide();
               else
                  self.show();
            };
         });
         
         jQuery(window).mousedown(function(event)
         { 
            if(tooltip.css('display') !== 'none')
            {
               var check = (conf.focus) ? jQuery(event.target).parents('.stooltip').andSelf().filter(function(){ return this === tooltip.get(0) }).length : 0;
               if(check === 0) self.hide();
            };
         });
      };
      
      
      jQuery.extend(self,
      {
         getVersion: function()
         {
            return [1, 2, 0];
         },
         
         getParent: function()
         {
            return elem;
         },
         
         getTooltip: function()
         {
            return tooltip;
         },
         
         getPos: function()
         {
            return tooltip.offset();
         },
         
         setPos: function(posX, posY)
         {
            var elemPos = elem.offset();
            
            if(typeof posX == 'string') posX = parseInt(posX) + elemPos.left;
            if(typeof posY == 'string') posY = parseInt(posY) + elemPos.top;
            
            tooltip.css({ left: posX, top: posY });
            
            return self;
         },
         
         show: function(event)
         {
            conf.onBeforeShow.call(self);
            
            self.updatePos( (conf.fixed) ? null : event );
            
            switch(conf.showEffect)
            {
               case 'fade': 
                  tooltip.fadeIn(conf.showTime); break;
               case 'slide': 
                  tooltip.slideDown(conf.showTime, self.updatePos); break;
               case 'custom':
                  conf.showCustom.call(tooltip, conf.showTime); break;
               default:
               case 'none':
                  tooltip.show(); break;
            };
            
            tooltip.addClass(conf.activeClass);
            
            conf.onShow.call(self);
            
            return self;
         },
         
         hide: function()
         {
            conf.onBeforeHide.call(self);
            
            switch(conf.hideEffect)
            {
               case 'fade': 
                  tooltip.fadeOut(conf.hideTime); break;
               case 'slide': 
                  tooltip.slideUp(conf.hideTime); break;
               case 'custom':
                  conf.hideCustom.call(tooltip, conf.hideTime); break;
               default:
               case 'none':
                  tooltip.hide(); break;
            };
            
            tooltip.removeClass(conf.activeClass);
            
            conf.onHide.call(self);
            
            return self;
         },
         
         update: function(content)
         {
            tooltip.html(content);
            conf.content = content;
            
            return self;
         },
         
         load: function(uri, data)
         {
            conf.beforeContentLoad.call(self);
            
            tooltip.load(uri, data, function(){ conf.onContentLoad.call(self); });
            
            return self;
         },
         
         boundryCheck: function(posX, posY)
         {
            var newX = posX + tooltip.outerWidth();
            var newY = posY + tooltip.outerHeight();
            
            var windowWidth = jQuery(window).width() + jQuery(window).scrollLeft();
            var windowHeight = jQuery(window).height() + jQuery(window).scrollTop();
            
            return [(newX >= windowWidth), (newY >= windowHeight)];
         },
         
         updatePos: function(event)
         {
            var tooltipWidth = tooltip.outerWidth();
            var tooltipHeight = tooltip.outerHeight();
            
            if(!event && conf.fixed)
            {
               if(conf.position.constructor == Array)
               {
                  posX = parseInt(conf.position[0]);
                  posY = parseInt(conf.position[1]);
               }
               else if(jQuery(conf.position).attr('nodeType') === 1)
               {
                  var offset = jQuery(conf.position).offset();
                  posX = offset.left;
                  posY = offset.top;
               }
               else
               {
                  var elemPos = elem.offset();
                  var elemWidth = elem.outerWidth();
                  var elemHeight = elem.outerHeight();
                  
                  switch(conf.position)
                  {
                     case 'top':
                        var posX = elemPos.left - (tooltipWidth / 2) + (elemWidth / 2);
                        var posY = elemPos.top - tooltipHeight;
                        break;
                        
                     case 'bottom':
                        var posX = elemPos.left - (tooltipWidth / 2) + (elemWidth / 2);
                        var posY = elemPos.top + elemHeight;
                        break;
                     
                     case 'left':
                        var posX = elemPos.left - tooltipWidth;
                        var posY = elemPos.top - (tooltipHeight / 2) + (elemHeight / 2);
                        break;
                        
                     case 'right':
                        var posX = elemPos.left + elemWidth;
                        var posY = elemPos.top - (tooltipHeight / 2) + (elemHeight / 2);
                        break;
                     
                     default:
                     case 'default':
                        var posX = (elemWidth / 2) + elemPos.left + 20;
                        var posY = elemPos.top;
                        break;
                  };
               };
            }
            else
            {
               var posX = event.pageX;
               var posY = event.pageY;
            };
            
            if(typeof conf.position != 'object')
            {
               posX = posX + conf.offset[0];
               posY = posY + conf.offset[1]; 
               
               if(conf.boundryCheck)
               {
                  var overflow = self.boundryCheck(posX, posY);
                                    
                  if(overflow[0]) posX = posX - (tooltipWidth / 2) - (2 * conf.offset[0]);
                  if(overflow[1]) posY = posY - (tooltipHeight / 2) - (2 * conf.offset[1]);
               }
            }
            else
            {
               if(typeof conf.position[0] == "string") posX = String(posX);
               if(typeof conf.position[1] == "string") posY = String(posY);
            };
            
            self.setPos(posX, posY);
            
            return self;
         }
      });
   };
   
   jQuery.fn.simpletip = function(conf)
   { 
      // Check if a simpletip is already present
      var api = jQuery(this).eq(typeof conf == 'number' ? conf : 0).data("simpletip");
      if(api) return api;
      
      // Default configuration
      var defaultConf = {
         // Basics
         content: 'Loading data',
         persistent: false,
         focus: false,
         hidden: true,
         
         // Positioning
         position: 'default',
         offset: [0, 0],
         boundryCheck: true,
         fixed: true,
         
         // Effects
         showEffect: 'fade',
         showTime: 150,
         showCustom: null,
         hideEffect: 'fade',
         hideTime: 150,
         hideCustom: null,
         
         // Selectors and classes
         baseClass: 'stooltip',
         activeClass: 'active',
         fixedClass: 'fixed',
         persistentClass: 'persistent',
         focusClass: 'focus',
         
         // Callbacks
         onBeforeShow: function(){},
         onShow: function(){},
         onBeforeHide: function(){},
         onHide: function(){},
         beforeContentLoad: function(){},
         onContentLoad: function(){}
      };
      jQuery.extend(defaultConf, conf);
      
      this.each(function()
      {
         var el = new Simpletip(jQuery(this), defaultConf);
         jQuery(this).data("simpletip", el);  
      });
      
      return this; 
   };
})();