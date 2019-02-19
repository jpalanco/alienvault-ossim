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

Session::logcheck('environment-menu', 'PolicyHosts');


//Validation array

$validate = array(
    'asset_type'    =>  array('validation' => 'OSS_NULLABLE, OSS_LETTER',                               'e_message'  =>  'illegal:' . _('Asset Type')),
    'external'      =>  array('validation' => 'OSS_NULLABLE, OSS_DIGIT',                                'e_message'  =>  'illegal:' . _('External Asset')),
    'descr'         =>  array('validation' => 'OSS_NULLABLE, OSS_ALL',                                  'e_message'  =>  'illegal:' . _('Description')),
    'asset_value'   =>  array('validation' => 'OSS_NULLABLE, OSS_DIGIT',                                'e_message'  =>  'illegal:' . _('Asset value')),
    'sboxs[]'       =>  array('validation' => 'OSS_NULLABLE, OSS_ALPHA, OSS_SCORE, OSS_PUNC, OSS_AT',   'e_message'  =>  'illegal:' . _('Sensors')),
    'os'            =>  array('validation' => 'OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT',                  'e_message'  =>  'illegal:' . _('Operating System')),
    'model'         =>  array('validation' => 'OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT',                  'e_message'  =>  'illegal:' . _('Model')),
    'devices[]'     =>  array('validation' => 'OSS_NULLABLE, OSS_DIGIT, OSS_PUNC',                      'e_message'  =>  'illegal:' . _('Devices')),
    'latitude'      =>  array('validation' => 'OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, OSS_PUNC',           'e_message'  =>  'illegal:' . _('Latitude')),
    'longitude'     =>  array('validation' => 'OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, OSS_PUNC',           'e_message'  =>  'illegal:' . _('Longitude')),
    'zoom'          =>  array('validation' => 'OSS_NULLABLE, OSS_DIGIT',                                'e_message'  =>  'illegal:' . _('Zoom'))
);


/****************************************************
************** Checking field to field **************
*****************************************************/

//Cleaning GET array

if (GET('ajax_validation') == TRUE)
{
    $data['status'] = 'OK';

    $validation_errors = validate_form_fields('GET', $validate);
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }

    echo json_encode($data);

    exit();
}


/****************************************************
**************** Checking all fields ****************
*****************************************************/

//Cleaning POST array

if (isset($_POST['sboxs']) && !empty($_POST['sboxs']))
{
    $_POST['sboxs'] = Util::clean_array(POST('sboxs'));
}

//Checking form token

if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
    if (Token::verify('tk_asset_form', POST('token')) == FALSE)
    {
        $data['status'] = 'error';
        $data['data']   = Token::create_error_message();

        echo json_encode($data);

        exit();
    }
}

$asset_type   = POST('asset_type');
$external     = POST('external');
$descr        = POST('descr');
$asset_value  = POST('asset_value');
$latitude     = POST('latitude');
$longitude    = POST('longitude');
$zoom         = POST('zoom');
$os           = POST('os');
$model        = POST('model');
$sensors      = $_POST['sboxs'];
$devices      = POST('devices');


$validation_errors = validate_form_fields('POST', $validate);

//Extra validations

if (empty($validation_errors))
{
    $db   = new ossim_db();
    $conn = $db->connect();

    //Validating icon format and size

    $icon = '';

    if (is_uploaded_file($_FILES['icon']['tmp_name']))
    {
       $icon = file_get_contents($_FILES['icon']['tmp_name']);
    }

    if ($icon != '')
    {
        $image = @imagecreatefromstring($icon);

        if (!$image || imagesx($image) > 400 || imagesy($image) > 400)
        {
            $validation_errors['icon'] = _('Image format is not allowed');
        }
    }

    //Validating Sensors

    if (is_array($sensors) && !empty($sensors))
    {
        foreach($sensors as $sensor)
        {
            if (!Av_sensor::is_allowed($conn, $sensor))
            {
                $validation_errors['sboxs[]'] .= sprintf(_("Error! Sensor %s cannot be assigned to this asset"), Av_sensor::get_name_by_id($conn, $sensor))."<br/>";
            }
        }
    }

    $db->close();
}


$data['status'] = 'OK';
$data['data']   = $validation_errors;

if (POST('ajax_validation_all') == TRUE)
{
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
    }

    echo json_encode($data);
    exit();
}
else
{
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title><?php echo _('OSSIM Framework');?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache">
        <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?t='.Util::get_css_id(), 'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');
        ?>
    </head>

    <body>
    <?php
    if ($data['status'] != 'error')
    {
        try
        {
            $db   = new ossim_db();
            $conn = $db->connect();

            $asset_data = array(
                'external'    => $external,
                'descr'       => $descr,
                'asset_value' => $asset_value,
                'latitude'    => $latitude,
                'longitude'   => $longitude,
                'zoom'        => $zoom,
                'os'          => $os,
                'model'       => $model,
                'sensors'     => $sensors,
                'devices'     => $devices,
                'icon'        => $icon
            );

            Asset_host::bulk_save_in_db($conn, $asset_data);

            $data['status'] = 'OK';
            $data['data']   = _('Your changes have been saved');

            $db->close();
        }
        catch(Exception $e)
        {
            $data['status'] = 'error';
            $data['data']   = array ('php_exception' => $e->getMessage());
        }
    }


    if ($data['status'] == 'error')
    {
        $txt_error = '<div>'._('The following errors occurred').":</div>
                      <div style='padding: 10px;'>".implode('<br/>', $data['data']).'</div>';

        $config_nt = array(
            'content' => $txt_error,
            'options' => array (
                'type'           => 'nf_error',
                'cancel_button'  => FALSE
            ),
            'style'    =>  'width: 80%; margin: 20px auto; text-align: left;'
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
    }
    else
    {
        ?>
        <script type='text/javascript'>
            var __cfg = <?php echo Asset::get_path_url()?>;

            document.location.href = __cfg.asset.views + 'asset_form.php?edition_type=bulk&asset_type=<?php echo $asset_type?>&msg=saved';

            window.scrollTo(0, 0);
        </script>
        <?php
    }
    ?>
    </body>
</html>
