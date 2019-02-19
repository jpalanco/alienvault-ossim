<?php
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

require_once dirname(__FILE__) . '/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');

$job_id = POST('job_id');

$validate = array ('job_id' => array('validation' => 'OSS_UUID', 'e_message' => 'illegal:' . _('Job ID')));

$validation_errors = validate_form_fields('POST', $validate);

if (is_array($validation_errors) && !empty($validation_errors))
{
    $data['status'] = 'error';
    $data['data']   = $validation_errors;
}
else
{
    try
    {
        $data = Ossec_agent::check_deployment_status($job_id);
    }
    catch(Exception $e)
    {
        $data['status'] = 'error';
        $data['data']   = $e->getMessage();
    }
}

echo json_encode($data);
exit();
