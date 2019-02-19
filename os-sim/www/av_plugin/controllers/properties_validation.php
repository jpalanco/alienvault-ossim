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


//Config File
require_once 'av_init.php';

if (!Session::am_i_admin())
{
    $error = _("You do not have permission to see this section");
    
    Util::response_bad_request($error);
}


/**
 * On demand validation from Step 2 (Properties)
 */
if (GET('ajax_validation') == TRUE || POST('ajax_validation_all') == TRUE)
{
    // Validation array
    
    $validate = array(
        'vendor'   =>  array('validation' => 'OSS_ALPHA',                'e_message'  =>  'illegal:' . _('Vendor')),
        'model'    =>  array('validation' => 'OSS_ALPHA',                'e_message'  =>  'illegal:' . _('Model')),
        'version'  =>  array('validation' => 'OSS_ALPHA, OSS_NULLABLE, OSS_DOT',  'e_message'  =>  'illegal:' . _('Version')),
	'product_type'  =>  array('validation' => 'OSS_DIGIT',           'e_message'  =>  'illegal:' . _('Product Type'))
    );
    
    $data['status'] = 'OK';

    $_method = (GET('ajax_validation') == TRUE) ? 'GET' : 'POST';
    
    $validation_errors = validate_form_fields($_method, $validate);
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
    
    foreach (array('vendor','model','version','product_type') as $name) {
        if (isset($_POST[$name])) {
            Av_plugin::set_wizard_data($name.'_valid', $data['status']);
            Av_plugin::set_wizard_data($name,          REQUEST($name));
        }
    }
    echo json_encode($data);
}
