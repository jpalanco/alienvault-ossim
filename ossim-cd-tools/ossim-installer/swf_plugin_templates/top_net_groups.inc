<?
/** Top C&A network groups template **
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

        "Name" => "Top C&A net groups",
        "Revision" => "1",
	"Category" => "Risk",
        "URL" => "top_net_groups.php",
        "Homepage" => "http://www.ossim.com/",
        "Description" => "Displays the top N network group ordered by Compromise + Attack, where N is specified in the variable numnets. The refresh interval can be customized.",
        "Parameters" => array(
                array(
                "Name" => "refresh",
                "Value" => "5",
                "Description" => "Seconds to wait before refreshing the graph"),
                array(
                "Name" => "numnets",
                "Value" => "10",
                "Description" => "Number of nets to be displayed"),
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