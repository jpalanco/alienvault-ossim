<?
/** Installed Software template **
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

        "Name" => "Installed Software",
        "Revision" => "1", 
	"Category" => "Inventory",
        "URL" => "inventory_soft_source.php",
        "Homepage" => "http://www.ossim.com/",
        "Description" => "Installed software. OCS must be running for this to work.",
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
