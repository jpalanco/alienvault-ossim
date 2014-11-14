// -------------------------------------------------------------------
// markItUp!
// -------------------------------------------------------------------
// Copyright (C) 2008 Jay Salvat
// http://markitup.jaysalvat.com/
// -------------------------------------------------------------------
// Mediawiki Wiki tags example
// -------------------------------------------------------------------
// Feel free to add more tags
// -------------------------------------------------------------------

var mySettings = {
	previewParserPath:	'../js/markitup/wikiparser.php', // path to your Wiki parser
	previewInWindow: 'width=800, height=600, resizable=yes, scrollbars=yes',
	markupSet:  [ 	
		{name:'Heading 1', key:'1', openWith:'== ', closeWith:' ==', placeHolder:'Your title here...' },
		{name:'Heading 2', key:'2', openWith:'=== ', closeWith:' ===', placeHolder:'Your title here...' },
		{name:'Heading 3', key:'3', openWith:'==== ', closeWith:' ====', placeHolder:'Your title here...' },
		{separator:'---------------' },		
		{name:'Bold', key:'B', openWith:"'''", closeWith:"'''"}, 
		{name:'Italic', key:'I', openWith:"''", closeWith:"''"}, 
		{separator:'---------------' },
		{name:'Bulleted list', openWith:'(!(* |!|*)!)'}, 
		{name:'Numeric list', openWith:'(!(# |!|#)!)'}, 
		{separator:'---------------' },
		{
			name:'Picture',
			key:'P',
			beforeInsert: function(markItUp) { InlineUpload.display(markItUp) },
		},
		{name:'Link', key:'L', openWith:"[[![Url:!:http://]!] [![Name:!:You link Name]!]", closeWith:']'},
		{separator:'---------------' },
		{name:'Clean', className:'clean', replaceWith:function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") } },		
		{name:'Preview', className:'preview',  call:'preview'}
	]
}



var InlineUpload = 
{
	dialog: null,
	block: '',
	offset: {},
	options: {
		container_class: 'markItUpInlineUpload',
		form_id: 'inline_upload_form',
		action: 'upload.php',
		inputs: {
			ticket: { label: '', id: 'ticket', name: 'ticket' },
			file: { label: 'File', id: 'inline_upload_file', name: 'inline_upload_file' }
		},
		submit: { id: 'inline_upload_submit', value: 'upload' },
		close: 'inline_upload_close',
		iframe: 'inline_upload_iframe'
	},
	display: function(hash)
	{		
		var self = this;
		
		/* Find position of toolbar. The dialog will inserted into the DOM elsewhere
		 * but has position: absolute. This is to avoid nesting the upload form inside
		 * the original. The dialog's offset from the toolbar position is adjusted in
		 * the stylesheet with the margin rule.
		 */
		this.offset = $(hash.textarea).siblings('.markItUpHeader').offset();	
		/* We want to build this fresh each time to avoid ID conflicts in case of
		 * multiple editors. This also means the form elements don't need to be
		 * cleared out.
		 */
		this.dialog = $([
			'<div class="',
			this.options.container_class,
			'"><div><form id="',
			this.options.form_id,
			'" action="',
			this.options.action,
			'" target="',
			this.options.iframe,
			'" method="post" enctype="multipart/form-data">',
			'<input name="',
			this.options.inputs.ticket.name,
			'" id="',
			this.options.inputs.ticket.id,
			'" value="',
			$('#ticket_id').val(),
			'" type="hidden" /><input id="',
			this.options.inputs.file.id,
			'" name="',
			this.options.inputs.file.name,
			'" type="file" /><br><br><input id="',
			this.options.submit.id,
			'" type="button" value="',
			this.options.submit.value,
			'" /></form><div id="upmsg"></div><div id="',
			this.options.close,
			'"></div><iframe id="',
			this.options.iframe,
			'" name="',
			this.options.iframe,
			'" src="about:blank"></iframe></div></div>',
		].join(''))
			.appendTo(document.body)
			.hide()
			.css('top', this.offset.top)
			.css('left', this.offset.left+75);
				
		
		/* init submit button
		 */
		$('#'+this.options.submit.id).click(function()
		{
			$('#'+self.options.form_id).submit();
		});
	
				
		/* init cancel button
		 */
		$('#'+this.options.close).click(function(){
			
			self.cleanUp();

		});
		
		
		/* form response will be sent to the iframe
		 */
		$('#'+this.options.iframe).bind('load', function()
		{
			
			$('#upmsg').hide();
			$('#upmsg').text('');
					
			if($(this).contents().find('body').text() == '')
			{
				return false;
			}
			
			
			var response = $.evalJSON($(this).contents().find('body').text());
			
			if (response.status == 'success')
			{
				this.block = [
					'[[Image:',
					response.src,
					']]'
				];
				
				self.cleanUp();
				
				/* add the img tag
				 */
				$.markItUp({ replaceWith: this.block.join('') } );
			}
			else
			{
				$('#upmsg').text(response.msg);
				$('#upmsg').show();
				//self.cleanUp();
			}
		});
		
		
		/* Finally, display the dialog
		 */
		this.dialog.fadeIn('slow');
	},
	cleanUp: function()
	{
		this.dialog.fadeOut().remove();
	}
};
