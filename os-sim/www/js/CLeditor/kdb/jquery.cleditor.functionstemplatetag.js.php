(function($) {
  // Define the mail button
  $.cleditor.buttons.functions = {
    name: "functions",
    image: "func.png",
    title: "Functions Tags",
    command: "inserthtml",
	popupClass: "cleditorList",
    popupName: "functions",
	popupHover: true,
	buttonClick: function(e, data) {
		$(data.popup).width(200);
	},
	popupClick: function(e, data) {
			var editor = data.editor;
			var val    = e.target.innerHTML;
			//data.value = "<font color='#0A5903'>"+val+"</font>";
			data.value = val;
	    }
	};
	
	<?php 
	require_once 'av_init.php';
	
	$sintax = new KDB_Sintax();
	
	$funcs  = implode(',', array_keys($sintax->_operations_elements));
	
	?>
	  
	var elemts = '<?php echo $funcs ?>';
	var $content = $("<div>");

	$.each(elemts.split(","), function(idx, tag) {
	$("<div>")
		.html(tag)
		.appendTo($content);			
	});
	$.cleditor.buttons.functions.popupContent = $content.children();
	    

})(jQuery);