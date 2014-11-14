<?
/** Events by Sensor plugin template **
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

        "Name" => "Events by sensor/plugin",
        "Revision" => "1", 
	"Category" => "Incidents",
        "URL" => "events_by_sensor_type_data.php",
        "Homepage" => "http://www.ossim.com/",
        "Description" => "Displays events by sensor plugin.",
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
