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



/************************************************************************************/
/************************************************************************************/
/***                                                                              ***/
/***  IF YOU MODIFY THIS FILE, PLEASE CHECK IT WORKS RIGHT IN THE WELCOME WIZARD  ***/
/***                                                                              ***/
/************************************************************************************/
/************************************************************************************/


/**
* Function list:
* - function print_form($msg_errors = '')
* - function clean_iic($string)
* - function import_assets_from_csv($filename, $iic, $ctx, $import_type)
*/

require_once 'av_init.php';

Session::logcheck('environment-menu', 'PolicyHosts');


//Functions
function print_form($import_type)
{
    $config = array(
        'hosts' => array(
            'contexts' => array(
                'show_tree'     => Session::show_entities(),
                'default_value' => Session::get_default_ctx()
            ),
            'chk_iic' => array(
                'show'    => TRUE,
                'checked' => FALSE
            ),
            'help' => array(
                'Version 4.x.x, 5.x.x' => array(
                    'format'  => _('"IPs(IP1,IP2,...)";"Hostname";"FQDNs(FQDN1,FQDN2,...)";"Description";"Asset Value";"Operating System";"Latitude";"Longitude";"Host ID";"External Asset";"Device Types(Type1,Type2,...)"'),
                    'header'  => '"IPs";"Hostname";"FQDNs";"Description";"Asset Value";"Operating System";"Latitude";"Longitude";"Host ID";"External Asset";"Device Type"',
                    'example' => '"192.168.10.3";"Host-1";"www.example-1.es,www.example-2.es";"'._('Short description').'";"2";"Windows";"23.78";"121.45";"379D45C0BBF22B4458BD2F8EE09ECCC2";0;"Server:Mail Server"'
                ),
                'Version 3.x.x' => array(
                    'format'  => _('"IP";"Hostname";"FQDNs(FQDN1,FQDN2,...)";"Description";"Asset Value";"Sensors(Sensor1,Sensor2,...)";"Operating System";"Latitude";"Longitude"'),
                    'header'  => '"IP";"Hostname";"FQDNs";"Description";"Asset Value";"Sensors";"Operating System";"Latitude";"Longitude"',
                    'example' => '"192.168.10.3";"Host-1";"www.example-1.es,www.example-2.es";"'._('Short description').'";"2";"192.168.10.2,192.168.10.3";"Windows";"23.78";"121.45"'
                )
            )
        ),
        'welcome_wizard_hosts' => array(
            'contexts' => array(
                'show_tree'     => FALSE,
                'default_value' => Session::get_default_ctx()
            ),
            'chk_iic' => array(
                'show'    => FALSE,
                'checked' => TRUE
            ),
            'help' => array(
                'Version 4.x.x or higher' => array(
                    'format'  => _('"IPs(IP1,IP2,...)";"Hostname";"Description";"Operating System";"Device Type(Type1,Type2,...)"'),
                    'header'  => _('"IPs";"Hostname";"Description";"Operating System";"Device Type"'),
                    'example' => '"192.168.10.3";"Host-1";"'._('Short description').'";"Windows";"Server,Network Device"'
                )
            )
        )
    );

    $paths = Asset::get_path_url(FALSE);
    $form_action = $paths['asset']['controllers'] . 'import_all_hosts_ajax.php';


    if (!array_key_exists($import_type, $config))
    {
        echo ossim_error(_('Error! Import Type not found'));
    }
    else
    {
        ?>
        <div id='av_info'></div>

        <form name='form_csv' id='form_csv' method='POST' action='<?php echo $form_action ?>' enctype='multipart/form-data' target='iframe_upload'>
            <input type="hidden" name="ctx" id="ctx" value="<?php echo $config[$import_type]['contexts']['default_value']?>"/>
            <input type="hidden" name="import_type" id="import_type" value="<?php echo $import_type?>"/>

            <table id='form_container'>

                <?php
                //Context
                if ($config[$import_type]['contexts']['show_tree'] == TRUE)
                {
                    ?>
                    <tr class='left'>
                        <td class="td_title">
                            <span><?php echo _('Select the Entity for the assets');?>:</span>
                        </td>
                    </tr>

                    <tr>
                        <td class='td_content'>
                            <table id="t_tree">
                                <tr>
                                    <td class='noborder'>
                                        <div id="tree"></div>
                                    </td>
                                </tr>

                                <tr>
                                    <td id='td_es'>
                                        <span class='bold'><?php echo _('Entity selected')?>:</span>
                                        <span id="entity_selected"> - </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php
                }

                //Input File
                ?>
                <tr>
                    <td class='td_title'>
                        <span><?php echo _('Choose a CSV file')?>:</span>
                    </td>
                </tr>

                <tr>
                    <td class='td_content'>
                        <input name='file_csv' id='file_csv' type='file' size='38'/>
                        <?php
                        if ($config[$import_type]['chk_iic']['show'] == TRUE)
                        {
                            $checked_iic = ($config[$import_type]['chk_iic']['checked'] == TRUE) ? 'checked="checked"' : ''

                            ?>
                            <span class='ignore_span'>
                                <input type='checkbox' name='iic' id='iic' <?php echo $checked_iic?> value='1'/>
                                <label for='iic' style='margin-left: 2px;'><?php echo _('Ignore invalid characters (Hostnames)')?></label>
                            </span>
                            <?php
                        }
                        else
                        {
                            $chk_iic_value = ($config[$import_type]['chk_iic']['checked'] == TRUE) ? '1' : '0';
                            ?>
                            <input type='hidden' name='iic' id='iic' value="<?php echo $chk_iic_value;?>"/>
                            <?php
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                        <td class='td_content'>
                            <div id='c_send'>
                                <input type='button' name='send' id='send' value='<?php echo _('Import')?>'/>
                            </div>
                        </td>
                    </tr>

                <tr>
                    <td class='td_title'>
                        <span><?php echo _('Formats allowed')?>:</span>
                    </td>
                </tr>

                <tr>
                    <td class='td_content'>
                        <table id='t_format'>
                            <?php
                            //Help
                            foreach($config[$import_type]['help'] as $version => $help_data)
                            {
                                ?>
                                <tr>
                                    <td class='td_version'>
                                        <?php echo $version?>:
                                    </td>
                                </tr>

                                <tr>
                                    <td class='td_format'>
                                        <strong><?php echo _('Format')?>:</strong>
                                        <?php echo $help_data['format']?>
                                    </td>
                                </tr>

                                <tr>
                                    <td class='td_header'>
                                        <strong><?php echo _('Header')?>:</strong>
                                        <?php echo $help_data['header']?>
                                    </td>
                                </tr>

                                <tr>
                                    <td class='td_example'>
                                        <strong><?php echo _('Example')?>:</strong>
                                       <?php echo $help_data['example']?>
                                    </td>
                                </tr>

                                <tr>
                                    <td></td>
                                </tr>

                                <?php
                            }
                            ?>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td class='td_title'>
                        <span><?php echo _('Notes')?>:</span>
                    </td>
                </tr>

                <tr>
                    <td class='td_content'>
                        <ul id='note_list'>
                            <li><?php echo _('IP address and sensor fields cannot be empty')?></li>
                            <li><?php echo _('Hostname syntax defined by RFC 1123')?></li>
                            <?php
                            if ($import_type == 'hosts')
                            {
                                ?>
                                <li><?php echo _('FQDN syntax defined by RFC 1035, RFC 1123 and RFC 2181')?></li>
                                <li>
                                    <span><?php echo _('Valid Operating System values')?>:</span>
                                    <div style='padding-left: 10px; font-style: italic;'>
                                        Windows, Linux, FreeBSD, NetBSD, OpenBSD, MacOS, Solaris, Cisco, AIX,HP-UX, Tru64, IRIX, BSD/OS, SunOS, Plan9 <?php echo _('or')?> iOS
                                    </div>
                                </li>
                                <?php
                            }
                            else
                            {
                                ?>
                                <li>
                                    <span><?php echo _('Valid Operating System values')?>:</span> <span class='italic'>Windows <?php echo _('or')?> Linux</span>
                                </li>
                                 <li>
                                    <span><?php echo _('Valid Device Types')?>:</span> <span class='italic'>Server <?php echo _('or')?> Network Device</span>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                    </td>
                </tr>
            </table>

            <div id='c_resume'></div>

            <iframe name="iframe_upload" id="iframe_upload" style='display:none;'></iframe>

        </form>
        <?php
    }
}




function clean_iic($string)
{
    $str  = strtr($string, "ٹŒژڑœ‍ں¥µہءآأؤإئابةتثجحخدذرزسشصضطظعغـفكàلâ�
نهوçèéêëىيîïًٌٍَôُِّùْûü‎ے","SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
    $size = strlen($str);

    $val_str = "-".OSS_LETTER.OSS_DIGIT;
    $res     = NULL;

    for($i = 0; $i < $size; $i++)
    {
        if (!preg_match("/[^$val_str]/", $str[$i]))
        {
            $res .= $str[$i];
        }
    }

    return $res;
}


function import_assets_from_csv($filename, $iic, $ctx, $import_type)
{
    //Process status
    $summary = array(
        'general' => array(
            'status'      => '',
            'data'        => '',
            'statistics'  => array(
                'total'    => 0,
                'warnings' => 0,
                'errors'   => 0,
                'saved'    => 0
            )
         ),
        'by_hosts' => array()
    );


    $db   = new ossim_db();
    $conn = $db->connect();

    $str_data = file_get_contents($filename);

    if ($str_data === FALSE)
    {
        $summary['general']['status'] = 'error';
        $summary['general']['data']['errors'] = _('Failed to read data from CSV file');
        $summary['general']['statistics']['errors'] = 1;

        return $summary;
    }


    $array_data = preg_split('/\n|\r/', $str_data);

    foreach ($array_data as $k => $v)
    {
        if (trim($v) != '')
        {
            $data[] = explode('";"', trim($v));
        }
    }


    /*************************************************************************************************************************************
     * From asset section:
     *  - Version 4.x.x or higher: "IP (IP1,IP2,...)";"Hostname";"FQDNs(FQDN1,FQDN2,...)";"Description";"Asset value";"Operating System";
     *                   "Latitude";"Longitude";"Host ID";"External Asset";"Device Types(Type1,Type2,...)"
     *
     *  - Version 3.x.x: "IP"*;"Hostname";"FQDNs(FQDN1,FQDN2,...)";"Description";"Asset value";"Sensors(Sensor1,Sensor2,...)";
     *                   "Operating System";"Latitude";"Longitude"
     *
     * From welcome wizard:
     *  - Version 4.x.x or higher: "IP (IP1,IP2,...)";"Hostname";"Description";"Operating System";"Device Type(Type1,Type2,...)"
     *
    **************************************************************************************************************************************/

    //Check file size
    if (count($data) <= 0 || (count($data) == 1 && preg_match('/IP/',$data[0][0])))
    {
        $summary['general']['status'] = 'error';
        $summary['general']['data']   = _('CSV file is empty');
        $summary['general']['statistics']['errors'] = 1;

        return $summary;
    }


    //Check importation type and headers
    $csv_headers = array();

    if ($import_type == 'hosts')
    {
        if (preg_match('/Operating System/',$data[0][5]) || preg_match('/Sensors/',$data[0][5]))
        {
            $csv_headers = array_shift($data);
        }
        else
        {
            $summary['general']['status'] = 'error';
            $summary['general']['data']   = _('Headers not found');
            $summary['general']['statistics']['errors'] = 1;

            return $summary;
        }
    }


    //Setting total hosts to import
    $summary['general']['statistics']['total'] = count($data);


    //Getting all Operating System
    $all_os = Properties::get_all_os();

    //Getting devices types
    $all_devices  = array();

    $aux_all_devices = Devices::get_all_for_filter($conn);
    $_all_devices    = $aux_all_devices[0];

    foreach($_all_devices as $d_data)
    {
        $d_key  = $d_data['type_name'];
        $d_key .= ($d_data['subtype_id'] != 0) ? ':'.$d_data['subtype_name'] : '';

        $all_devices[$d_key] = $d_data['type_id'].':'.$d_data['subtype_id'];
    }


    //Allowed sensors
    $filters = array(
       'where' => "acl_sensors.entity_id = UNHEX('$ctx')"
    );

    $a_sensors  = Av_sensor::get_basic_list($conn, $filters);
    $sensor_ids = array_keys($a_sensors);


    if (count($sensor_ids) == 0)
    {
        $summary['general']['status'] = 'error';

        $s_error_msg = (Session::is_pro()) ? _('There is no sensors for this context') : _('There is no sensors for this IP address');
        $summary['general']['data'] = $s_error_msg;
        $summary['general']['statistics']['errors'] = 1;

        return $summary;
    }

    Util::disable_perm_triggers($conn, TRUE);

    foreach ($data as $k => $v)
    {
        //Clean previous errors
        ossim_clean_error();

        $num_line = $k + 1;

        //Set default status
        $summary['by_hosts'][$num_line]['status'] = 'error';

        //Check file format
        $cnd_1 = ($import_type == 'hosts' && count($v) < 9);
        $cnd_2 = ($import_type == 'welcome_wizard_hosts' && count($v) < 5);

        if ($cnd_1 || $cnd_2)
        {
            $summary['by_hosts'][$num_line]['errors']['Format'] = _('Number of fields is incorrect');
            $summary['general']['statistics']['errors']++;

            continue;
        }

        //Clean values
        $param = array();

        $index     = 0;
        $max_index = count($v) - 1;

        foreach ($v as $field)
        {
            $parameter = trim($field);

            if ($index == 0)
            {
                $pattern = '/^\"|^\'/';
                $param[] = preg_replace($pattern, '', $parameter);
            }
            else if ($index == $max_index)
            {
                $pattern = '/\"$|\'$/';
                $param[] = preg_replace($pattern, '', $parameter);
            }
            else
            {
                $param[] = $parameter;
            }

            $index++;
        }

        //Values
        $is_in_db = FALSE;
        $host_id  = '';
        $sensors  = $sensor_ids;

        $csv_ips  = preg_replace ("/\s+/", '', $param[0]);

        if(!empty($param[1]))
        {
            $name = $param[1];
        }
        else
        {
            $aux_name = str_replace(' ', '', $csv_ips);
            $aux_name = str_replace(',', '-', $aux_name);
            $name     = Asset_host::get_autodetected_name($aux_name);
        }

        if ($import_type == 'hosts')
        {
            $fqdns       = $param[2];
            $descr       = $param[3];
            $asset_value = (!empty($param[4])) ? $param[4] : 2;

            if (preg_match('/Host ID/', $csv_headers[8]))
            {
                $os          = $param[5];
                $latitude    = floatval($param[6]);
                $longitude   = floatval($param[7]);
                $external    = (empty($param[9])) ? 0 : intval($param[9]);
                $csv_devices = $param[10];
            }
            else
            {
                $os          = $param[6];
                $latitude    = floatval($param[7]);
                $longitude   = floatval($param[8]);
                $external    = 0;
                $csv_devices = '';
            }
        }
        else
        {
            $descr       = $param[2];
            $os          = $param[3];
            $latitude    = 0;
            $longitude   = 0;
            $asset_value = 2;
            $external    = 0;
            $csv_devices = $param[4];
        }

        //Permissions
        $can_i_create_assets = Session::can_i_create_assets();
        $can_i_modify_ips    = TRUE;


        //IPs
        if (!ossim_valid($csv_ips, OSS_IP_ADDR, 'illegal:' . _('IP')))
        {
            $summary['by_hosts'][$num_line]['errors']['IP'] = ossim_get_error_clean();
            $summary['general']['statistics']['errors']++;

            continue;
        }


        //Check Host ID: Is there a host registered in the System?
        $host_ids = Asset_host::get_id_by_ips($conn, $csv_ips, $ctx);
        $host_id  = key($host_ids);

        if (!empty($host_id))
        {
            $is_in_db = TRUE;
        }
        else
        {
            $host_id = Util::uuid();
        }
        // Special case: Forced Host ID [Version 4.x.x or higher]
        if ($import_type == 'hosts' && preg_match('/Host ID/', $csv_headers[8]) && valid_hex32($param[8]))
        {
            $csv_hosts_id = strtoupper($param[8]);

            if ($is_in_db == TRUE && $csv_hosts_id != $host_id)
            {
                $id_error_msg = _('Host is already registered in the System with another Host ID');

                $summary['by_hosts'][$num_line]['errors']['Host'] = $id_error_msg;
                $summary['general']['statistics']['errors']++;

                continue;
            }
            else if ($is_in_db == FALSE)
            {
                $host_id = $csv_hosts_id; // Save host ID to insert it
            }
        }


        //Hostname
        if (!empty($iic))
        {
            $name = clean_iic($name);
        }

        if (!ossim_valid($name, OSS_HOST_NAME, 'illegal:' . _('Hostname')))
        {
            ossim_clean_error();
            $name = Asset_host::create_valid_name($name);

            $warning_msg = _('Hostname does not match with RFC 1123 specifications').'<br/>'._('Hostname will be replaced by').": <strong>$name</strong>";

            $summary['by_hosts'][$num_line]['warnings']['Hostname'] = $warning_msg;
            $summary['by_hosts'][$num_line]['status'] = 'warning';
            $summary['general']['statistics']['warnings']++;


            if (!ossim_valid($name, OSS_HOST_NAME, 'illegal:' . _('Hostname')))
            {
                unset($summary['by_hosts'][$num_line]['warnings']);
                $summary['general']['statistics']['warnings']--;

                $summary['by_hosts'][$num_line]['status'] = 'error';
                $summary['by_hosts'][$num_line]['errors']['Hostname'] = ossim_get_error_clean();
                $summary['general']['statistics']['errors']++;

                continue;
            }
        }

        //Description
        if (!ossim_valid($descr, OSS_NULLABLE, OSS_ALL, 'illegal:' . _('Description')))
        {
            $summary['by_hosts'][$num_line]['errors']['Description'] = ossim_get_error_clean();
            $summary['general']['statistics']['errors']++;

            continue;
        }
        else
        {
            if (mb_detect_encoding($descr.' ','UTF-8,ISO-8859-1') == 'UTF-8')
            {
                 $descr = mb_convert_encoding($descr,'HTML-ENTITIES', 'UTF-8');
            }
        }

        //Operating System

        $os_pattern = '/'.preg_quote(implode('|', $all_os), '/').'/';
        $os_pattern = str_replace('\|', '|', $os_pattern);

        if (!empty($os) && !preg_match($os_pattern, $os))
        {
           $warning_msg = _('Operating System unknown');

           $summary['by_hosts'][$num_line]['warnings']['Operating System'] = $warning_msg;
           $summary['by_hosts'][$num_line]['status'] = 'warning';
           $summary['general']['statistics']['warnings']++;

           $os = 'Unknown';
        }

        //Devices Types
        $devices           = array();
        $unallowed_devices = array();

        if (!empty($csv_devices))
        {
            $aux_devices = explode(',', $csv_devices);

            if (is_array($aux_devices) && !empty($aux_devices))
            {
                foreach($aux_devices as $d_name)
                {
                    $d_name = trim($d_name);

                    if(array_key_exists($d_name, $all_devices))
                    {
                        $devices[] = $all_devices[$d_name];
                    }
                    else
                    {
                        $unallowed_devices[] = $d_name;
                    }
                }

                if (!empty($unallowed_devices))
                {
                    $warning_msg = _('Some devices could not be added (Type and/or subtype unknown)').': '.implode(',', $unallowed_devices);

                    $summary['by_hosts'][$num_line]['warnings']['Devices'] = $warning_msg;
                    $summary['by_hosts'][$num_line]['status'] = 'warning';

                    $summary['general']['statistics']['warnings']++;
                }
            }
        }


        //Sensor
        if ($is_in_db == FALSE)
        {
            //Only update host sensors with unregistered hosts

            if ($import_type == 'hosts' && preg_match('/Sensors/', $csv_headers[5]))
            {
                //Special case: Sensors in CSV file //[Version 3.x.x]
                $sensors  = array();

                $_sensors = explode(',', $param[4]);

                if (is_array($_sensors) && !empty($_sensors))
                {
                    $_sensors = array_flip($_sensors);

                    if (is_array($a_sensors) && !empty($a_sensors))
                    {
                        foreach ($a_sensors as $s_id => $s_data)
                        {
                            if (array_key_exists($s_data['ip'], $_sensors))
                            {
                                $sensors[] = $s_id;
                            }
                        }
                    }
                }

                if (!is_array($sensors) || empty($sensors))
                {
                    $s_error_msg = (Session::is_pro()) ? _('There is no sensors for this context') : _('There is no sensors for this IP address');

                    $summary['by_hosts'][$num_line]['errors']['Sensors'] = $s_error_msg;
                    $summary['general']['statistics']['errors']++;

                    continue;
                }
            }
        }


        /***********************************************************
         ********** Only for importation from host section **********
         ***********************************************************/

        if ($import_type == 'hosts')
        {
            //FQDNs
            if (!ossim_valid($fqdns, OSS_FQDNS, OSS_NULLABLE, 'illegal:' . _('FQDN/Aliases')))
            {
                $summary['by_hosts'][$num_line]['errors']['FQDN/Aliases'] = ossim_get_error_clean();
                $summary['general']['statistics']['errors']++;

                continue;
            }

            //Asset
            if (!ossim_valid($asset_value, OSS_DIGIT, 'illegal:' . _('Asset value')))
            {
                $summary['by_hosts'][$num_line]['errors']['Asset value'] = ossim_get_error_clean();
                $summary['general']['statistics']['errors']++;

                continue;
            }

            //Latitude
            if (!empty($latitude))
            {
                if (!ossim_valid(trim($latitude), OSS_NULLABLE, OSS_DIGIT, OSS_DOT, '\-', 'illegal:' . _('Latitude')))
                {
                    $summary['by_hosts'][$num_line]['errors']['Latitude'] = ossim_get_error_clean();
                    $summary['general']['statistics']['errors']++;

                    continue;
                }
            }

            //Longitude
            if (!empty($longitude))
            {
                if (!ossim_valid(trim($longitude), OSS_NULLABLE, OSS_DIGIT, OSS_DOT, '\-', 'illegal:' . _('Longitude')))
                {
                    $summary['by_hosts'][$num_line]['errors']['Longitude'] = ossim_get_error_clean();
                    $summary['general']['statistics']['errors']++;

                    continue;
                }
            }
        }

        //Insert/Update host in database

        if (count($summary['by_hosts'][$num_line]['errors']) == 0)
        {
            try
            {
                $host = new Asset_host($conn, $host_id);

                if($is_in_db == TRUE)
                {
                    $host->load_from_db($conn, $host_id);

                    $can_i_modify_ips = Asset_host::can_i_modify_ips($conn, $host_id);
                }
                else
                {
                    if ($can_i_create_assets == FALSE)
                    {
                        $n_error_msg = _('Host').' '.$name.' '._("not allowed. You don't have permissions to import this host");

                        $summary['by_hosts'][$num_line]['errors']['Net'] = $n_error_msg;
                        $summary['general']['statistics']['errors']++;

                        continue;
                    }
                }

                //Check IPs

                if ($can_i_modify_ips == TRUE)
                {
                    $aux_ips = explode(',', $csv_ips);

                    foreach ($aux_ips as $ip)
                    {
                        $host_ids = Asset_host::get_id_by_ips($conn, $ip, $ctx);

                        unset($host_ids[$host_id]);

                        if (!empty($host_ids))
                        {
                            $c_error_msg = _('IP').' '.$csv_ips.' '._("not allowed. IP $ip already exists for this entity");

                            $summary['by_hosts'][$num_line]['errors']['IP'] = $c_error_msg;
                            $summary['general']['statistics']['errors']++;

                            break;
                        }
                        else
                        {
                            $cnd_1 = Session::get_net_where() != '' && !Session::only_ff_net();
                            $cnd_2 = Asset_host::is_ip_in_cache_cidr($conn, $ip, $ctx, TRUE);

                            if ($cnd_1 && !$cnd_2)
                            {
                                $c_error_msg = sprintf(_("Error! The IP %s is not allowed. Please check with your account admin for more information"), $csv_ips);

                                $summary['by_hosts'][$num_line]['errors']['IP'] = $c_error_msg;
                                $summary['general']['statistics']['errors']++;

                                break;
                            }
                        }
                    }
                }
                else
                {
                    $c_error_msg = _('Host').' '.$name.': '._("IP address not allowed. IP address cannot be modified");

                    $summary['by_hosts'][$num_line]['status'] = 'warning';
                    $summary['general']['warnings']['errors']++;

                    $summary['by_hosts'][$num_line]['warnings']['IP'] = $c_error_msg;
                }


                //Setting new values

                if (count($summary['by_hosts'][$num_line]['errors']) == 0)
                {
                    $host->set_ctx($ctx);
                    $host->set_name($name);
                    $host->set_descr($descr);

                    if($is_in_db == FALSE)
                    {
                        if ($can_i_modify_ips == TRUE)
                        {
                            if (is_array($aux_ips) && !empty($aux_ips))
                            {
                                $ips = array();

                                foreach ($aux_ips as $ip)
                                {
                                    $ips[$ip] = array(
                                        'ip'   =>  $ip,
                                        'mac'  =>  NULL,
                                    );
                                }

                                $host->set_ips($ips);
                            }
                        }

                        $host->set_sensors($sensors);
                    }

                    if (!empty($fqdns))
                    {
                        $host->set_fqdns($fqdns);
                    }

                    $host->set_external($external);
                    $host->set_location($latitude, $longitude);
                    $host->set_asset_value($asset_value);
                    $host->set_devices($devices);

                    $host->save_in_db($conn, FALSE);

                    //Save Operating System
                    if (!empty($os))
                    {
                        Asset_host_properties::save_property_in_db($conn, $host_id, 3, $os, 2);
                    }


                    $summary['general']['statistics']['saved']++;
                    $summary['by_hosts'][$num_line]['data'] = ($is_in_db == TRUE) ? _('Asset updated') : _('New asset inserted');

                    //Keep warnings
                    if ($summary['by_hosts'][$num_line]['status'] != 'warning')
                    {
                        $summary['by_hosts'][$num_line]['status'] = 'success';
                    }
                }
            }
            catch(Exception $e)
            {
                $summary['by_hosts'][$num_line]['errors']['Database error'] = $e->getMessage();
                $summary['general']['statistics']['errors']++;
            }
        }
    }


    if ($summary['general']['statistics']['saved'] > 0)
    {
        if ($summary['general']['statistics']['errors'] == 0)
        {
            $summary['general']['status'] = 'success';
            $summary['general']['data']   = _('All assets have been successfully imported ');
        }
        else
        {
            $summary['general']['status'] = 'warning';
            $summary['general']['data']   = _('Some assets cannot be imported');
        }

        Util::disable_perm_triggers($conn, FALSE);

        try
        {
            Asset_host::report_changes($conn, 'hosts');
        }
        catch(Exception $e)
        {
            Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
        }
    }
    else
    {
        $summary['general']['statistics']['errors'] = count($data);

        //CSV file is not empty, but all lines are wrong
        if (empty($summary['general']['status']))
        {
            $summary['general']['status'] = 'error';
            $summary['general']['data']   = _('Assets cannot be imported');
        }
    }

    @$conn->Execute("REPLACE INTO alienvault.host_net_reference SELECT host.id,net_id FROM alienvault.host, alienvault.host_ip, alienvault.net_cidrs WHERE host.id = host_ip.host_id AND host_ip.ip >= net_cidrs.begin AND host_ip.ip <= net_cidrs.end");
    $db->close();

    return $summary;
}


/****************************************************
 ******************** Import data *******************
 ****************************************************/


$import_type = REQUEST('import_type');
$import_type = (empty($import_type)) ? 'hosts' : $import_type;

if ($_POST['import_assets'] == 1)
{
    $data['status'] = 'error';
    $data['data']   = NULL;

    $file_csv = $_SESSION['file_csv'];
    unset($_SESSION['file_csv']);

    $iic = POST('iic');
    $ctx = POST('ctx');

    if (Session::is_pro())
    {
        if (!valid_hex32($ctx) || Acl::entityAllowed($ctx) < 1)
        {
            $data['data'] = (empty($ctx)) ? _('You must select an entity') : _('Entity not allowed');

            echo json_encode($data);
            exit();
        }
    }
    else
    {
        $ctx = Session::get_default_ctx();
    }

    if (!empty($file_csv))
    {
        $data['status'] = 'OK';
        $data['data']   = import_assets_from_csv($file_csv, $_POST['iic'], $ctx, $import_type);

        @unlink($file_csv);
    }
    else
    {
         $data['data'] = _('CSV file not found.  Please, choose a CSV file');
    }

    echo json_encode($data);
    exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo _('Import Assets from CSV')?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>

    <?php
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css',             'def_path' => TRUE),
        array('src' => 'tree.css',                  'def_path' => TRUE),
        array('src' => 'tipTip.css',                'def_path' => TRUE),
        array('src' => 'jquery.dataTables.css',     'def_path' => TRUE),
        array('src' => 'jquery-ui-1.7.custom.css',  'def_path' => TRUE)
    );

    Util::print_include_files($_files, 'css');


    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',                 'def_path' => TRUE),
        array('src' => 'jquery-ui.min.js',              'def_path' => TRUE),
        array('src' => 'utils.js',                      'def_path' => TRUE),
        array('src' => 'notification.js',               'def_path' => TRUE),
        array('src' => 'jquery.tipTip.js',              'def_path' => TRUE),
        array('src' => 'messages.php',                  'def_path' => TRUE),
        array('src' => 'av_import_assets.js.php',       'def_path' => TRUE),
        array('src' => 'jquery.tipTip.js',              'def_path' => TRUE),
        array('src' => 'jquery.dataTables.js',          'def_path' => TRUE)
    );


    if (Session::show_entities())
    {
        $_files[] = array('src' => 'jquery.dynatree.js',    'def_path' => TRUE);
    }


    Util::print_include_files($_files, 'js');
    ?>


    <style type="text/css">

        #container
        {
            width: 94%;
            text-align: center;
            margin: 0 auto 20px auto;
            position: relative;
        }

        #form_container
        {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            background: none;
            border: none;
            text-align: center;
        }

        #t_tree
        {
            margin-top: 0px;
            margin-bottom: 10px;
            min-width: 300px;
            background: none;
            border: none;
            text-align: left;
            border: 1px dotted black;
        }

        #t_tree td
        {
            vertical-align: middle !important;
            white-space: nowrap;
        }

        #tree
        {
            width: 100%;
            text-align: left !important;
            padding: 0px;
            margin: 0px;
        }

        #tree .dynatree-container
        {
            margin: 2px;
            border: none;
        }

        #td_es
        {
            text-align: left;

        }

        .td_title
        {
            font-weight: bold;
            text-align: left;
            padding: 15px 0px 5px 0px;
        }

        .td_content
        {
            text-align: left;
            padding-left: 0px;
        }

        #file_csv
        {
            margin: 3px 0px 0px 0px;
        }

        .ignore_span
        {
            margin-left: 15px;
        }

        #t_format
        {
            text-align: left;
            min-width: 600px;
            padding: 5px 3px;
            border: none;
            background: #F2F2F2;
            margin-bottom: 15px;
        }

        .td_version
        {
            padding-top: 5px;
            font-style: normal;
            font-weight: bold;
            font-sixe: 12px;
        }

        .td_format, .td_header, .td_example
        {
            padding: 5px 0px 0px 15px;
            font-style: italic;
        }

        .td_example
        {
            font-size: 10px;
        }

        #note_list
        {
            margin: 0px 0px 0px 25px;
            text-align: left;
        }

        #note_list li
        {
            padding: 2px;
        }

        #c_send
        {
            padding: 10px 0px 15px 0px;
            text-align: left;
        }

        #av_info > div
        {
            margin: 20px auto 0px auto;
            width: 90%;
        }

        /* Summary */

        #sm_container
        {
            width: 90%;
            text-align: center;
            margin: auto;
            position: relative;
            display: none;
        }

        .error
        {
            color: #D8000C !important;
        }

        .warning
        {
            color: #9F6000 !important;
        }

        .success
        {
            color: #4F8A10 !important;
        }

        #c_sm_statistics
        {
            width: 100%;
            margin: auto;
        }

        #t_sm_statistics
        {
            width: 100%;
            border-collapse: collapse;
        }

        #t_sm_statistics tbody td
        {
            font-size: 12px;
        }

        #t_sm_statistics thead th
        {
            height: 20px;
        }

        #c_sm_container
        {
            width: 100%;
            margin: 30px auto 0px auto;
        }

        #t_sm_container
        {
            width: 100%;
            border-collapse: collapse;
        }

        #t_sm_container th_details
        {
            width: 40px;
        }

        #t_sm_container .td_details
        {
            height: auto;
        }

        #t_sm_container .td_details img
        {
            cursor: pointer;
        }

        .sm_wizard
        {
            margin-top: 30px;
        }

        .sm_back_button
        {
            margin-top: 20px;
        }

        .dataTables_wrapper .dt_header div.dt_title
        {
            top:6px;
            left: 0px;
            right: 0px;
            margin: auto;
            text-align: center;
        }

        .details_info
        {
            display:none;
        }

        .asset_details_w, .asset_details_w:hover
        {
           color: #9F6000 !important;
           background-color: #FEEFB3 !important;
        }

        .table_data  > tbody > tr:hover > td.asset_details_w
        {
            background-color: #FEEFB3 !important;
        }

        .asset_details_e, .asset_details_e:hover
        {
            background: #FFBABA !important;
            color: #D8000C !important;
        }

        .table_data  > tbody > tr:hover > td.asset_details_e
        {
            background: #FFBABA !important;
        }

        .tray_container
        {
            border: 0px;
            background-color: inherit;
            position:relative;
            height:100%;
            margin: 2px 5px;
        }

        .tray_triangle
        {
            position: absolute;
            z-index: 99999999;
            top: -17px;
            left: 20px;
            width:0;
            height:0;
            border-color: transparent transparent #FFBABA transparent;
            border-style: solid;
            border-width: 7px;
        }

        .tt_error
        {
            border-color: transparent transparent #FFBABA transparent;
        }

        .tt_warning
        {
            border-color: transparent transparent #FEEFB3 transparent;
        }

        .tray_container ul
        {
            text-align: left;
            padding: 15px 20px;
        }

        .tray_container ul li
        {
            text-align: left;
            list-style-type: square;
            color: inherit;
        }

        #summary_info
        {
            margin: 20px auto 0px auto;
            width: 90%;
        }

        #c_new_importation
        {
            text-align: center;
            margin: auto;
            padding: 20px 0px;
        }

    </style>

    <script type='text/javascript'>

        $(document).ready(function(){

            //Setting all handlers
            bind_import_actions();
        });
    </script>
</head>

<body>

    <div id='container'>
        <?php
        print_form($import_type);
        ?>
    </div>

    <div id='sm_container'></div>

</body>
</html>
