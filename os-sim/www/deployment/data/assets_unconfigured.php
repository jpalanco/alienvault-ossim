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
require_once 'av_init.php';


require_once '../deploy_common.php';


//Checking perms
check_deploy_perms();


/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

$id   = GET('id');


ossim_valid($id,    OSS_HEX,    'illegal:' . _("Network ID"));

if (ossim_error())
{
    $error_msg = "Error: ".ossim_get_error();
    $error     = true;
    ossim_clean_error();
}


$sql    = "SELECT distinct HEX(h.id) as id, h.hostname, MAX(ac.day) as log
                FROM alienvault.host_net_reference hn, alienvault.host h
                LEFT JOIN alienvault_siem.ac_acid_event ac ON ac.src_host = h.id
                WHERE h.id=hn.host_id AND hn.net_id=UNHEX(?) AND h.id NOT IN (Select host_id from host_types)
                GROUP BY h.id
                ";

$params = array($id);


$asset_list= array();

if ($rs = $conn->Execute($sql, $params)) 
{
    while (!$rs->EOF) 
    {
        try
        {
            $ips = Asset_host_ips::get_ips_to_string($conn, $rs->fields['id']);
        }
        catch(Exception $e)
        {
            $ips = '';
        }
        
        $asset_list[] = array(
                            'id'   => $rs->fields['id'],
                            'name' => $rs->fields["hostname"],
                            'ip'   => $ips,
                            'log'  => $rs->fields["log"]
                        );

        $rs->MoveNext();
    }
}   

try
{
    $devices  = new Devices($conn);
    $dev_list = $devices->get_devices();
}
catch(Exception $e)
{
    $dev_list = array();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _("OSSIM Framework"); ?> </title>

    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    
    <!-- JQuery -->
    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
    
    <!-- JQuery TipTip: -->
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
    <script type="text/javascript" src="/ossim/js/jquery.tipTip.js"></script>

    <!-- JQuery DataTables: -->
    <script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>

    <!-- Notification: -->
    <script type="text/javascript" src="/ossim/js/notification.js"></script>

    <!-- Util: -->
    <script type="text/javascript" src="/ossim/js/utils.js"></script>

    <!-- Token: -->
    <script type="text/javascript" src="/ossim/js/token.js"></script>
    
    <script>

        var subtypes = new Array;
        <?php 
        foreach ($dev_list as $d_id => $_device) 
        { 
            echo "subtypes[$d_id] = new Array;";
            
            $subtypes = is_array($_device['subtypes']) ? $_device['subtypes'] : array();
            
            foreach ($subtypes as $s_id => $s_name) 
            { 
                echo 'subtypes['.$d_id.']['.$s_id.'] = "'. $s_name .'";';
            } 
        } 
        ?>

        function fill_device_subtypes(row_id) 
        {

            if (row_id > 0)
            {
                var type_id = $('#device_type_'+row_id).val();

                $('#device_subtype_'+row_id).empty();
                $('#device_subtype_'+row_id).append('<option value=""></option>');
                
                if (typeof subtypes[type_id] != "undefined") 
                {
                    for (var i in subtypes[type_id]) 
                    {
                        $('#device_subtype_'+row_id).append('<option value="'+i+'">'+subtypes[type_id][i]+'</option>');
                    }
                }

                update_device_host(row_id);
            }

       }

       function update_device_host(row_id)
       {

            if (row_id < 1)
            {
                return false;
            }
            
            var id      = $('#host_'+row_id).data('id');
            var type    = $('#device_type_'+row_id).val();
            var subtype = $('#device_subtype_'+row_id).val();

            var ctoken = Token.get_token("deploy_ajax");
            $.ajax({
                data:  {"action": 4, "data": {"id": id, "type": type, "subtype": subtype}},
                type: "POST",
                url: "../deploy_ajax.php?&token="+ctoken, 
                dataType: "json",
                success: function(data)
                { 

                    if (data.error)
                    {
                        notify(data.msg, 'nf_error');
                    } 
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) 
                {
                    notify(data.msg, 'nf_error');
                }
            });

        }
        
        $(document).ready(function(){

            $('.datatable').dataTable( {
                "sScrollY": "400",
				"bLengthChange": false,
                "iDisplayLength": 10,
                "bLengthChange": false,
                "sPaginationType": "full_numbers",
                "bJQueryUI": true,
                "aaSorting": [[ 1, "asc" ]],
                "aoColumns": [
                    { "bSortable": true },
                    { "bSortable": true },
                    { "bSortable": false },
                    { "bSortable": false },
                    { "bSortable": false }
                ],
                oLanguage : {
                    "sProcessing": "<?php echo _('Processing') ?>...",
                    "sLengthMenu": "",
                    "sZeroRecords": "",
                    "sEmptyTable": "<?php echo _('No assets available') ?>",
                    "sLoadingRecords": "<?php echo _('Loading') ?>...",
                    "sInfo": "",
                    "sInfoEmpty": "",
                    "sInfoFiltered": "",
                    "sInfoPostFix": "",
                    "sInfoThousands": ",",
                    "sSearch": "",
                    "sUrl": ""
                }
                
            });
                        
            //TipTip
            $('.tip').tipTip();                        
            
        });
        
    </script>
    
    <style>

        html,body
        {
            background: transparent;
        }

        .l_error, .l_error td, .l_error a
        {
            color: #D8000C; 
            font-weight:bold;
        }

        #av_msg_info {
            top: 0px !important;
        }
        
    </style>
    
</head>

<body>

<?php
if ($error)
{
?>
    <div style='width:100%;margin:0 auto;'>
    
        <?php
        
        $config_nt = array(
            'content' => $error_msg,
            'options' => array (
                'type'          => 'nf_error',
                'cancel_button' => true
            ),
            'style'   => 'width: 45%; margin: 20px auto; text-align: center;'
        ); 
                        
        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
        
        ?>
        
    </div>
    
<?php
    die();
}
?>



    <div id='notification'></div>
    
    <div style='width:85%;margin:0 auto;padding-top:20px;'>
        <table id='dt_1' class='datatable table_list' width='100%' align="center">
            <thead>
                <tr>
                    <th><?php echo _('Hostname') ?></th>
                    <th><?php echo _('Ip') ?></th>
                    <th><?php echo _('Latest Log') ?></th>
                    <th><?php echo _('Device Type') ?></th>
                    <th><?php echo _('Device Subtype') ?></th>
                </tr>
            </thead>
            <tbody>     
            <?php 
            $i = 1;
            
            foreach ($asset_list as $asset) 
            { 
            ?>
                <tr id='host_<?php echo $i?>' data-id='<?php echo $asset['id'] ?>'>
                    <td>
                        <?php echo $asset['name'] ?>
                    </td>
                    <td>
                        <?php echo $asset['ip'] ?>
                    </td>
                    <td class="center">
                        <?php echo (($asset['log']) ? $asset['log'] : "<span class='l_error'>". _('Not received yet') ."</span>" ) ?>
                    </td>
                    <td class="center">
                        <select id="device_type_<?php echo $i ?>" onchange="fill_device_subtypes(<?php echo $i?>);">
                            <option value=""></option>
                            <?php 
                            foreach ($dev_list as $d_id => $_device) 
                            {
                                $dev_name = $_device['name'];
                            ?>
                            
                                <option value="<?php echo $d_id ?>">
                                    <?php echo $dev_name ?>
                                </option>
                                
                            <?php 
                            } 
                            ?>
                        </select>
                    </td>
                    <td class="center">
                        <select id="device_subtype_<?php echo $i ?>" onchange="update_device_host(<?php echo $i?>);" style='min-width:75px;'>
                            <option value=""></option>
                        </select>
                    </td>
                </tr>
           
            <?php
                $i++;
            }
            ?>          
            </tbody>
        </table>
    </div>

</body>
</html>
<?php
$db->close($conn);
?>
