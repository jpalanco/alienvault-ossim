(function($) {
  // Define the mail button
  $.cleditor.buttons.vars = {
    name: "vars",
    image: "var.png",
    title: "Vars Tags",
    command: "inserthtml",
	popupClass: "cleditorList",
    popupName: "vars",
	popupHover: true,
	buttonClick: function(e, data) {
		$(data.popup).width(200);
	},
	popupClick: function(e, data) {
			var editor = data.editor;
			var val    = e.target.innerHTML;
			//data.value = "<font color='#0342B7'> $"+val+" </font>";
			data.value = "$"+val;
	    }
	};
	
	<?php 
	require_once 'av_init.php';
	
	$sintax = new KDB_Sintax();
	
	$vars   = implode(',', array_keys($sintax->_variable_list));
	
	?>
	
	var elemts = "<?php echo $vars ?>";
	var $content = $("<div>");

	$.each(elemts.split(","), function(idx, tag) {
	$("<div>")
		.html(tag)
		.appendTo($content);			
	});
	$.cleditor.buttons.vars.popupContent = $content.children();
	    

})(jQuery);
