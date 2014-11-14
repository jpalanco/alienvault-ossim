<?
/** OS Types template **
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

        "Name" => "OS Types",
        "Revision" => "1", 
	"Category" => "Inventory",
        "URL" => "inventory_os_source.php",
        "Homepage" => "http://www.ossim.com/",
        "Description" => "Operating system distribution by type. You must have OCS up and running for this to work correctly.",
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
