<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
**/


/**
* Function list:
* - InitArray()
* - RegisterGlobalState()
* - CleanVariable()
* - SetSessionVar()
* - ImportHTTPVar()
* - ExportHTTPVar()
* - filterSql()
* - XSSPrintSafe()
*/


defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
/* ***********************************************************************
* Function: InitArray()
*
* @doc Defines an initializes a 1 or 2 dimensional PHP array.
*
* @param $a      (in/out) array to initialize
* @param $dim1   number of elements of first dimension
* @param $dim2   number of elements of second dimension
* @param $value  default value
*
************************************************************************/
function InitArray(&$a, $dim1, $dim2, $value) {
    $a = "";
    /* determine the number of dimensions in the array */
    if ($dim2 == 0) /* 1-dim */
    for ($i = 0; $i < $dim1; $i++) $a[$i] = $value;
    else
    /* 2-dim */
    for ($i = 0; $i < $dim1; $i++) for ($j = 0; $j < $dim2; $j++) $a[$i][$j] = $value;
}
/* ***********************************************************************
* Function: RegisterGlobalState()
*
* @doc Application-specific wrapper for PHP session_start().  It performs
*      a couple of additional configuration checks (notably for custom
*      PHP session handlers).
*
************************************************************************/
function RegisterGlobalState() {
    /* Deal with user specified session handlers */
    if (session_module_name() == "user") {
        $phperrorcsession = gettext("PHP ERROR: A custom (user) PHP session have been detected. However, BASE has not been set to explicitly use this custom handler.  Set <CODE>use_user_session=1</CODE> in <CODE>base_conf.php</CODE>");
        $phperrorcsessioncode = gettext("PHP ERROR: A custom (user) PHP session hander has been configured, but the supplied hander code specified in <CODE>user_session_path</CODE> is invalid.");
        $phperrorcsessionvar = gettext("PHP ERROR: A custom (user) PHP session handler has been configured, but the implementation of this handler has not been specified in BASE.  If a custom session handler is desired, set the <CODE>user_session_path</CODE> variable in <CODE>base_conf.php</CODE>.");
        if ($GLOBALS['use_user_session'] != 1) {
            ErrorMessage($phperrorcsession);
            die();
        } else if ($GLOBALS['user_session_path'] != "") {
            if (is_file($GLOBALS['user_session_path'])) {
            	ErrorMessage(_("Custom User PHP session handlers are deprecated in this version of SIEM Events Console"));
            	die();
                //include_once ($GLOBALS['user_session_path']);
                //if ($GLOBALS['user_session_function'] != "") $GLOBALS['user_session_function']();
            } else {
                ErrorMessage($phperrorcsessioncode);
                die();
            }
        } else {
            ErrorMessage($phperrorcsessionvar);
            die();
        }
    }
    //session_start();
    //if ($GLOBALS['debug_mode'] > 0) echo '<FONT COLOR="#FF0000">' . gettext("Session Registered") . '</FONT><BR>';
}
/* ***********************************************************************
* Function: CleanVariable()
*
* @doc Removes invalid characters/data from a variable based on a
*      specified mask of acceptable data or a list of explicit values.
*
*      Note: both mask and explicit list can be used a a time
*
* @param item        variable to scrub
* @param valid_data  mask of valid characters
* @param exception   array with explicit values to match
*
* @return a sanitized version of the passed variable
*
************************************************************************/
function CleanVariable($item, $valid_data, $exception = "") {
    /* Determine whether a variable is set */
    if (!isset($item)) return $item;
    /* Recursively clean array elements -- nikns */
    if (is_array($item)) {
        foreach($item as $key => $value) $item[$key] = CleanVariable($value, $valid_data, $exception);
        return $item;
    }
    /* Check the exception value list first */
    if ($exception != "" && in_array($item, $exception)) return $item;
    if ($valid_data == "") return ""; //return $item;
    
    $regex_mask = "";
    if (($valid_data & VAR_DIGIT) > 0) $regex_mask = $regex_mask . "0-9";
    if (($valid_data & VAR_LETTER) > 0) $regex_mask = $regex_mask . "A-Za-z";
    if (($valid_data & VAR_ULETTER) > 0) $regex_mask = $regex_mask . "A-Z";
    if (($valid_data & VAR_LLETTER) > 0) $regex_mask = $regex_mask . "a-z";
    if (($valid_data & VAR_ALPHA) > 0) $regex_mask = $regex_mask . "0-9A-Za-z";
    if (($valid_data & VAR_HEX) > 0) $regex_mask = $regex_mask . "0-9A-Fa-f";
    if (($valid_data & VAR_SPACE) > 0) $regex_mask = $regex_mask . "\ ";
    if (($valid_data & VAR_PERIOD) > 0) $regex_mask = $regex_mask . "\.";
    if (($valid_data & VAR_FSLASH) > 0) $regex_mask = $regex_mask . "\/";
    if (($valid_data & VAR_OPAREN) > 0) $regex_mask = $regex_mask . "\(";
    if (($valid_data & VAR_CPAREN) > 0) $regex_mask = $regex_mask . "\)";
    if (($valid_data & VAR_BOOLEAN) > 0) $regex_mask = $regex_mask . "\)";
    if (($valid_data & VAR_OPERATOR) > 0) $regex_mask = $regex_mask . "\)";
    if (($valid_data & VAR_USCORE) > 0) $regex_mask = $regex_mask . "\_";
    if (($valid_data & VAR_AT) > 0) $regex_mask = $regex_mask . "\@";
    /* Score (\-) always must be at the end of the character class */
    if (($valid_data & VAR_PUNC) > 0) $regex_mask = $regex_mask . "\~\!\#\$\%\^\&\*\_\=\+\:\;\,\.\?\ \(\))\-\[\]";
    if (($valid_data & VAR_SCORE) > 0) $regex_mask = $regex_mask . "\-";
    return preg_replace("/[^" . $regex_mask . "]/", "", $item);
}
/* ***********************************************************************
* Function: SetSessionVar()
*
* @doc Handles retrieving and updating persistant session (criteria)
*      data.  This routine handles the details of checking for criteria
*      updates passed through POST/GET and resolving this with values
*      that may already have been set and stored in the session.
*
*      All criteria variables need invoke this function before they are
*      used for the first time to extract their previously stored values,
*      and process potential updates to their value.
*
*      Note: Validation of user input is not performed by this routine.
*
* @param $var_name  name of the persistant session variable to retrieve
*
* @return the updated value of the persistant session variable named
*         by $var_name
*
************************************************************************/
function SetSessionVar($var_name) {
    if (isset($_POST[$var_name])) {
        // if ($GLOBALS['debug_mode'] > 0) echo "importing POST var '$var_name'<BR>";
        return $_POST[$var_name];
    } else if (isset($_GET[$var_name])) {
        // if ($GLOBALS['debug_mode'] > 0) echo "importing GET var '$var_name'<BR>";
        return $_GET[$var_name];
    } else if (isset($_SESSION[$var_name])) {
        // if ($GLOBALS['debug_mode'] > 0) echo "importing SESSION var '$var_name'<BR>";
        return $_SESSION[$var_name];
    } else return "";
}
/* ***********************************************************************
* Function: ImportHTTPVar()
*
* @doc Handles retrieving temporary state variables needed to present a
*      given set of results (e.g., sort order, current record).  The
*      values of these variables are never persistantly stored.  Rather,
*      they are passed as HTTP POST and GET parameters.
*
*      All temporary variables need invoke this function before they are
*      used for the first time to extract their value.
*
*      Optionally, sanitization parameters can be set, ala CleanVariable()
*      syntax to validate the user input.
*
* @param $var_name     name of the temporary state variable to retrieve
* @param $valid_data   (optional) list of valid character types
*                                 (see CleanVariable)
* @param $exception    (optional) array of explicit values the imported
*                      variable must be set to
*
* @see CleanVariable
*
* @return the sanitized value of the temporary state variable named
*         by $var_name
*
************************************************************************/
function ImportHTTPVar($var_name, $valid_data = "", $exception = "") {
    $tmp = "";
    if (isset($_POST[$var_name])) {
        //if ( $debug_mode > 0 )  echo "importing POST var '$var_name'<BR>";
        $tmp = $_POST[$var_name];
    } else if (isset($_GET[$var_name])) {
        //if ( $debug_mode > 0 )  echo "importing GET var '$var_name'<BR>";
        $tmp = $_GET[$var_name];
    } else $tmp = "";
    return CleanVariable($tmp, $valid_data, $exception);
}
/* ***********************************************************************
* Function: ExportHTTPVar()
*
* @doc Handles export of a temporary state variables needed to present a
*      given set of results (e.g., sort order, current record).  This
*      routine creates a hidden HTML form variable.
*
*      Note: The user is responsible for generating the appropriate HTML
*            form code.
*
*      Security Note: Only, temporary variables should make use of this
*                     function. These values are exposed in HTML to the
*                     user; he is free to modify them.
*
* @param $var_name     name of the temporary state variable to export
* @param $var_value   value of the temporary state variable
*
* @see ImportHTTPVar
*
************************************************************************/
function ExportHTTPVar($var_name, $var_value) {
    echo "<INPUT TYPE=\"hidden\" NAME=\"$var_name\" VALUE=\"".Util::htmlentities($var_value)."\">\n";
}
/* ***********************************************************************
* Function: filterSql()
*
* @doc Filters the input string so that it can be safely used in SQL queries.
*
* @param $item             value of the variable to filter
* @param $force_alert_db   (default 0 - use current db)
*
*
************************************************************************/
function filterSql($item, $db = NULL) {
    GLOBAL $DBlib_path, $DBtype, $db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password;
    /* Determine whether a variable is set */
    if (!isset($item)) return $item;
    /* Recursively filter array elements -- nikns */
    if (is_array($item)) {
        for ($i = 0; $i < count($item); $i++) $item[$i] = XSSPrintSafe($item[$i]);
        return $item;
    }
    
    // *********** Aux connection commented, unable to open multiple connections with mysqli *************
    /*
    if (!is_object($db)) {
        $dbc = NewBASEDBConnection($DBlib_path, $DBtype);
        $dbc->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password, $force_alert_db);
        //magic_quotes_gpc safe adodb qmagic() returns escaped $item in quotes
        $item = $dbc->DB->qmagic($item);
        $dbc->baseClose();
    } else {
        $item = $db->DB->qmagic($item);
    }
    */
    /* cut off first and last character (quotes added by qmagic()) */
    //$item = substr($item, 1, strlen($item) - 2);
    return $item;
}
/* ***********************************************************************
* Function: XSSPrintSafe()
*
* @doc Converts unsafe html special characters to printing safe
*      equivalents so we can safetly print them.
*
************************************************************************/
function XSSPrintSafe($item) {
    /* Determine whether a variable is set */
    if (!isset($item)) return $item;
    /* Recursively convert array elements -- nikns */
    if (is_array($item)) {
        for ($i = 0; $i < count($item); $i++) $item[$i] = XSSPrintSafe($item[$i]);
        return $item;
    }
    return Util::htmlentities($item);
}
?>
