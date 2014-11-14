<?
/** Incident Status template **
*
* Strings: Name, Revision, URL, Homepage, Description.
* Array: Parameters.
*
*  Parameters items:
*     Strings: Name, Value, Description. 
* The Parameters width and height should be in all the plugins (but there's no need to fill them).
*
**/

$item=array(

        "Name" => "Incident status",
        "Revision" => "1", 
	"Category" => "Incidents",
        "URL" => "incident_status_source.php",
        "Homepage" => "http://www.ossim.com/",
        "Description" => "Displays the percentage of incidents by status.",
        "Parameters" => array(
                array(
                "Name" => "width",
                "Value" => "470",
                "Description" => "Window width"),
                array(
                "Name" => "height",
                "Value" => "350",
                "Description" => "Window height")
        )
);

?>
