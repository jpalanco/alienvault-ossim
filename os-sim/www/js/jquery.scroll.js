( function( $ ) {
	$.fn.scrollLoad = function( options ) {
	
		var defaults = {
    		notif_div: '',
			url : '',
			data : '',
			ScrollAfterHeight : 90,
			onload : function( data, itsMe ) {
				alert( data );
			},
			start : function( itsMe ){},
			continueWhile : function() {
				return true;
			},
			getData : function( itsMe ) {
				return '';
			}
		};

		for (var eachProperty in defaults)
		{
			if (options[eachProperty])
			{
				defaults[eachProperty] = options[eachProperty];
			}
		}

		return this.each(function() {
    		
			this.scrolling = false;
			
			this.scrollPrev = this.onscroll ? this.onscroll : null;
			
			$(this).bind('scroll', function(e) {
    			
				if (this.scrollPrev) {
					this.scrollPrev();
				}
				if (this.scrolling) return;
				
				if (Math.round($(this).prop('scrollTop') / ($(this).prop('scrollHeight') - $(this).prop('clientHeight')) * 100) > defaults.ScrollAfterHeight)
				{
					defaults.start.call(this, this);
					this.scrolling = true;
					$this = $(this);
					
					$.ajax({
    					url : defaults.url,
    					data : defaults.getData.call( this, this ),
    				    type : 'post',
    				    dataType: "json",
    				    success : function(data)
    				    {
						    $this[0].scrolling = false;
						
						    defaults.onload.call($this[0], data, $this[0]);
						    
						    if(!defaults.continueWhile.call($this[0], data))
						    {
							    $this.unbind('scroll');
						    }
					    },
                        error: function(XMLHttpRequest, textStatus, errorThrown)
                        {
                            //Checking expired session
                            var session = new Session(XMLHttpRequest, '');
                        
                            if (session.check_session_expired() == true)
                            {
                                session.redirect();
                                return;
                            }
        
                            var msg_error = XMLHttpRequest.responseText;
                            
                            if (defaults.notif_div != '')
                            {
                                show_notification(defaults.notif_div, msg_error, 'nf_error', 5000, true);
                            }
                        }
				    });
				}
			});
		});
	}
})( jQuery );
