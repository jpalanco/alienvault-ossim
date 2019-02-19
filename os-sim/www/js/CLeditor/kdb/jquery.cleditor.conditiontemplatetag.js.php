(function($) {
  // Define the mail button
  $.cleditor.buttons.conditions = {
    name: "conditions",
    image: "cond.png",
    title: "Conditions Tags",
    command: "inserthtml",
	popupClass: "cleditorList",
    popupName: "conditions",
	popupHover: true,
	buttonClick: function(e, data) {
		$(data.popup).width(200);
	},
	popupClick: function(e, data, selection) {
		var editor = data.editor;
		var val    = e.target.innerHTML;
		var result = '';
		
		if(selection == '')
		{
			selection = '<br>';
		}
		else
		{
			selection = '<br>' + selection + '<br>';
		}
		
		result  = "{"+val+"}";
		result += selection;
		result += "{END"+val+"}<br>";

		data.value = result;
    }
	};
	  
	var elemts = "IF,ELSE";
	var $content = $("<div>");

	$.each(elemts.split(","), function(idx, tag) {
	$("<div>")
		.html(tag)
		.appendTo($content);			
	});
	$.cleditor.buttons.conditions.popupContent = $content.children();
	    

})(jQuery);
