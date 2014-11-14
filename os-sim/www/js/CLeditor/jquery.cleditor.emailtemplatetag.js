(function($) {
  // Define the mail button
  $.cleditor.buttons.mail = {
    name: "mail",
    image: "../../js/CLeditor/images/at.png",
    title: "Mail Tags",
    command: "inserthtml",
	popupClass: "cleditorList",
    popupName: "mail",
	popupHover: true,
	buttonClick: function(e, data) {
		$(data.popup).width(200);
	},
	popupClick: function(e, data) {
		var editor = data.editor;
		data.value = e.target.innerHTML;
    }
	};
	  
	var elemts = "ID,INCIDENT_NO,TITLE,EXTRA_INFO,IN_CHARGE_NAME,IN_CHARGE_LOGIN,IN_CHARGE_EMAIL,IN_CHARGE_DPTO,IN_CHARGE_COMPANY,PRIORITY_NUM,PRIORITY_STR,TAGS,CREATION_DATE,STATUS,CLASS,TYPE,LIFE_TIME,TICKET_DESCRIPTION,TICKET_ACTION,TICKET_AUTHOR_NAME,TICKET_AUTHOR_EMAIL,TICKET_AUTHOR_DPTO,TICKET_AUTHOR_COMPANY,TICKET_EMAIL_CC,TICKET_HISTORY,TICKET_INVERSE_HISTORY";
	var $content = $("<div>");

	$.each(elemts.split(","), function(idx, tag) {
	$("<div>")
		.html(tag)
		.appendTo($content);			
	});
	$.cleditor.buttons.mail.popupContent = $content.children();
	    
  // Add the button to the default controls before the bold button
	$.cleditor.defaultOptions.controls = $.cleditor.defaultOptions.controls
	.replace("bold", "mail bold");

})(jQuery);