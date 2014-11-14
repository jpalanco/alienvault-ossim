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

require_once ('av_init.php');

Session::logcheck("configuration-menu", "CorrelationDirectives");

$sections = array(
	"1" => _("Rule name"),
	"2" => _("Plugin"),
	"3" => _("Event Type"),
	"5" => _("Network"),
	"6" => _("Reliability"),
	"7" => _("Protocol"),
	"8" => _("Sensor"),
	"9" => _("Occurrence"),
	"10" => _("Timeout"),
	"11" =>_("Monitor Value"),
	"12" =>_("Monitor Interval"),
	"13" =>_("Monitor Absolute"),
	"14" =>_("Sticky"),
	"15" =>_("Sticky Dif"),
	"16" =>_("Other"),
	"17" =>_("Userdata")
);

// Secondary validation
$error     = FALSE;
$error_msg = array();

$db = new ossim_db();
$conn = $db->connect();

$id = GET("id");
$directive_id = GET("directive_id");
$xml_file = GET('xml_file');
$level = (GET('level') != "") ? GET('level') : 1;
$engine_id = GET('engine_id');
$reloadindex = GET('reloadindex');
$from_directive = (GET('from_directive') != "") ? true : false;
$directive_name = GET('directive_name');
$directive_prio = GET('directive_prio');
$directive_intent = GET('directive_intent');
$directive_strategy = GET('directive_strategy');
$directive_method = GET('directive_method');
ossim_valid($id, OSS_DIGIT, '\-', OSS_NULLABLE, 'illegal:' . _("rule ID"));
ossim_valid($directive_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("directive ID"));
ossim_valid($xml_file, OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("xml_file"));
ossim_valid($level, OSS_DIGIT, 'illegal:' . _("Level"));
ossim_valid($engine_id, OSS_HEX, OSS_SCORE, 'illegal:' . _("Engine ID"));
ossim_valid($reloadindex, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Reload Index"));
ossim_valid($directive_name, OSS_DIRECTIVE_NAME, OSS_NULLABLE, 'illegal:' . _("Directive Name"));
ossim_valid($directive_prio, OSS_DIGIT, OSS_NULLABLE,          'illegal:' . _("New Priority"));
ossim_valid($directive_intent, OSS_DIGIT, OSS_NULLABLE,          'illegal:' . _("New Intent"));
ossim_valid($directive_strategy, OSS_DIGIT, OSS_NULLABLE,          'illegal:' . _("New Strategy"));
ossim_valid($directive_method, OSS_TEXT, OSS_NULLABLE,          'illegal:' . _("New Method"));
if (ossim_error()) {
    die(ossim_error());
}

// Save rule
if (POST('name') != "") {
    // Do not allow all ANY in source type
	if (POST('plugin_id') == "" && POST('product_list') == "" && POST('category') < 1 && POST('plugin_sid_list') == "") {
		die(ossim_error(_("You cannot save this rule. No event source type defined")));
	}
	
	// For taxonomy option, always detector type
	if (POST('type') == "") $_POST["type"] = "detector";
	
	if (POST("plugin_sid") == "LIST") $_POST["plugin_sid"] = POST("plugin_sid_list");
	if (POST("entity") == "LIST") $_POST["entity"] = POST("entity_list");
	if (POST("product") == "LIST") $_POST["product"] = POST("product_list");
	
	// Force assets when user perms, cannot be ANY
	if ((Session::get_host_where() != "" || Session:: get_net_where() != "") && (POST('from') == "ANY" || POST('from_list') == "")) {
		$_POST["from"] = "LIST";
		$assets_aux    = array();
		
		$_list_data = Asset_host::get_basic_list($conn);
		$_host_aux  = array_keys($_list_data[1]);
		foreach ($_host_aux as $h_id)
		{
			$assets_aux[] = Util::uuid_format($h_id);
		}
		
		$_list_data = Asset_net::get_list($conn);
		$_net_aux   = array_keys($_list_data[0]);
		foreach ($_net_aux as $n_id)
		{
			$assets_aux[] = Util::uuid_format($n_id);
		}
		
		$_POST["from_list"] = implode(",", $assets_aux);
	}
	if ((Session::get_host_where() != "" || Session:: get_net_where() != "") && (POST('to') == "ANY" || POST('to_list') == "")) {
		$_POST["to"] = "LIST";
		$assets_aux  = array();
	    
		$_list_data = Asset_host::get_basic_list($conn);
		$_host_aux  = array_keys($_list_data[1]);
		foreach ($_host_aux as $h_id)
		{
			$assets_aux[] = Util::uuid_format($h_id);
		}
		
		$_list_data = Asset_net::get_list($conn);
		$_net_aux   = array_keys($_list_data[0]);
		foreach ($_net_aux as $n_id)
		{
			$assets_aux[] = Util::uuid_format($n_id);
		}
		
		$_POST["to_list"] = implode(",", $assets_aux);
	}
	
    if (POST("from") == "LIST") $_POST["from"] = POST("from_list");
    if (POST("port_from") == "LIST") $_POST["port_from"] = POST("port_from_list");
    if (POST("to") == "LIST") $_POST["to"] = POST("to_list");
    if (POST("port_to") == "LIST") $_POST["port_to"] = POST("port_to_list");
    if (POST("protocol_any")) {
        $protocol = "ANY";
    } else {
        $protocol = "";
        if (POST("protocol_tcp")) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "TCP";
        }
        if (POST("protocol_udp")) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "UDP";
        }
        if (POST("protocol_icmp")) {
            if ($protocol != "") $protocol.= ",";
            $protocol.= "ICMP";
        }
        for ($i = 1; isset($_POST['protocol_' . $i]); $i++) {
            if ($protocol != '') $protocol.= ',';
            $protocol.= $i . ':PROTOCOL';
        }
    }
    if (POST("reliability_op") == "+") $_POST["reliability"] = "+" . POST("reliability");
    if (POST("sensor") == "LIST") $_POST["sensor"] = POST("sensor_list");
    if (POST("occurrence") == "LIST") $_POST["occurrence"] = POST("occurrence_list");
    if (POST("time_out") == "LIST") $_POST["time_out"] = POST("time_out_list");
    
    ossim_valid(POST("xml_file"), OSS_FILENAME, 'illegal:' . _("xml file"));
    ossim_valid(POST("plugin_sid"), OSS_PLUGIN_SID, '_', '!', OSS_NULLABLE, 'illegal:' . _("plugin sid"));
    ossim_valid(POST("plugin_sid_list"), OSS_PLUGIN_SID_LIST, OSS_NULLABLE, 'illegal:' . _("plugin sid list"));
    ossim_valid(POST("product"), OSS_ALPHA, OSS_NULLABLE, ',', 'illegal:' . _("Product Type"));
    ossim_valid(POST("product_list"), OSS_DIGIT, OSS_NULLABLE, ',', 'illegal:' . _("Product Type"));
    ossim_valid(POST("category"), OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Category"));
    ossim_valid(POST("subcategory"), OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Subcategory"));
    ossim_valid(POST("entity"), OSS_FROM, OSS_NULLABLE, 'illegal:' . _("entity"));
    ossim_valid(POST("entity_list"), OSS_HEX, ',', '-', OSS_NULLABLE, 'illegal:' . _("entity list"));
    ossim_valid(POST("from"), OSS_FROM, OSS_NULLABLE, '-\/', 'illegal:' . _("from"));
    ossim_valid(POST("from_list"), OSS_FROM, OSS_NULLABLE, '-\/' ,'illegal:' . _("from list"));
    ossim_valid(POST("port_from"), OSS_PORT_FROM, OSS_NULLABLE, 'illegal:' . _("port from"));
    ossim_valid(POST("port_from_list"), OSS_PORT_FROM_LIST, OSS_NULLABLE, 'illegal:' . _("port from list"));
    ossim_valid(POST("to"), OSS_TO, OSS_NULLABLE, '-\/', 'illegal:' . _("to"));
    ossim_valid(POST("to_list"), OSS_TO, OSS_NULLABLE, '-\/', 'illegal:' . _("to list"));
    ossim_valid(POST("port_to"), OSS_PORT_TO, OSS_NULLABLE, 'illegal:' . _("port to"));
    ossim_valid(POST("port_to_list"), OSS_PORT_TO_LIST, OSS_NULLABLE, 'illegal:' . _("port from list"));
    ossim_valid(POST("from_rep"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation from"));
    ossim_valid(POST("to_rep"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation to"));
    ossim_valid(POST("from_rep_min_pri"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation from min priority"));
    ossim_valid(POST("to_rep_min_pri"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation to min priority"));
    ossim_valid(POST("from_rep_min_rel"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation from min reliability"));
    ossim_valid(POST("to_rep_min_rel"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation to min reliability"));
    ossim_valid(POST("sensor_list"), OSS_HEX, ',', '-', OSS_NULLABLE, 'illegal:' . _("sensor list"));
    ossim_valid(POST("sensor"), OSS_SENSOR, OSS_NULLABLE, 'illegal:' . _("sensor"));
    ossim_valid(POST("occurrence_list"), OSS_NULLABLE, OSS_DIGIT , 'illegal:' . _("occurrence list"));
    ossim_valid(POST("occurrence"), OSS_NULLABLE, OSS_LETTER , OSS_DIGIT, 'illegal:' . _("occurrence"));
    ossim_valid(POST("time_out_list"), OSS_NULLABLE, OSS_DIGIT, OSS_LETTER , 'illegal:' . _("time out list"));
    ossim_valid(POST("time_out"), OSS_NULLABLE, OSS_DIGIT, OSS_LETTER , 'illegal:' . _("time out"));
    ossim_valid(POST("reliability"), OSS_DIGIT, '\+\=' , 'illegal:' . _("reliability"));
    ossim_valid(POST("reliability_op"), OSS_NULLABLE, '\+\=' , 'illegal:' . _("reliabilty op"));
    ossim_valid(POST("protocol_any"), OSS_NULLABLE, OSS_ALPHA, 'illegal:' . _("protocol any"));
    ossim_valid(POST("protocol_tcp"), OSS_NULLABLE, OSS_ALPHA, 'illegal:' . _("protocol tcp"));
    ossim_valid(POST("protocol_udp"), OSS_NULLABLE, OSS_ALPHA, 'illegal:' . _("protocol udp"));
    ossim_valid(POST("protocol_icmp"), OSS_NULLABLE, OSS_ALPHA, 'illegal:' . _("protocol icmp"));
    ossim_valid(POST('id'), OSS_DIGIT, '\-', 'illegal:' . _("rule ID"));
    ossim_valid(POST('directive_id'), OSS_DIGIT, 'illegal:' . _("directive ID"));
    ossim_valid(POST('level'), OSS_DIGIT, OSS_NULLABLE, '\-', 'illegal:' . _("level"));
    ossim_valid(POST('name'), OSS_NULLABLE, OSS_RULE_NAME, 'illegal:' . _("rule name"));
    ossim_valid(POST('plugin_id'), OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("plugin ID"));
    ossim_valid(POST('type'), OSS_LETTER, OSS_NULLABLE, 'illegal:' . _("type"));
    ossim_valid(POST('condition'), OSS_NULLABLE, OSS_LETTER, OSS_SPACE, 'illegal:' . _("condition"));
    ossim_valid(POST('value'), OSS_NULLABLE, OSS_DIGIT, OSS_LETTER, 'illegal:' . _("value"));
    ossim_valid(POST('interval'), OSS_NULLABLE, OSS_DIGIT, OSS_LETTER, 'illegal:' . _("interval"));
    ossim_valid(POST('absolute'), OSS_LETTER, OSS_NULLABLE, 'illegal:' . _("absolute"));
    ossim_valid(POST('sticky'), OSS_LETTER, OSS_NULLABLE, 'illegal:' . _("sticky"));
    ossim_valid(POST('sticky_different'), OSS_LETTER, OSS_NULLABLE, OSS_SCORE, 'illegal:' . _("sticky different"));
    ossim_valid(POST('filename'), OSS_NULLABLE, OSS_ALPHA, OSS_SLASH, OSS_DIGIT, OSS_DOT, OSS_COLON, '\!,', 'illegal:' . _("file name"));
    ossim_valid(POST('username'), OSS_NULLABLE, OSS_USER, OSS_PUNC_EXT, 'illegal:' . _("user name"));
    ossim_valid(POST('password'), OSS_NULLABLE, OSS_PASSWORD, 'illegal:' . _("password"));
    ossim_valid(POST('userdata1'), OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("userdata1"));
    ossim_valid(POST('userdata2'), OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("userdata2"));
    ossim_valid(POST('userdata3'), OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("userdata3"));
    ossim_valid(POST('userdata4'), OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("userdata4"));
    ossim_valid(POST('userdata5'), OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("userdata5"));
    ossim_valid(POST('userdata6'), OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("userdata6"));
    ossim_valid(POST('userdata7'), OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("userdata7"));
    ossim_valid(POST('userdata8'), OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("userdata8"));
    ossim_valid(POST('userdata9'), OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("userdata9"));
    
    ossim_valid(POST('directive_name'), OSS_DIRECTIVE_NAME, OSS_NULLABLE, 'illegal:' . _("Directive Name"));
	ossim_valid(POST('directive_prio'), OSS_DIGIT, OSS_NULLABLE,          'illegal:' . _("New Priority"));
    
    if (ossim_error()) {
        die(ossim_error());
    }
    
    // Secondary validation
    if (!Directive_editor::valid_directive_port(POST("port_from")) || !Directive_editor::valid_directive_port(POST("port_from_list")))
    {
        $error       = TRUE;
        $error_msg[] = _('Invalid source port value');
    }
    
    if (!Directive_editor::valid_directive_port(POST("port_to")) || !Directive_editor::valid_directive_port(POST("port_to_list")))
    {
        $error       = TRUE;
        $error_msg[] = _('Invalid destination port value');
    }
    
    if ($error)
    {
        die(ossim_error(implode('<br>', $error_msg)));
    }
    
    $directive_editor = new Directive_editor($engine_id);
    $attributes = array(
    		"name"             => stripslashes(POST("name")),
    		"plugin_id"        => POST("plugin_id"),
    		"type"             => POST("type"),
    		"plugin_sid"       => POST("plugin_sid"),
    		"product"          => POST("product"),
    		"category"         => POST("category"),
    		"subcategory"      => POST("subcategory"),
    		"entity"           => POST("entity"),
    		"from"             => POST("from"),
    		"port_from"        => POST("port_from"),
    		"to"               => POST("to"),
    		"port_to"          => POST("port_to"),
    		"from_rep"         => POST("from_rep"),
    		"to_rep"           => POST("to_rep"),
    		"from_rep_min_pri" => POST("from_rep_min_pri"),
    		"to_rep_min_pri"   => POST("to_rep_min_pri"),
    		"from_rep_min_rel" => POST("from_rep_min_rel"),
    		"to_rep_min_rel"   => POST("to_rep_min_rel"),
    		"protocol"         => $protocol,
    		"sensor"           => POST("sensor"),
    		"occurrence"       => POST("occurrence"),
    		"time_out"         => POST("time_out"),
    		"reliability"      => POST("reliability"),
    		"condition"        => POST("condition"),
    		"value"            => POST("value"),
    		"interval"         => POST("interval"),
    		"absolute"         => POST("absolute"),
    		"sticky"           => POST("sticky"),
    		"sticky_different" => POST("sticky_different"),
    		"userdata1"        => utf8_encode(stripslashes(POST("userdata1"))),
    		"userdata2"        => utf8_encode(stripslashes(POST("userdata2"))),
    		"userdata3"        => utf8_encode(stripslashes(POST("userdata3"))),
    		"userdata4"        => utf8_encode(stripslashes(POST("userdata4"))),
    		"userdata5"        => utf8_encode(stripslashes(POST("userdata5"))),
    		"userdata6"        => utf8_encode(stripslashes(POST("userdata6"))),
    		"userdata7"        => utf8_encode(stripslashes(POST("userdata7"))),
    		"userdata8"        => utf8_encode(stripslashes(POST("userdata8"))),
    		"userdata9"        => utf8_encode(stripslashes(POST("userdata9"))),
    		"filename"         => POST("filename"),
    		"username"         => utf8_encode(POST("username")),
    		"password"         => POST("password")
    );
    $rule = new Directive_rule(POST('id'), POST('level'), "", $attributes);
    $file = $directive_editor->engine_path."/".POST('xml_file');
	$directive_error = false;
	
	if (POST('from_directive') != "") {
		$dom  = $directive_editor->get_xml($file, "DOMXML");
		$node = $dom->createElement('directive');
	    $node->setAttribute('id', POST('directive_id'));
	    $node->setAttribute('name', POST('directive_name'));
	    $node->setAttribute('priority', POST('directive_prio'));
	    $dom->appendChild($node);
		if (!$directive_editor->save_xml($file, $dom, "DOMXML", false)) { // DTD Validation = false
			$directive_error = true;
		} else {
			$directive_editor->update_directive_pluginsid(POST('directive_id'), 2, POST('directive_prio'), POST('directive_name'));
			$directive_editor->update_directive_taxonomy(POST('directive_id'), POST('directive_intent'), POST('directive_strategy'), POST('directive_method'));
		}
    }
    
    if (!$directive_error) {
	    $directive_editor->insert($rule, POST("directive_id"), $file);
        ?>
        <script type="text/javascript">
            var params          = new Array();
            params['xml']       = "<?php echo $xml_file ?>";
            params['directive'] = "<?php echo POST('directive_id') ?>";


            <?php
    	    if (POST('reloadindex') != "") 
            {
    	    ?>
                params['reload'] = true;
            <?php
    	    }
            else 
            {
    	    ?>
                params['reload'] = false;
                
            <?php
    	    }
        ?>
            parent.GB_hide(params);

        </script>
        <?php
    }
    
    exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>" />
<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css" />
<link type="text/css" rel="stylesheet" href="../style/ui.multiselect.css" />
<link type="text/css" rel="stylesheet" href="../style/tree.css" />
<style>
           
	/*Multiselect loading styles*/        
	#ms_body {height: 297px;}
	#load_ms {
		margin:auto; 
		padding-top: 105px; 
		text-align:center;
	}
</style>
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
<script type="text/javascript" src="../js/ui.multiselect_search.js"></script>
<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
<script type="text/javascript" src="../js/notification.js"></script>
<script type="text/javascript" src="../js/combos.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	load_categories();
});

function valid_input(input) {
	if (input == "name") {
		if ($('#name').val() != "" && $('#name').val().match(/^[a-zA-Z\s\_\-0-9\.\,\:]+$/)) {
			return true;
		} else {
			notify('<?php echo _("You must type a correct rule name") ?>', 'nf_error');
			return false;
		}
	}
	return false;
}

function save_all() {
	save_ptypes();
	save_sids();
	<?php if (Session::is_pro()) { ?>/*save_entity();*/<?php } ?>
	save_network();
	if (save_sensor())
		return true;
	else
		return false;
}

// **************** Step 1 functions *****************
/* Get the current event object. */
function getEvent(event) {
	
	if (window.event) return window.event.keyCode;
	if (event) return event.which;
}

/* Call the "onChange()" handler if "Enter" is pressed. */
function onKeyEnterWizard(elt, evt) {

	if (getEvent(evt) == 13) {
		if (typeof(wizard_next) == "function") {
			if (valid_input('name')) {
				wizard_next();
			} else {
				return false;
			}
		}
	}
}

// *************** Step 2 functions ******************
// Plugin search
function search_plugin(q) {
	var str = "";
	var _regex = new RegExp( "^" + q, "i");
	$('.plugin_line').each(function() {
		val = $(this).attr("id");
		aux = val.split(";");
		pid = aux[0];
		pname = aux[1];
		visible = false;
		if (q.match(/^\d+$/)) {
			if (pid == q) visible = true;
		} else {
			if (pname.match(_regex)) {
				visible = true;
			}
		}
		if (!visible) {
			str += pname;
			document.getElementById(val).style.display='none';
		} else {
			document.getElementById(val).style.display='block';
		}
	});
	//alert(str);
}
// Plugin mode toggle
function change_event_type(type, step) {
	if (type == 0) {
		$('#txn_'+step).hide();
		$('#dsg_'+step).show();
	} else {
		$('#dsg_'+step).hide();
		$('#txn_'+step).show();	
	}
}
// Taxonomy product types multiselect
function init_ptypes() {
	$(".multiselect_product").multiselect({
		searchable: false,
		dividerLocation: 0.5,
		nodeComparator: function (node1,node2){ return 1 }
	});
}
function save_ptypes() {
	var product_list = getselectedcombovalue('productselect');
	if (product_list != "") {
			document.getElementById('product').value = "LIST";
			document.getElementById('product_list').value = product_list;
	} else {
			document.getElementById('product').value = "ANY";
			document.getElementById('product_list').value = "";
	}
}

//*************** Step 3 functions ******************
function init_sids(id,m,product_type) {
	is_monitor = m;
	//alert("plugin_sid_ajax.php?plugin_id="+id+"&product_type="+product_type);
	$.ajax({
		data: {plugin_id: id, product_type: product_type },
		type: "GET",
		url: "plugin_sid_ajax.php", 
		dataType: "json",
		async: false,
		success: function(data){
			if(!data.error){	
				$('#ms_body').html('');
				$('#ms_body').html(data.data);
				$('#msg').html(data.message);
			}
		}
    });
	$("#pluginsids").multiselect({
		searchDelay: 700,
		searchable: true,
		sortable: 'both',
		dividerLocation: 0.5,
		remoteUrl: 'plugin_sid.php',
		remoteParams: { plugin_id: id, product_type: product_type },
		nodeComparator: function (node1,node2){ return 1 },
		dataParser: customDataParser,
		sizelength: 73
	});

	// Force to Taxonomy
	if (typeof product_type != "undefined" && product_type != "") {
		$('#plug_type_sid_tax').attr("checked", true);
		$('.plug_type_sid').attr("disabled", true);
		change_event_type(1, "sid");
	} else {
		$('.plug_type_sid').attr("disabled", false);
	}
}
function save_sids() {
	var current_sid = document.getElementById('plugin_sid').value;
	if (!current_sid.match(/\d\:PLUGIN\_SID/)) {
			var plugin_sid_list = getselectedcombovalue('pluginsids');
			if (plugin_sid_list != "") {
					document.getElementById('plugin_sid').value = plugin_sid_list;
					document.getElementById('plugin_sid_list').value = plugin_sid_list;
			} else {
					document.getElementById('plugin_sid').value = "ANY";
					document.getElementById('plugin_sid_list').value = "";
			}
	}
}
var customDataParser = function(data) {
	if ( typeof data == 'string' ) {
		var pattern = /^(\s\n\r\t)*\+?$/;
		var selected, line, lines = data.split(/\n/);
		data = {};
		$('#msg').html('');
		for (var i in lines) {
			line = lines[i].split("=");
			if (!pattern.test(line[0])) {
				if (i==0 && line[0]=='Total') {
					$('#msg').html("<?php echo _("Total plugin sids found:")?> <b>"+line[1]+"</b>");
				} else {
					// make sure the key is not empty
					selected = (line[0].lastIndexOf('+') == line.length - 1);
					if (selected) line[0] = line.substr(0,line.length-1);
					// if no value is specified, default to the key value
					data[line[0]] = {
						selected: false,
						value: line[1] || line[0]
					};
				}
			}
		}
	} else {
		this._messages($.ui.multiselect.constante.MESSAGE_ERROR, $.ui.multiselect.locale.errorDataFormat);
		data = false;
	}
	return data;
};
function load_categories() {
	$.ajax({
		data:  {"action": 5, "data":  {"ctx": "<?php echo Session::get_default_ctx() ?>"}},
		type: "POST",
		url: "../policy/policy_ajax.php", 
		dataType: "json",
		async: false,
		success: function(data){ 
				if(!data.error){
					if(data.data != ''){								
						$('#category').html(data.data);
					}
				} 
			}
	});
	return true;
}
function load_subcategories(cat_id){
	$.ajax({
		data:  {"action": 6, "data":  {"id": cat_id, "ctx": "<?php echo Session::get_default_ctx() ?>"}},
		type: "POST",
		url: "../policy/policy_ajax.php", 
		dataType: "json",
		async: false,
		success: function(data){ 
				if(!data.error){
					if(data.data != ''){								
						$('#subcategory').html(data.data);
					}
				} 
			}
	});
	
	return true;
}

// ***************** Step 5 Functions *******************
function init_network() {
	load_tree();
}

var layer_i = null;
var nodetree_i = null;
var i=1;
var layer_j = null;
var nodetree_j = null;
var j=1;
function load_tree(filter)
{
	var e_filter = ""; // Maybe entity filter
	var combo2 = "toselect";
	var suf2 = "to";
	if (nodetree_j!=null) {
			nodetree_j.removeChildren();
			$(layer_j).remove();
	}
	layer_j = '#dsttree'+j;
	$('#container'+suf2).append('<div id="dsttree'+j+'" style="width:100%"></div>');

	$(layer_j).dynatree({
			initAjax: { url: "../tree.php?key="+e_filter+"assets", data: {filter: filter} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
					if (dtnode.data.key.match(/net_/) || dtnode.data.key.match(/host_/)) {
						var k = dtnode.data.key.replace("net_","");
						k = k.replace("host_","");
						key_can = k.replace(/(........)(....)(....)(....)(............)/, "$1-$2-$3-$4-$5");
						addto(combo2,dtnode.data.val,key_can.toLowerCase());
					}
			},
			onDeactivate: function(dtnode) {},
			onLazyRead: function(dtnode){
					dtnode.appendAjax({
							url: "../tree.php",
							data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
					});
			}
	});
	nodetree_j = $(layer_j).dynatree("getRoot");
	j=j+1;
	
	var combo1 = "fromselect";
	var suf1 = "from";
	if (nodetree_i!=null) {
			nodetree_i.removeChildren();
			$(layer_i).remove();
	}
	layer_i = '#srctree'+i;
	$('#container'+suf1).append('<div id="srctree'+i+'" style="width:100%"></div>');
	$(layer_i).dynatree({
			initAjax: { url: "../tree.php?key="+e_filter+"assets", data: {filter: filter} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
				if (dtnode.data.key.match(/net_/) || dtnode.data.key.match(/host_/)) {
					var k = dtnode.data.key.replace("net_","");
					k = k.replace("host_","");
					key_can = k.replace(/(........)(....)(....)(....)(............)/, "$1-$2-$3-$4-$5");
					addto(combo1,dtnode.data.val,key_can.toLowerCase());
				}
			},
			onDeactivate: function(dtnode) {},
			onLazyRead: function(dtnode){
					dtnode.appendAjax({
							url: "../tree.php",
							data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
					});
			}
	});
	nodetree_i = $(layer_i).dynatree("getRoot");
	i=i+1;
}

function save_network() {
	selectall('fromselect');
	selectall('toselect');
	var from_list = getselectedcombovalue('fromselect');
	var to_list = getselectedcombovalue('toselect');
	var port_from_list = document.getElementById('port_from_list').value;
	var port_to_list = document.getElementById('port_to_list').value;
	if (from_list != "") {
			document.getElementById('from').value = "LIST";
			document.getElementById('from_list').value = from_list;
	} else {
			document.getElementById('from').value = "ANY";
			document.getElementById('from_list').value = "";
	}
	if (to_list != "") {
			document.getElementById('to').value = "LIST";
			document.getElementById('to_list').value = to_list;
	} else {
			document.getElementById('to').value = "ANY";
			document.getElementById('to_list').value = "";
	}
	if (port_from_list != "" && port_from_list != "ANY") document.getElementById('port_from').value = "LIST";
	if (port_to_list != "" && port_to_list != "ANY") document.getElementById('port_to').value = "LIST"; 
}

//***************** Step 7 Functions *******************
function init_sensor() {
	$(".multiselect_sensor").multiselect({
		searchDelay: 700,
		dividerLocation: 0.5,
		nodeComparator: function (node1,node2){ return 1 }
	});
}
function save_sensor() {
	var sensor_list = getselectedcombovalue('sensorselect');
	var ret = true;
	//var entities = get_entities_string();
	var entities = "";
	var entities_arr = entities.split(",");
	$("#sensorselect option:selected").each(function(){
	   	sensor_allowed = false;
	   	for (var i in entities_arr) {
			if ($(this).attr('ctx').match(entities_arr[i])) { sensor_allowed = true; }
		}
		if (!sensor_allowed) {
			$('#sensor_msg').html("Sensor "+$(this).text()+" is not allowed for the selected entities");
			wizard_goto(7);
			ret = false;
		}
	});
	
	// from parent rule
	if (document.getElementById('sensor').value.match(/SENSOR/)) {
		document.getElementById('sensor_list').value = "";
	// from select list
	} else if (sensor_list != "") {
		document.getElementById('sensor').value = "LIST";
		document.getElementById('sensor_list').value = sensor_list;
	// empty
	} else {
		document.getElementById('sensor').value = "ANY";
		document.getElementById('sensor_list').value = "";
	}

	return ret;
}

// *********************** WIZARD Functions ******************************
var wizard_current = 1;
function wizard_refresh() {
	document.getElementById('wizard_'+(wizard_current)).style.display = "block";
	$('#link_'+wizard_current).css("font-weight", "bold");
}
function wizard_next() {
	document.getElementById('wizard_'+wizard_current).style.display = "none";
	if (wizard_current == 0)  document.getElementById('steps').style.display = "";
	else $('#link_'+wizard_current).css("font-weight", "normal");
	wizard_current++;
	
	if (wizard_current >= 18) {
			if (save_all()) document.getElementById('frule').submit();
	} else {
			
			<?php if (!Session::is_pro() || 1 == 1) echo "if (wizard_current == 4) wizard_current = 5;"; // Skip entities ?>
			
			if (wizard_current == 11 && !is_monitor) { // Skip monitor options (detector selected)
					wizard_current = 14;
			}

			// Skip Sticky always
			if (wizard_current == 14) wizard_current = 15;

			// Skip occurrence, timeout to first level rule
			<?php if (!$level || $level <= 1) { ?>
			if (wizard_current == 10 || wizard_current == 9) {
					wizard_current = (is_monitor) ? 11 : 16;
			}
			if (wizard_current == 14 || wizard_current == 15) {
					wizard_current = 16;
			}
			<?php } ?>
			document.getElementById('wizard_'+(wizard_current)).style.display = "block";
			if (wizard_current == 2) {
				init_ptypes();
			}
			if (wizard_current == 4) {
				init_entities();
			}
			if (wizard_current == 5) {
					init_network();
			}
			if (wizard_current == 8) {
					init_sensor();
			}
	}
	// Update steps
	if (wizard_current < 17) {
			document.getElementById('step_'+wizard_current).style.display = "";
			$('#link_'+wizard_current).css("font-weight", "bold");
	}
}
function wizard_goto(num) {
	<?php if (!Session::is_pro() || 1 == 1) echo "if (num == 4) num = 5;"; // Skip entities ?>
	document.getElementById('wizard_'+wizard_current).style.display = "none";
	$('#link_'+wizard_current).css("font-weight", "normal");
	var aux_step = wizard_current;
	wizard_current = num;
	wizard_refresh();

	if (num == 2) {
		init_ptypes();
	}
	if (num == 3) {
		if(aux_step < 3)
			init_sids($('#plugin_id').val(),is_monitor,$('#productselect').val());
	}
	if (num == 4) {
		init_entities();
	}
	if (num == 5) {
		init_network();
	}
	if (num == 8) {
		init_sensor();
	}
	
}
function wizard_refresh() {
	document.getElementById('wizard_'+(wizard_current)).style.display = "block";
	$('#link_'+wizard_current).css("font-weight", "bold");
}

function wizard_back() {
	document.getElementById('wizard_'+wizard_current).style.display = "none";
	if (wizard_current == 0)  document.getElementById('steps').style.display = "";
	else $('#link_'+wizard_current).css("font-weight", "normal");
	
	wizard_current--;
	
	<?php if (!Session::is_pro() || 1 == 1) echo "if (wizard_current == 4) wizard_current = 3;"; // Skip entities ?>

	<?php if (!$level || $level <= 1) { ?>
	if (wizard_current == 10 || wizard_current == 9) {
			wizard_current = 8;
	}
	if (wizard_current == 14 || wizard_current == 15) {
			wizard_current = 13;
	}
	<?php } ?>
	
	if (wizard_current == 14) wizard_current = 13; // Skip Sticky always
	if (wizard_current == 13 && !is_monitor) { // Skip monitor options (detector selected)
			wizard_current = <?php echo (!$level || $level <= 1) ? "8" : "10" ?>;
	}
	
	document.getElementById('wizard_'+(wizard_current)).style.display = "block";
	if (wizard_current == 2) {
		init_ptypes();
	}
	if (wizard_current == 4) {
		init_entities();
	}
	if (wizard_current == 5) {
			init_network();
	}
	if (wizard_current == 8) {
			init_sensor();
	}
	
	// Update steps
	if (wizard_current < 17) {
			document.getElementById('step_'+wizard_current).style.display = "";
			$('#link_'+wizard_current).css("font-weight", "bold");
	}
}
function goto_ask() {
	document.getElementById('wizard_'+(wizard_current)).style.display = "none";
	document.getElementById('wizard_ask').style.display = "block";
}

function onChangePortSelectBox(id,val) {
	if (val == "LIST") {
		if (id.match(/port/)) document.getElementById(id+'_input').style.display = 'inline';
		else document.getElementById(id+'_input').style.visibility = 'visible';
		document.getElementById(id+'_list').value = "";
	} else {
		if (id.match(/port/)) document.getElementById(id+'_input').style.display = 'none';
		else document.getElementById(id+'_input').style.visibility = 'hidden';
		document.getElementById(id+'_list').value = val;
	}
}

// Protocol inputs events
function onClickProtocolAny(level) {

	if (document.getElementById("protocol_any").checked != "") {

		/* check all the protocols */
		document.getElementById("protocol_tcp").checked = "checked";
		document.getElementById("protocol_udp").checked = "checked";
		document.getElementById("protocol_icmp").checked = "checked";

		for (i = 1; i <= level-1; i++)
			document.getElementById("protocol_" + i).checked = "checked";
	}
	else {
		
		/* uncheck all the protocols */
		document.getElementById("protocol_tcp").checked = "";
		document.getElementById("protocol_udp").checked = "";
		document.getElementById("protocol_icmp").checked = "";

		for (i = 1; i <= level-1; i++)
			document.getElementById("protocol_" + i).checked = "";

		/* check at least one default protocol */
		document.getElementById("protocol_tcp").checked = "checked";
	}
}

/** Return true if all the protocols are checked. */
function allProtocolChecked(level) {

	if (document.getElementById("protocol_tcp").checked == "") return false;
	if (document.getElementById("protocol_udp").checked == "") return false;
	if (document.getElementById("protocol_icmp").checked == "") return false;

	for (i = 1; i <= level-1; i++)
		if (document.getElementById("protocol_" + i).checked == "") return false;

	return true;
}

/** Return true if no protocol is checked. */
function noneProtocolChecked(level) {

	if (document.getElementById("protocol_tcp").checked != "") return false;
	if (document.getElementById("protocol_udp").checked != "") return false;
	if (document.getElementById("protocol_icmp").checked != "") return false;

	for (i = 1; i <= level-1; i++)
		if (document.getElementById("protocol_" + i).checked != "") return false;

	return true;
}

/* Event. */
function onClickProtocol(id, level) {

	if (allProtocolChecked(level)) {
		/* check "ANY" if all the protocols are checked */
		document.getElementById("protocol_any").checked = "checked";
	}
	else {
		/* uncheck "ANY" if no protocol is checked */
		document.getElementById("protocol_any").checked = "";
	}

	/* cannot uncheck the protocol if this is the last one */
	if (noneProtocolChecked(level))
		document.getElementById(id).checked = "checked";
}
</script>
</head>
<body>
<form method="post" id="frule" name="frule" action="" style="height:100%">
<input type="hidden" name="id" value="<?php echo $id; ?>" />
<input type="hidden" name="directive_id" value="<?php echo $directive_id; ?>" />
<input type="hidden" name="directive_name" value="<?php echo $directive_name; ?>" />
<input type="hidden" name="directive_prio" value="<?php echo $directive_prio; ?>" />
<input type="hidden" name="directive_intent" value="<?php echo $directive_intent; ?>" />
<input type="hidden" name="directive_strategy" value="<?php echo $directive_strategy; ?>" />
<input type="hidden" name="directive_method" value="<?php echo Util::htmlentities($directive_method); ?>" />
<input type="hidden" name="from_directive" value="<?php echo $from_directive; ?>" />
<input type="hidden" name="level" value="<?php echo $level; ?>" />
<input type="hidden" name="xml_file" value="<?php echo $xml_file; ?>" />
<input type="hidden" name="engine_id" value="<?php echo $engine_id; ?>" />
<input type="hidden" name="reloadindex" value="<?php echo $reloadindex; ?>" />
<table class="transparent" style="width:100%;height:100%" cellpadding="0" cellspacing="0">
	<tr>
		<td class="nobborder" id="steps" style="border-bottom:1px solid #EEEEEE !important;padding:5px" height="20">
			<table class="transparent">
				<tr>
					<td class="nobborder"><img src="../pixmaps/wand.png" alt="wizard"></img></td>
					<?php foreach ($sections as $num => $section_title) { ?>
					<td class="nobborder" style="font-size:11px<?php if ($num > 1) echo ";display:none" ?>" id="step_<?php echo $num ?>" nowrap><?php if ($num > 1) echo " > " ?><a href='' onclick='wizard_goto(<?php echo $num ?>);return false;' style="<?php if ($num == 1) echo "font-weight:bold" ?>" id="link_<?php echo $num ?>"><?php echo $section_title ?></a></td>
					<?php } ?>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<table class="transparent" align="center">
				<tr>
					<td>
<!-- ################## STEP 1: Rule name ##################### -->
						<div id="wizard_1">
						<table width="400" class="transparent">
							<tr>
								<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
									<?php echo gettext("Name for the rule"); ?>
								</th>
							</tr>
			                <tr>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="font-size:12px;height:20px;width: 100%" name="name" id="name" value="" onkeyup="onKeyEnterWizard(this,event);" />
								</td>
								<td class="nobborder" style="white-space:nowrap">
									<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>
									<input type="button" id="button_next_1" onclick="if (valid_input('name')) wizard_next()" value="<?php echo _("Next") ?>"/>
								</td>
			                </tr>
        				</table>
						</div>
						
						<!-- #### STEP 2: Plugin #### -->
						<div id="wizard_2" style="display:none">
						<?php
						$plugin_list = Plugin::get_list($conn, "ORDER BY name");
						$none_checked = 'true';
						$product_types = Product_type::get_list($conn);
						?>
						<input type="hidden" name="plugin_id" id="plugin_id" value="" />
						<input type="hidden" name="type" id="type" value="" />
						<table width="500" class="transparent">
						    <tr>
								<td class="nobborder center" style='height:48px;'>
									<div style="padding-bottom:10px">
										<?php echo _('Choose between ')."<b>"._("Event Types Selection")."</b> "._("or")." <b>"._('Taxonomy')."</b>" ?>
									</div>
									<div style="padding-bottom:10px">
										<input type="radio" name="plug_type_id" value="0" onclick="change_event_type(0, 'id');" checked='checked' /> <b><?php echo _("Event Types")?></b>
										<input type="radio" name="plug_type_id" value="1" onclick="change_event_type(1, 'id');" /> <b><?php echo _("Taxonomy")?></b>
									</div>
								</td>
							</tr>
							<tr>
								<td class='noborder' valign="top">
									<div id='dsg_id' style='height:100%;width:100%;' >
										<table>
									        <!-- ##### plugin id ##### -->
									        <tr>
									                <th style="white-space: nowrap; padding: 5px;font-size:12px">
									                        <?php echo gettext("Select a Plugin"); ?>
									                </th>
									        </tr>
									        <tr>
									                <td class="nobborder">
									                        <table class="transparent" width="100%">
									                                <tr>
									                                        <td class="nobborder" colspan="5">
									                                        <div style="overflow:auto;height:300px;width:500px;border:1px solid #EEEEEE">
									                                                <table class="transparent" width="100%">
									                                                <?php
									                                                foreach($plugin_list as $plugin) {
									                                                    $plugin_type = $plugin->get_type();
									                                                    // Skip monitor plugins for root rule
									                                                    if ($plugin_type != '1' && (!$level || $level <= 1)) { continue; }
									                                                    if ($plugin_type == '1') $type_name = 'Detector';
									                                                    elseif ($plugin_type == '2') $type_name = 'Monitor';
									                                                    else $type_name = 'Other (' . $plugin_type . ')';
									                                                    ?>
									                                                
									                                                <tr id="<?php echo $plugin->get_id() ?>;<?php echo strtolower($plugin->get_name()) ?>" class="plugin_line" style="display:block">
									                                                    <td width="110" class="nobborder"><input type="button" style="width:110px" onclick="document.getElementById('plugin_id').value='<?php echo $plugin->get_id() ?>';document.getElementById('type').value='<?php echo ($plugin_type == '2') ? "monitor" : "detector" ?>';wizard_next();init_sids(<?php echo $plugin->get_id() ?>,<?php echo ($plugin_type == '2') ? "true" : "false" ?>);" value="<?php echo $plugin->get_name() ?>"/></td>
									                                                    <td class="nobborder"><?php echo $type_name." - ".$plugin->get_description() ?></td>
									                                                </tr>
									                                                
									                                                <?php } ?>
									                                                </table>
									                                        </div>
									                                        </td>
									                                </tr>
									                                <tr><td class="nobborder" colspan="3">&middot; <?php echo "<b>"._("Search")."</b> "._("a plugin name or ID") ?>: <input type="text" name="search_string" id="search_string" value="" onkeyup="search_plugin(this.value)"></input>
									                                <br/><center><input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>
									                                &nbsp;<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/></center></td></tr>
									                        </table>
									                </td>
									        </tr>
										</table>
									</div>
									
									<div id='txn_id' style='height:100%;width:100%;display:none;'>
										<table width="100%">
											<tr>
												<th style="background-position:top center;padding:5px;font-size:12px" valign='middle'>
													<?php echo _("Product Type") ?><br/>
												</th>
											</tr>
											<tr><td class="nobborder">&middot; <i><?php echo _("Empty selection means ANY product type") ?></i></td></tr>
											<tr>
												<td class="left nobborder">
													<input type="hidden" name="product" id="product" value=""></input>
													<input type="hidden" name="product_list" id="product_list" value=""></input>
													<select id="productselect" class="multiselect_product" multiple="multiple" name="productselect[]" style='width:600px;height:300px;'>
														<?php
														foreach ($product_types  as $ptype){
															echo "<option value='".$ptype->get_id()."'>".$ptype->get_name()."</option>\n";
														}
														?>
													</select>
												</td>
											</tr>
											<tr>
												<td class="center nobborder">
													<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;
													<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/>&nbsp;
													<input type="button" value="Next" id="button_next_2" onclick="wizard_next();init_sids(0,<?php echo ($plugin_type == '2') ? "true" : "false" ?>,$('#productselect').val())"></input>
												</td>
											</tr>
										</table>
									</div>
									
									</td>
								</tr>
						</table>
						</div>
						
<!-- ################## STEP 3: Plugin SID ######################## -->
						<div id="wizard_3" style="display:none">
						<input type="hidden" name="plugin_sid" id="plugin_sid" value="ANY">
						<input type="hidden" name="plugin_sid_list" id="plugin_sid_list" value="ANY" />
						<table class="transparent" width="500">
							<tr>
								<td class="nobborder center" style='height:48px;'>
									<div style="padding-bottom:10px">
										<?php echo _('Choose between ')."<b>"._("Event Sub-Types Selection")."</b> "._("or")." <b>"._('Taxonomy')."</b>" ?>
									</div>
									<div style="padding-bottom:10px">
										<input type="radio" name="plug_type_sid" class="plug_type_sid" value="0" onclick="change_event_type(0, 'sid');" checked/> <b><?php echo _("Event Sub-Types")?></b>
										<input type="radio" name="plug_type_sid" id="plug_type_sid_tax" class="plug_type_sid" value="1" onclick="change_event_type(1, 'sid');"/> <b><?php echo _("Taxonomy")?></b>
									</div>
								</td>
							</tr>
							<tr>
								<td class='noborder' valign="top">
									<div id='dsg_sid' style='height:100%;width:100%' >
						                <!-- ##### plugin sid ##### -->
						                <table class='container'>
									        <tr>
									                <th style="white-space: nowrap; padding: 5px;font-size:12px">
									                        <?php echo gettext("Plugin Signatures"); ?>
									                </th>
									        </tr>
									        <tr>
									                <td class="nobborder">
		                                                <div id='ms_body'>
		                                                </div>
									                </td>
									        </tr>
									        <tr><td class="nobborder">&middot; <i><?php echo _("Empty selection means ANY signature") ?></i></td></tr>
									        <tr>
									                <td class="center nobborder" style="padding-top:10px">
									                        <input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;
									                        <input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/>&nbsp;
									                        <input type="button"  id="button_next_3_sid"<?php if (!preg_match("/\:PLUGIN\_SID/",$rule->plugin_sid)) { ?><?php } ?> value="<?php echo ($level > 1) ? _("Selected from List") : _("Next") ?>" onclick="wizard_next();"/>
									                </td>
									        </tr>
									        <?php for ($i = 1; $i <= $level - 1; $i++) {
									                        $sublevel = $i . ":PLUGIN_SID";
									                        //echo "<option value=\"$sublevel\">$sublevel</option>";
									                        ?><tr><td class="center nobborder"><input type="button" value="<?php echo _("Plugin Sid from rule of level")." $i" ?>"<?php if ($rule->plugin_sid == $sublevel) { ?><?php } ?> onclick="document.getElementById('plugin_sid').value='<?php echo $sublevel ?>';wizard_next()"/></td></tr><?php
									                        $sublevel = "!" . $i . ":PLUGIN_SID";
									                        ?><tr><td class="center nobborder"><input type="button" value="<?php echo "!"._("Plugin Sid from rule of level")." $i" ?>"<?php if ($rule->plugin_sid == $sublevel) { ?><?php } ?> onclick="document.getElementById('plugin_sid').value='<?php echo $sublevel ?>';wizard_next()"/></td></tr><?php
									                        //echo "<option value=\"$sublevel\">$sublevel</option>";?>
									        <?php } ?>
									     </table>
									 </div>
									 
									 <div id='txn_sid' style='height:100%;width:100%;display:none'>
										<table width="100%">
											<tr>
												<th colspan="3" style="background-position:top center" valign='middle'>
													<?php echo _("Taxonomy Parameters") ?><br/>
												</th>
											</tr>
											<tr>
												<td class="nobborder" style="text-align:right"><?php echo _("Category")?>:</td>
												<td class="left nobborder">
													<input type="hidden" name="category_aux" value="<?php echo $rule->category ?>">
													<select name="category" id='category' style='width:165px;' onchange='load_subcategories($(this).val());'>
														<option value='' selected='selected'><?php echo _("ANY") ?></option>
													</select>
												</td>
											</tr>
											<tr>
												<td class="nobborder" style="text-align:right"><?php echo _("Subcategory")?>:</td>
												<td class="left nobborder">
													<select name="subcategory" id='subcategory' style='width:165px;'>
														<option value='' selected='selected'><?php echo _("ANY") ?></option>
													</select>
												</td>
												<td class="center nobborder">
													<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;
													<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/>&nbsp;
													<input type="button" value="Next" id="button_next_3_txn" onclick="wizard_next();"></input>
												</td>
											</tr>
										</table>
									</div>
								</td>
							</tr>
						</table>
						</div>
						
<!-- ################## STEP 5: Network ###################### -->
						<div id="wizard_5" style="display:none">
						<table class="transparent">
							<tr>
								<td class="container">
									<input type="hidden" name="from" id="from" value=""></input>
									<input type="hidden" name="from_list" id="from_list" value=""></input>
									<input type="hidden" name="port_from" id="port_from" value=""></input>
									<input type="hidden" name="to" id="to" value=""></input>
									<input type="hidden" name="to_list" id="to_list" value=""></input>
									<input type="hidden" name="port_to" id="port_to" value=""></input>
									<table class="transparent">
										<tr>
											<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
												<?php echo gettext("Network"); ?>
											</th>
										</tr>
										<tr><td class="nobborder">&middot; <i><?php echo _("Empty selection means ANY asset") ?></i></td></tr>
										<tr>
											<td class="nobborder" valign="top">
												<table class="transparent">
													<!-- ##### from ##### -->
													<tr>
														<td class="nobborder" valign="top">
															<table class="transparent">
																<tr>
																	<th><?php echo _("Source Host/Network") ?></th>
																</tr>
																<tr>
																	<td class="nobborder">
																	<div id="from_input" style="visibility:<?php echo (preg_match("/\:...\_IP/",$rule->from)) ? "hidden" : "visible" ?>">
																		<table>
																			<tr>
																				<td class="nobborder" valign="top">
																					<table align="center" class="noborder">
																					<tr>
																						<th style="background-position:top center"><?php echo _("Source") ?>
																						</th>
																						<td class="left nobborder">
																							<select id="fromselect" name="fromselect[]" size="12" multiple="multiple" style="width:150px">
																							
																							</select>
																							<input type="button" class="small av_b_secondary" value=" [X] " onclick="deletefrom('fromselect');"/>
																						</td>
																					</tr>
																					</table>
																				</td>
																				<td valign="top" class="nobborder">
																					<table class="noborder" align='center'>
																					<tr><td class="left nobborder" id="inventory_loading_sources"></td></tr>
																					<tr>
																						<td class="left nobborder" nowrap>
																							<?php echo _("Asset")?>: <input type="text" id="filterfrom" name="filterfrom" size='18'/>
																							&nbsp;<input type="button" class="small av_b_secondary" value="<?php echo _("Filter")?>" onclick="load_tree(this.form.filterfrom.value)" />&nbsp;<input type="button" class="small av_b_secondary" value="<?php echo _("Add IP")?>" onclick="addto('fromselect',this.form.filterfrom.value,this.form.filterfrom.value)" /> 
																							<div id="containerfrom" class='container_ptree'></div>
																						</td>
																					</tr>
																					<tr><td class="left nobborder"><input type="button" class='av_b_secondary' id="button_homenet_from" value="<?php echo _("Home Net") ?>" onclick="addto('fromselect','HOME_NET','HOME_NET')"/> <input type="button" class='av_b_secondary' id="button_nothomenet_from" value="!<?php echo _("Home Net") ?>" onclick="addto('fromselect','!HOME_NET','!HOME_NET')"/></td></tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																	</div>
																	</td>
																</tr>
																<?php if ($level > 1) { ?>
																<tr>
																	<td class="center nobborder">
																	From a parent rule: <select name="from" id="from" style="width:180px" onchange="onChangePortSelectBox('from',this.value)">
																	<?php
																	echo "<option value=\"LIST\"></option>";
																	for ($i = 1; $i <= $level - 1; $i++) {
																	    $sublevel = $i . ":SRC_IP";
																	    $selected = ($rule->from == $sublevel) ? " selected" : "";
																	    echo "<option value=\"$sublevel\"$selected>Source IP from level $i</option>";
																	    $sublevel = "!" . $i . ":SRC_IP";
																	    $selected = ($rule->from == $sublevel) ? " selected" : "";
																	    echo "<option value=\"$sublevel\"$selected>!Source IP from level $i</option>";
																	    $sublevel = $i . ":DST_IP";
																	    $selected = ($rule->from == $sublevel) ? " selected" : "";
																	    echo "<option value=\"$sublevel\"$selected>Destination IP from level $i</option>";
																	    $sublevel = "!" . $i . ":DST_IP";
																	    $selected = ($rule->from == $sublevel) ? " selected" : "";
																	    echo "<option value=\"$sublevel\"$selected>!Destination IP from level $i</option>";
																	}
																	?>
																	</select>
																	</td>
																</tr>
																<?php } else { ?>
																<input type="hidden" name="from" id="from" value="LIST"></input>
																<?php } ?>
															</table>
														</td>
													</tr>
													<tr><th><?php echo _("Source Port(s)") ?></th></tr>
													<tr><td class="nobborder">&middot; <i><?php echo _("Use comma to specify several ports").'<br>&middot '._("Can be negated using '!'") ?></i></td></tr>
													<tr>
														<td class="center nobborder">
															<?php if ($level > 1) { ?>
															From a parent rule: <select style="width:180px" name="port_from" id="port_from" onchange="onChangePortSelectBox('port_from',this.value)">
															<?php
															echo "<option value=\"LIST\"></option>";
															for ($i = 1; $i <= $level - 1; $i++) {
															    $sublevel = $i . ":SRC_PORT";
															    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
															    echo "<option value=\"$sublevel\"$selected>Source Port from level $i</option>";
															    $sublevel = "!" . $i . ":SRC_PORT";
															    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
															    echo "<option value=\"$sublevel\"$selected>!Source Port from level $i</option>";
															    $sublevel = $i . ":DST_PORT";
															    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
															    echo "<option value=\"$sublevel\"$selected>Destination Port from level $i</option>";
															    $sublevel = "!" . $i . ":DST_PORT";
															    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
															    echo "<option value=\"$sublevel\"$selected>!Destination Port from level $i</option>";
															}
															?>
															</select>&nbsp;
															<?php } else { ?>
															<input type="hidden" name="port_from" id="port_from" value="LIST"></input>
															<?php } ?>
															<div id="port_from_input" style="display:<?php echo (preg_match("/\_PORT/",$rule->port_from)) ? "none" : "inline" ?>"><input type="text" name="port_from_list" id="port_from_list" value="<?php echo $rule->port_from ?>"></input></div>
														</td>
													</tr>
													
													<tr><td class="nobborder"><a href="" id="link_reputation_from" onclick="$('#rep_from_div').toggle(); if($('#rep_from_div').is(':visible')){ $('#rep_from_arrow').attr('src','../pixmaps/arrow_green_down.gif'); } else{ $('#rep_from_arrow').attr('src','../pixmaps/arrow_green.gif'); } return false;"><img id="rep_from_arrow" border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?php echo "<b>"._("Reputation")."</b> "._("options") ?></a></td></tr>
													<tr>
														<td class="nobborder" id="rep_from_div" style="display:none">
															<table class="noborder" align='center' width='100%'>
																<tr>
																	<th style="background-position:top center;" valign='middle'>
																		<?php echo _("Reputation Parameters") ?><br/>
																	</th>
																</tr>
																<tr>
																	<td class="left nobborder">
																		<div style='text-align: left; padding:10px 0 15px 10px; clear: both;'>
																			<div style='float: left; width:90px;'><?php echo _("Reputation from")?>:</div>
																			<div style='float: left;'>
																				<select id="from_rep" name="from_rep" style="width:60px" onchange="if(this.value=='true') $('.rep_from_select').attr('disabled',false); else $('.rep_from_select').attr('disabled',true);">
																					<option value=''><?php echo _("No") ?></option>
																					<option value='true' <?php if ($rule->from_rep == "true" || $rule->from_rep_min_pri || $rule->from_rep_min_rel) echo "selected" ?>><?php echo _("Yes") ?></option>
																				</select>
																			</div>
																		</div>
																	</td>
																</tr>
																<tr>
																	<td class="left nobborder">
																		<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
																			<div style='float: left; width:90px;'><?php echo _("Min Priority")?>:</div>
																			<div style='float: left;'>
																				<select id="from_rep_min_pri" name="from_rep_min_pri" class="rep_from_select" <?php if ($rule->from_rep != "true" && !$rule->from_rep_min_pri && !$rule->from_rep_min_rel) echo "disabled" ?>>
																					<option value="">-</option>
																					<?php
																					for($i=1; $i <= 10; $i++) {
																						$selected = ($rule->from_rep_min_pri == $i) ? "selected" : "";
																						echo "<option value=$i $selected>$i</option>\n";
																					}
																					?>
																				</select>
																			</div>
																		</div>
																	</td>
																</tr>
																<tr>
																	<td class="left nobborder">
																		<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
																			<div style='float: left; width:90px;'><?php echo _("Min Reliablility")?>:</div>
																			<div style='float: left;'>
																				<select id="from_rep_min_rel" name="from_rep_min_rel" class="rep_from_select" <?php if ($rule->from_rep != "true" && !$rule->from_rep_min_pri && !$rule->from_rep_min_rel) echo "disabled" ?>>
																					<option value="">-</option>
																					<?php
																					for($i=1; $i <= 10; $i++) {
																						$selected = ($rule->from_rep_min_rel == $i) ? "selected" : "";
																						echo "<option value=$i $selected>$i</option>\n";
																					}
																					?>
																				</select>
																			</div>
																		</div>
																	</td>
																</tr>
															</table>
														</td>
													</tr>
												</table>
											</td>
										
											<td class="nobborder" valign="top">
												<table class="transparent">
													<!-- ##### to ##### -->
													<tr>
														<td class="nobborder" valign="top">
															<table class="transparent">
																<tr>
																	<th><?php echo _("Destination Host/Network") ?></th>
																</tr>
																<tr>
																	<td class="nobborder">
																	<div id="to_input" style="visibility:<?php echo (preg_match("/\:...\_IP/",$rule->to)) ? "hidden" : "visible" ?>">
																		<table>
																			<tr>
																				<td class="nobborder" valign="top">
																					<table align="center" class="noborder">
																					<tr>
																						<th style="background-position:top center"><?php echo _("Destination") ?>
																						</th>
																						<td class="left nobborder">
																							<select id="toselect" name="toselect[]" size="12" multiple="multiple" style="width:150px">
																							
																							</select>
																							<input type="button" class="small av_b_secondary" value=" [X] " onclick="deletefrom('toselect');"/>
																						</td>
																					</tr>
																					</table>
																				</td>
																				<td valign="top" class="nobborder">
																					<table class="noborder" align='center'>
																					<tr><td class="left nobborder" id="inventory_loading_sources"></td></tr>
																					<tr>
																						<td class="left nobborder">
																							<?php echo _("Asset")?>: <input type="text" id="filterto" name="filterto"/>
																							&nbsp;<input type="button" class="small av_b_secondary" value="<?php echo _("Filter")?>" onclick="load_tree(this.form.filterto.value)" />&nbsp;<input type="button" class="small av_b_secondary" value="<?php echo _("Add IP")?>" onclick="addto('toselect',this.form.filterto.value,this.form.filterto.value)" /> 
																							<div id="containerto" class='container_ptree'></div>
																						</td>
																					</tr>
																					<tr><td class="left nobborder"><input type="button" class='av_b_secondary' id="button_homenet_to" value="<?php echo _("Home Net") ?>" onclick="addto('toselect','HOME_NET','HOME_NET')"/> <input type="button" class='av_b_secondary' id="button_nothomenet_to" value="!<?php echo _("Home Net") ?>" onclick="addto('toselect','!HOME_NET','!HOME_NET')"/></td></tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																	</div>
																	</td>
																</tr>
																<?php if ($level > 1) { ?>
																<tr>
																	<td class="center nobborder">
																	From a parent rule: <select name="to" id="to" style="width:180px" onchange="onChangePortSelectBox('to',this.value)">
																	<?php
																	echo "<option value=\"LIST\"></option>";
																	for ($i = 1; $i <= $level - 1; $i++) {
																	    $sublevel = $i . ":SRC_IP";
																	    $selected = ($rule->to == $sublevel) ? " selected" : "";
																	    echo "<option value=\"$sublevel\"$selected>Source IP from level $i</option>";
																	    $sublevel = "!" . $i . ":SRC_IP";
																	    $selected = ($rule->to == $sublevel) ? " selected" : "";
																	    echo "<option value=\"$sublevel\"$selected>!Source IP from level $i</option>";
																	    $sublevel = $i . ":DST_IP";
																	    $selected = ($rule->to == $sublevel) ? " selected" : "";
																	    echo "<option value=\"$sublevel\"$selected>Destination IP from level $i</option>";
																	    $sublevel = "!" . $i . ":DST_IP";
																	    $selected = ($rule->to == $sublevel) ? " selected" : "";
																	    echo "<option value=\"$sublevel\"$selected>!Destination IP from level $i</option>";
																	}
																	?>
																	</select>
																	</td>
																</tr>
																<?php } else { ?>
																<input type="hidden" name="to" id="to" value="LIST"/>
																<?php } ?>
															</table>
														</td>
													</tr>
													<tr><th><?php echo _("Destination Port(s)") ?></th></tr>
													<tr><td class="nobborder">&middot; <i><?php echo _("Use comma to specify several ports").'<br>&middot '._("Can be negated using '!'") ?></i></td></tr>
													<tr>
														<td class="center nobborder">
															<?php if ($level > 1) { ?>
															From a parent rule: <select style="width:180px" name="port_to" id="port_to" onchange="onChangePortSelectBox('port_to',this.value)">
															<?php
															echo "<option value=\"LIST\"></option>";
															for ($i = 1; $i <= $level - 1; $i++) {
															    $sublevel = $i . ":SRC_PORT";
															    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
															    echo "<option value=\"$sublevel\"$selected>Source Port from level $i</option>";
															    $sublevel = "!" . $i . ":SRC_PORT";
															    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
															    echo "<option value=\"$sublevel\"$selected>!Source Port from level $i</option>";
															    $sublevel = $i . ":DST_PORT";
															    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
															    echo "<option value=\"$sublevel\"$selected>Destination Port from level $i</option>";
															    $sublevel = "!" . $i . ":DST_PORT";
															    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
															    echo "<option value=\"$sublevel\"$selected>!Destination Port from level $i</option>";
															}
															?>
															</select>&nbsp;
															<?php } else { ?>
															<input type="hidden" name="port_to" id="port_to" value="LIST"></input>
															<?php } ?>
															<div id="port_to_input" style="display:<?php echo (preg_match("/\_PORT/",$rule->port_to)) ? "none" : "inline" ?>"><input type="text" name="port_to_list" id="port_to_list" value="<?php echo $rule->port_to ?>"></input></div>
														</td>
													</tr>
													
													<tr><td class="nobborder"><a href="" id="link_reputation_to" onclick="$('#rep_to_div').toggle(); if($('#rep_to_div').is(':visible')){ $('#rep_to_arrow').attr('src','../pixmaps/arrow_green_down.gif'); } else{ $('#rep_to_arrow').attr('src','../pixmaps/arrow_green.gif'); } return false;"><img id="rep_to_arrow" border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?php echo "<b>"._("Reputation")."</b> "._("options") ?></a></td></tr>
													<tr>
														<td class="nobborder" id="rep_to_div" style="display:none">
															<table class="noborder" align='center' width='100%'>
																<tr>
																	<th style="background-position:top center;" valign='middle'>
																		<?php echo _("Reputation Parameters") ?><br/>
																	</th>
																</tr>
																<tr>
																	<td class="left nobborder">
																		<div style='text-align: left; padding:10px 0 15px 10px; clear: both;'>
																			<div style='float: left; width:90px;'><?php echo _("Reputation to")?>:</div>
																			<div style='float: left;'>
																				<select id="to_rep" name="to_rep" style="width:60px" onchange="if(this.value=='true') $('.rep_to_select').attr('disabled',false); else $('.rep_to_select').attr('disabled',true);">
																					<option value=''><?php echo _("No") ?></option>
																					<option value='true' <?php if ($rule->to_rep == "true" || $rule->to_rep_min_pri || $rule->to_rep_min_rel) echo "selected" ?>><?php echo _("Yes") ?></option>
																				</select>
																			</div>
																		</div>
																	</td>
																</tr>
																<tr>
																	<td class="left nobborder">
																		<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
																			<div style='float: left; width:90px;'><?php echo _("Min Priority")?>:</div>
																			<div style='float: left;'>
																				<select id="to_rep_min_pri" name="to_rep_min_pri" class="rep_to_select" <?php if ($rule->to_rep != "true" && !$rule->to_rep_min_pri && !$rule->to_rep_min_rel) echo "disabled" ?>>
																					<option value="">-</option>
																					<?php
																					for($i=1; $i <= 10; $i++) {
																						$selected = ($rule->to_rep_min_pri == $i) ? "selected" : "";
																						echo "<option value=$i $selected>$i</option>\n";
																					}
																					?>
																				</select>
																			</div>
																		</div>
																	</td>
																</tr>
																<tr>
																	<td class="left nobborder">
																		<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
																			<div style='float: left; width:90px;'><?php echo _("Min Reliablility")?>:</div>
																			<div style='float: left;'>
																				<select id="to_rep_min_rel" name="to_rep_min_rel" class="rep_to_select" <?php if ($rule->to_rep != "true" && !$rule->to_rep_min_pri && !$rule->to_rep_min_rel) echo "disabled" ?>>
																					<option value="">-</option>
																					<?php
																					for($i=1; $i <= 10; $i++) {
																						$selected = ($rule->to_rep_min_rel == $i) ? "selected" : "";
																						echo "<option value=$i $selected>$i</option>\n";
																					}
																					?>
																				</select>
																			</div>
																		</div>
																	</td>
																</tr>
															</table>
														</td>
													</tr>
												</table>
											</td>
										</tr>
										<tr>
											<td colspan="2" class="center nobborder" style="padding-top:10px">
												<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;
												<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/>&nbsp;
												<input type="button" id="button_next_5" value="<?php echo _("Next") ?>" onclick="wizard_next()"/>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						</div>

<!-- ################## STEP 6: Reliability ###################### -->
						<div id="wizard_6" style="display:none">
						<input type="hidden" name="reliability" id="reliability" value=""></input>
						<input type="hidden" name="reliability_op" id="reliability_op" value=""></input>
							<table class="transparent" width="100%">
								<tr>
									<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
										<?php echo gettext("Reliability"); ?>
									</th>
								</tr>
								<tr><td colspan="2" class="nobborder">&middot; <i>Risk = (priority * reliability * asset_value) / 25.</i></td></tr>
								<?php
								$value = 5;
								for ($i = 0; $i <= 10; $i++) {
								    ?>
								    <tr>
								    	<td class="center nobborder"><input type="button" value="= <?php echo $i ?>" onclick="document.getElementById('reliability').value = '<?php echo $i ?>';document.getElementById('reliability_op').value = '=';goto_ask();" style="width:50px"></input></td>
								    	<?php if ($level > 1) { ?><td class="center nobborder"><input type="button" value="+ <?php echo $i ?>" onclick="document.getElementById('reliability').value = '<?php echo $i ?>';document.getElementById('reliability_op').value = '+';goto_ask();" style="width:50px"></input></td><?php } ?>
								    </tr>
								    <?php
								}
								?>
								<tr>
									<td colspan="2" class="center nobborder">
										<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;
										<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/>
									</td>
								</tr>
							</table>
						</div>

<!-- ################## STEP X: Ask to user -->
						<div id="wizard_ask" style="display:none">
						<table class="transparent">
							<tr><th style="white-space: nowrap; padding: 5px;font-size:12px"><?php echo _("Rule defined") ?></th></tr>
							<tr><td style="padding-top:10px;padding-bottom:10px"><?php echo _("Would you like to specify any other condition for this rule (Protocol, Sensor, Special fields...)?") ?></td></tr>
							<tr>
								<td>
									<input type="button" value="<?php echo _("Back") ?>" onclick="$('#wizard_ask').hide();wizard_current++;wizard_back()"/>&nbsp;
									<input type="button" id="button_finish_x" value="<?php echo _("Finish") ?>" onclick="if (save_all()) document.getElementById('frule').submit();"/>&nbsp;
									<input type="button" id="button_next_x" value="<?php echo _("Next") ?>" onclick="$('#wizard_ask').hide();wizard_next()"/>
								</td>
							</tr>
						</table>
						</div>

<!-- ################## STEP 7: Protocol ###################### -->
						<div id="wizard_7" style="display:none">
						<table class="transparent">
							<tr>
								<td class="container">
									<table class="transparent">
										<tr>
											<th style="white-space: nowrap; padding: 5px;font-size:12px">
												<?php echo gettext("Protocol"); ?>
											</th>
										</tr>
										<!-- ##### first line ##### -->
										<tr>
											<td class="center nobborder">
												<!-- ##### any ##### -->
												<input type="checkbox" name="protocol_any" id="protocol_any" onclick="onClickProtocolAny(<?php echo $level; ?>)" checked />&nbsp;ANY&nbsp;&nbsp;&nbsp;
												<!-- ##### tcp ##### -->
												<input type="checkbox" name="protocol_tcp" id="protocol_tcp" onclick="onClickProtocol('protocol_tcp',<?php echo $level; ?>)" />&nbsp;TCP&nbsp;&nbsp;&nbsp;
												<!-- ##### udp ##### -->
												<input type="checkbox" name="protocol_udp" id="protocol_udp" onclick="onClickProtocol('protocol_udp',<?php echo $level; ?>)" />&nbsp;UDP&nbsp;&nbsp;&nbsp;
												<!-- ##### icmp ##### -->
												<input type="checkbox" name="protocol_icmp" id="protocol_icmp" onclick="onClickProtocol('protocol_icmp',<?php echo $level; ?>)" />&nbsp;ICMP&nbsp;&nbsp;&nbsp;
											</td>
										</tr>
										<!-- ##### second line ##### -->
										<?php if ($level > 1) { ?>
										<tr>
											<td class="center nobborder">
												<!-- ##### :protocol ##### -->
												<?php for ($i = 1; $i <= $level - 1; $i++) { ?>
												<input type="checkbox" name="protocol_<?php echo $i; ?>" id="protocol_<?php echo $i; ?>" onclick="onClickProtocol('protocol_<?php echo $i; ?>',<?php echo $level; ?>)" />&nbsp;<?php echo $i . ":PROTOCOL"; ?>&nbsp;&nbsp;&nbsp;
												<?php } ?>
											</td>
										</tr>
										<?php } ?>
										<tr>
											<td class="center nobborder" style="padding-top:10px">
												<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;
												<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/>&nbsp;
												<input type="button" id="button_next_7" value="<?php echo _("Next") ?>" onclick="wizard_next()"/>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						</div>
						
<!-- ################## STEP 8: Sensor ###################### -->
						<div id="wizard_8" style="display:none">
						<table class="transparent">
							<tr>
								<td class="container">
									<input type="hidden" name="sensor" id="sensor" value=""></input>
									<input type="hidden" name="sensor_list" id="sensor_list" value=""></input>
									<table class="transparent">
										<tr>
											<th style="white-space: nowrap; padding: 5px;font-size:12px">
												<?php echo gettext("Sensor"); ?>
											</th>
										</tr>
										<tr><td class="nobborder">&middot; <i><?php echo _("Empty selection means ANY sensor") ?></i></td></tr>
										<tr><td class="nobborder" id="sensor_msg" style="color:red"></td></tr>
										<tr>
											<td class="nobborder">
												<div id='ms_body'>
													<select id="sensorselect" class="multiselect_sensor" multiple="multiple" name="sensorselect[]" style="display:none;width:600px;height:300px">
													<?php
													$_list_data = Av_sensor::get_list($conn);
													$s_list     = $_list_data[0];
													foreach($s_list as $s_id => $s)
                                                     {
														$sensor_name = $s['name'];
														$sensor_id = Util::uuid_format($s_id);
														$sensor_entities_arr = $s['ctx'];
														$sensor_entities = "";
														foreach ($sensor_entities_arr as $e_id => $e_name) {
															$sensor_entities .= " $e_id";
														}
														echo "<option value='$sensor_id' ctx='$sensor_entities'>$sensor_name</option>\n";
													}
													?>
													</select>
												</div>
											</td>
										</tr>
										<?php for ($i = 1; $i <= $level - 1; $i++) {
												$sublevel = $i . ":SENSOR";
												//echo "<option value=\"$sublevel\">$sublevel</option>";
												?><tr><td class="center nobborder"><input type="button" id="button_sensor_fromrule_<?php echo $i ?>" value="<?php echo _("Sensor from rule of level")." $i" ?>"<?php if ($rule->sensor == $sublevel) { ?><?php } ?> onclick="document.getElementById('sensor').value='<?php echo $sublevel ?>';wizard_next()"></td></tr><?php
												$sublevel = "!" . $i . ":SENSOR";
												?><tr><td class="center nobborder"><input type="button" id="button_notsensor_fromrule_<?php echo $i ?>" value="<?php echo "!"._("Sensor from rule of level")." $i" ?>"<?php if ($rule->sensor == $sublevel) { ?><?php } ?> onclick="document.getElementById('sensor').value='<?php echo $sublevel ?>';wizard_next()"></td></tr><?php
												//echo "<option value=\"$sublevel\">$sublevel</option>";?>
										<?php } ?>
										<tr>
											<td class="center nobborder" style="padding-top:10px">
												<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;
												<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/>&nbsp;
												<input type="button" id="button_next_8" value="<?php echo _("Next") ?>" onclick="wizard_next()"/>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						</div>
						
<!-- ################## STEP 9: Occurrence ###################### -->
						<?php
						/* default values for "occurrence" */
						$occurrence_list = array(
						    1,
						    2,
						    3,
						    4,
						    5,
						    10,
						    15,
						    50,
						    75,
						    200,
						    300,
						    1000,
						    1500,
						    10000,
						    20000,
						    50000,
						    65535,
						    100000
						);
						?>
						<div id="wizard_9" style="display:none">	
						<input type="hidden" name="occurrence" id="occurrence" value="1"></input>
							<table class="transparent" width="100%">
								<tr>
									<th style="white-space: nowrap; padding: 5px;font-size:12px">
										<?php echo gettext("Ocurrence"); ?>
									</th>
								</tr>
								<tr><td class="center nobborder"><input type="button" value="ANY" onclick="document.getElementById('occurrence').value = 'ANY';wizard_next();" style="width:80px"></input></td></tr>
								<?php
								foreach($occurrence_list as $value) {
									?><tr><td class="center nobborder"><input type="button" value="<?php echo $value ?>" onclick="document.getElementById('occurrence').value = '<?php echo $value ?>';wizard_next();" style="width:80px"></input></td></tr><?php
								}
								?>
								<tr><td class="center nobborder"><input type="text" style="width:80px" name="aux_occurrence" id="aux_occurrence" value="<?php echo _("Other...") ?>" onfocus="this.value='';document.getElementById('risk_oc_next').style.display=''"></input></td></tr>
								<tr><td class="center nobborder" id="risk_oc_next" style="display:none"><input type="button" value="OK" onclick="document.getElementById('occurrence').value = document.getElementById('aux_occurrence').value;wizard_next();" style="width:60px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/></td></tr>
							</table>
						</div>
						
<!-- ################## STEP 10: Timeout ###################### -->
						<?php
						/* default values for "time_out" */
						$timeout_list = array(
						    5,
						    10,
						    20,
						    30,
						    60,
						    180,
						    300,
						    600,
						    1200,
						    1800,
						    3600,
						    7200,
						    43200,
						    86400
						);
						?>
						<div id="wizard_10" style="display:none">
						<input type="hidden" name="time_out" id="time_out" value=""></input>
							<table class="transparent" width="100%">
								<tr>
									<th style="white-space: nowrap; padding: 5px;font-size:12px">
										<?php echo gettext("Timeout")." ("._("seconds").")"; ?>
									</th>
								</tr>
								<?php
								foreach($timeout_list as $value) {
									?><tr><td class="center nobborder"><input type="button" value="<?php echo $value ?>" onclick="document.getElementById('time_out').value = '<?php echo $value ?>';wizard_next();" style="width:80px"></input></td></tr><?php
								}
								?>
								<tr><td class="center nobborder"><input type="text" style="width:80px" name="aux_time_out" id="aux_time_out" value="<?php echo _("Other...") ?>" onfocus="this.value='';document.getElementById('risk_timeout_next').style.display=''"></input></td></tr>
								<tr><td class="center nobborder" id="risk_timeout_next" style="display:none"><input type="button" value="OK" onclick="document.getElementById('time_out').value = document.getElementById('aux_time_out').value;wizard_next();" style="width:60px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/></td></tr>
							</table>
						</div>
						
<!-- ################## STEP 11: Monitor Value ###################### -->
						<?php
						/* default values for "value" */
						$value_list = array(
						    10,
						    15,
						    20,
						    30,
						    50,
						    100,
						    200,
						    300,
						    400,
						    500
						);
						?>
						<div id="wizard_11" style="display:none">
						<input type="hidden" name="condition" id="condition" value=""></input>
						<input type="hidden" name="value" id="value" value=""></input>
							<table class="transparent" width="100%">
								<!-- ##### condition AND value ##### -->
								<tr>
									<th style="white-space: nowrap; padding: 5px;font-size:12px">
										<?php echo gettext("Monitor value"); ?>
									</th>
								</tr>
								<tr>
									<td class="center nobborder">
										<table class="transparent">
											<tr>
												<td colspan="5" class="center nobborder"><input type="button" value="Default" onclick="document.getElementById('condition').value = 'Default';document.getElementById('value').value = 'Default';wizard_next();" style="width:80px"></input></td>
											</tr>
											<?php foreach ($value_list as $value) { ?>
											<tr>
												<td class="center nobborder"><input type="button" value="&#8800 <?php echo $value ?>" onclick="document.getElementById('condition').value = 'ne';document.getElementById('value').value = '<?php echo $value ?>';wizard_next();" style="width:80px"></input></td>
												<td class="center nobborder"><input type="button" value="&#60 <?php echo $value ?>" onclick="document.getElementById('condition').value = 'lt';document.getElementById('value').value = '<?php echo $value ?>';wizard_next();" style="width:80px"></input></td>
												<td class="center nobborder"><input type="button" value="&#62 <?php echo $value ?>" onclick="document.getElementById('condition').value = 'gt';document.getElementById('value').value = '<?php echo $value ?>';wizard_next();" style="width:80px"></input></td>
												<td class="center nobborder"><input type="button" value="&#8804 <?php echo $value ?>" onclick="document.getElementById('condition').value = 'le';document.getElementById('value').value = '<?php echo $value ?>';wizard_next();" style="width:80px"></input></td>
												<td class="center nobborder"><input type="button" value="&#8805 <?php echo $value ?>" onclick="document.getElementById('condition').value = 'ge';document.getElementById('value').value = '<?php echo $value ?>';wizard_next();" style="width:80px"></input></td>
											</tr>
											<?php } ?>
										</table>
									</td>
								</tr>
								<tr><td class="center nobborder"><input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/></td></tr>
							</table>
						</div>
						
<!-- ################## STEP 12: Monitor Interval ###################### -->
						<?php
						/* default values for "monitor" */
						$interval_list = array(
						    10,
						    15,
						    20,
						    30,
						    50,
						    100,
						    200,
						    300,
						    400,
						    500
						);
						?>
						<div id="wizard_12" style="display:none">
						<input type="hidden" name="interval" id="interval" value=""></input>
							<table class="transparent" width="100%">
								<!-- ##### interval ##### -->
								<tr>
									<th style="white-space: nowrap; padding: 5px;font-size:12px">
										<?php echo gettext("Monitor interval"); ?>
									</th>
								</tr>
								<tr><td class="center nobborder"><input type="button" value="Default" onclick="document.getElementById('interval').value = 'Default';wizard_next();" style="width:80px"></input></td></tr>
								<?php foreach ($interval_list as $value) { ?>
								<tr><td class="center nobborder"><input type="button" value="<?php echo $value ?>" onclick="document.getElementById('interval').value = '<?php echo $value ?>';wizard_next();" style="width:80px"></input></td></tr>
								<?php } ?>
								<tr><td class="center nobborder"><input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/></td></tr>
							</table>
						</div>
						
<!-- ################## STEP 13: Monitor Abolute ###################### -->
						<div id="wizard_13" style="display:none">
						<input type="hidden" name="absolute" id="absolute" value=""></input>
							<table class="transparent" width="100%">
								<!-- ##### absolute ##### -->
								<tr>
									<th style="white-space: nowrap; padding: 5px;font-size:12px">
										<?php echo gettext("Monitor absolute"); ?>
									</th>
								</tr>
								<tr><td class="center nobborder"><input type="button" value="Default" onclick="document.getElementById('absolute').value = 'Default';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" value="true" onclick="document.getElementById('absolute').value = 'true';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" value="false" onclick="document.getElementById('absolute').value = 'false';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_hide()"/>&nbsp;<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/></td></tr>
							</table>
						</div>

<!-- ################## STEP 14: Sticky ###################### -->
						<div id="wizard_14" style="display:none">
						<input type="hidden" name="sticky" id="sticky" value=""></input>
							<table class="transparent" width="100%">
								<tr>
									<th style="white-space: nowrap; padding: 5px;font-size:12px">
										<?php echo gettext("Sticky"); ?>
									</th>
								</tr>
								<tr><td class="center nobborder"><input type="button" value="None" onclick="document.getElementById('sticky').value = 'None';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" value="true" onclick="document.getElementById('sticky').value = 'true';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" value="false" onclick="document.getElementById('sticky').value = 'false';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/></td></tr>
							</table>
						</div>

<!-- ################## STEP 15: Sticky Different ###################### -->						
						<div id="wizard_15" style="display:none">
						<input type="hidden" name="sticky_different" id="sticky_different" value="<?php if ($level > 1) echo $rule->sticky_different ?>"></input>
							<table class="transparent" width="100%">
								<!-- sticky different -->
								<tr>
									<th style="white-space: nowrap; padding: 5px;font-size:12px">
										<?php echo gettext("Sticky different"); ?>
									</th>
								</tr>
								<tr><td class="center nobborder"><input type="button" value="None" onclick="document.getElementById('sticky_different').value = 'None';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" value="PLUGIN_SID" onclick="document.getElementById('sticky_different').value = 'PLUGIN_SID';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" value="SRC_IP" onclick="document.getElementById('sticky_different').value = 'SRC_IP';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" value="DST_IP" onclick="document.getElementById('sticky_different').value = 'DST_IP';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" value="SRC_PORT" onclick="document.getElementById('sticky_different').value = 'SRC_PORT';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" value="DST_PORT" onclick="document.getElementById('sticky_different').value = 'DST_PORT';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" value="PROTOCOL" onclick="document.getElementById('sticky_different').value = 'PROTOCOL';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" value="SENSOR" onclick="document.getElementById('sticky_different').value = 'SENSOR';wizard_next();" style="width:80px"></input></td></tr>
								<tr><td class="center nobborder"><input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/></td></tr>
							</table>
						</div>
						
<!-- ################## STEP 16: Other info ###################### -->						
						<div id="wizard_16" style="display:none">
						<table class="transparent">
							<tr>
								<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
									<?php echo gettext("Other"); ?>
								</th>
							</tr>
						
							<!-- ##### filename ##### -->
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("filename"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="width: 200px" name="filename" id="filename" value="" />
								</td>
							</tr>
						
							<!-- ##### username ##### -->
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("username"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="width: 120px" name="username" id="username" autocomplete="off" value="" />
								</td>
							</tr>
							
							<!-- ##### password ##### -->
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("password"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="password" style="width: 120px" name="password" id="password" autocomplete="off" value="" />
								</td>
							</tr>
							
							<tr>
								<td class="center nobborder" colspan="2" style="padding-top:10px">
									<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;
									<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/>&nbsp;
									<input type="button" id="button_next_16" value="<?php echo _("Next") ?>" onclick="wizard_next();"/>
								</td>
							</tr>
						</table>
						</div>
						
<!-- ################## STEP 17: Userdata ###################### -->						
						<div id="wizard_17" style="display:none">
						<table class="transparent">
							<tr>
								<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
									<?php echo gettext("User data"); ?>
								</th>
							</tr>
							<!-- ##### userdata 1 ##### -->
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("userdata1"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="width: 100%" name="userdata1" id="userdata1" value="" />
								</td>
							</tr>
						
							<!-- ##### userdata 2 ##### -->
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("userdata2"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="width: 100%" name="userdata2" id="userdata2" value="" />
								</td>
							</tr>
							
							<!-- ##### userdata 3 ##### -->
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("userdata3"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="width: 100%" name="userdata3" id="userdata3" value="" />
								</td>
							</tr>
						
							<!-- ##### userdata 4 ##### -->
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("userdata4"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="width: 100%" name="userdata4" id="userdata4" value="" />
								</td>
							</tr>
							
							<!-- ##### userdata 5 ##### -->
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("userdata5"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="width: 100%" name="userdata5" id="userdata5" value="" />
								</td>
							</tr>
						
							<!-- ##### userdata 6 ##### -->
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("userdata6"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="width: 100%" name="userdata6" id="userdata6" value="" />
								</td>
							</tr>
							
							<!-- ##### userdata 7 ##### -->
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("userdata7"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="width: 100%" name="userdata7" id="userdata7" value="" />
								</td>
							</tr>
							
							<!-- ##### userdata 8 ##### -->	
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("userdata8"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="width: 100%" name="userdata8" id="userdata8" value="" />
								</td>
							</tr>
								
							<!-- ##### userdata 9 ##### -->
							<tr>
								<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
									<?php echo gettext("userdata9"); ?>
								</td>
								<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
									<input type="text" style="width: 100%" name="userdata9" id="userdata9" value="" />
								</td>
							</tr>
							<tr>
								<td class="center nobborder" colspan="2" style="padding-top:10px">
									<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>&nbsp;
									<input type="button" value="<?php echo _("Back") ?>" onclick="wizard_back()"/>&nbsp;
									<input type="button" id="button_finish" value="<?php echo _("Finish") ?>" onclick="wizard_next();"/>
								</td>
							</tr>
						</table>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
</body>
</html>
