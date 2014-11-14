<?php
header("Content-type: text/javascript");
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* This package is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; version 2 dated June, 1991.
* You may not use, modify or distribute this program under any other version
* of the GNU General Public License.
*
* This package is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this package; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
* MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*
*/

//AJAX Validator
echo "var av_messages = new Array();
			av_messages['required_fields']      = '"._("The following fields are required:")."';
			av_messages['error_header']         = '"._("We found the following errors:")."';
			av_messages['submit_text']          = '"._("Updating")."...';
			av_messages['unknown_error']        = '"._("Sorry, operation was not completed due to an unknown error").".';
			av_messages['submit_checking']      = '"._("Update")."';\n\n";
			
			
echo "var messages = new Array();
			messages[0] = '"._("The following fields are required:")."';
			messages[1] = '"._("Invalid send method")."';
			messages[2] = '"._("Validation error, please submit form again")."';
			messages[3] = '"._("We found the following errors:")."';
			messages[4] = '"._("Updating")."...';
			messages[5] = '"._("Update")."';
			messages[6] = '"._("Reloading data")."'";			

?>