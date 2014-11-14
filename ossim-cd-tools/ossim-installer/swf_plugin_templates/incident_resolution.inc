<?
/** Incident Resolution Time template **
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

        "Name" => "Incident Resolution Time",
        "Revision" => "1", 
	"Category" => "Incidents",
        "URL" => "incident_resolution_source.php",
        "Homepage" => "http://www.ossim.com/",
        "Description" => "Displays the incidents by resolution time.",
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
