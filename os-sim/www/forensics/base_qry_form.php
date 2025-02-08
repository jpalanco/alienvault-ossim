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
*/
require ("base_conf.php");
require ("vars_session.php");
$_SESSION['norefresh'] = 1;
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/includes/base_action.inc.php");

include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/base_ag_common.php");
include_once ("$BASE_path/base_qry_common.php");

require_once ('av_init.php');
Session::logcheck("analysis-menu", "EventsForensics");

/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

$categories = GetPluginCategories($db);
$subcategories = GetPluginSubCategories($db,$categories);
$subcategories_json = json_encode($subcategories);

$cs = new CriteriaState("base_qry_main.php", "&amp;new=1&amp;submit=" . gettext("Query+DB"));
$cs->ReadState();

$_submit_param = ($_POST['mode'] != '') ? 'mode' : 'submit';
$ip_id = $_POST['remove_ip'];

if(isset($ip_id)) {
    $cs->criteria['ip_addr']->Clear($ip_id);
    echo json_encode(true);
    die();
}
/* This call can include many values. */
$submit = Util::htmlentities(ImportHTTPVar($_submit_param, VAR_DIGIT | VAR_PUNC | VAR_LETTER, array(
        gettext("Query DB"),
        //gettext("ADD TIME"),
        gettext("ADD Addr"),
        //gettext("ADD IP Field"),
        gettext("ADD TCP Port"),
        //gettext("ADD TCP Field"),
        gettext("ADD UDP Port"),
        //gettext("ADD UDP Field"),
        //_ADDICMPFIELD
)));

if ($submit == "TCP") {
    $cs->criteria['layer4']->Set("TCP");
}
if ($submit == "UDP") {
    $cs->criteria['layer4']->Set("UDP");
}
/*
if ($submit == "ICMP") {
    $cs->criteria['layer4']->Set("ICMP");
}
*/
if ($submit == gettext("no layer4")) {
    $cs->criteria['layer4']->Set("");
}
//if ($submit == gettext("ADD TIME") && $cs->criteria['time']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['time']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == gettext("ADD Addr") && $cs->criteria['ip_addr']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['ip_addr']->AddFormItem($submit, $cs->criteria['layer4']->Get());
//if ($submit == gettext("ADD IP Field") && $cs->criteria['ip_field']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['ip_field']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == gettext("ADD TCP Port") && $cs->criteria['tcp_port']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['tcp_port']->AddFormItem($submit, $cs->criteria['layer4']->Get());
//if ($submit == gettext("ADD TCP Field") && $cs->criteria['tcp_field']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['tcp_field']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == gettext("ADD UDP Port") && $cs->criteria['udp_port']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['udp_port']->AddFormItem($submit, $cs->criteria['layer4']->Get());
//if ($submit == gettext("ADD UDP Field") && $cs->criteria['udp_field']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['udp_field']->AddFormItem($submit, $cs->criteria['layer4']->Get());
//if ($submit == gettext("ADD ICMP Field") && $cs->criteria['icmp_field']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['icmp_field']->AddFormItem($submit, $cs->criteria['layer4']->Get());
if ($submit == gettext("ADD Payload") && $cs->criteria['data']->GetFormItemCnt() < $MAX_ROWS) $cs->criteria['data']->AddFormItem($submit, $cs->criteria['data']->Get());


// Show or hide forms
if ($cs->criteria['ip_addr']->GetFormItemCnt() > 1 || $cs->criteria['ip_addr']->criteria[0][7] != '')
{
    $hide_ip_criteria = 'false';
}
else
{
    $hide_ip_criteria = 'true';
}

if ($cs->criteria['data']->GetFormItemCnt() > 1 || $cs->criteria['data']->criteria[0][2] != '')
{
    $hide_payload_criteria = 'false';
}
else
{
    $hide_payload_criteria = 'true';
}

if ( $cs->criteria['sourcetype']->criteria != '' || ($cs->criteria['category']->criteria[0] != '' && $cs->criteria['category']->criteria[0] != '0') )
{
    $hide_tax_criteria = 'false';
}
else
{
    $hide_tax_criteria = 'true';
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo gettext("iso-8859-1") ?>"/>
<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
<link rel="stylesheet" type="text/css" href="/ossim/style/av_toggle.css"/>
<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
<script type="text/javascript" src="/ossim/js/av_toggle.js.php"></script>
<script>
    function addtocombo (myselect,text,value) {
        var elOptNew = document.createElement('option');
        elOptNew.text = text
        elOptNew.value = value
        try {
            myselect.add(elOptNew, null);
        } catch(ex) {
            myselect.add(elOptNew);
        }
    }

    function deleteall (myselect) {
        var len = myselect.options.length
        for (var i=len-1; i>=0; i--) myselect.remove(i)
    }

    function changesensor(filter) {
        combo = document.getElementById('sensor')
        deleteall(combo);
        for (var i=0; i<num_sensors; i++) {
            if (sensortext[i].match(filter)) {
                addtocombo (combo,sensortext[i],sensorvalue[i])
            }
        }
    }

    // Add IP/Port/Payload Buttons must be self target base_qry_form.php
    function adv_search_autosubmit(submit_value)
    {
        $('#PacketForm').attr("action", "base_qry_form.php");
        $('#PacketForm').attr("target", "");
        $('#mode').val(submit_value);
    }

    function remove_param(data) {
        $.post("base_qry_form.php",data,function( result ) {
            window.location.href ="base_qry_form.php";
        });
    }

    var categories = <?php echo $subcategories_json; ?>;
    $(document).ready(function()
    {
        $('.remove_row').on('click', function () {
            var id = $(this).data('ip_id');
            $(this).parent().remove();
            remove_param({remove_ip:id});
        });

        $('.av_toggle').av_toggle();
        $("#category-select").change(function() {
            var val = $(this).val();
            var tr = $("#subcategory-tr");
            var select = $("#subcategory-select");
            select.html("<option value=\"\"></option>");
            if (!val || !categories[$(this).val()]) {
                tr.addClass("hidden");
                return;
            }
            var subcats = categories[val];
            for (var subcat in subcats) {
                select.append("<option value=\""+subcat+"\">"+subcats[subcat]+"</option>");
            }
            tr.removeClass("hidden");
        });
    });
</script>

<style type="text/css">
input[type=text], select
{
    margin: 5px;
}

table
{
    margin: 0px 0px 0px 11px;
}
</style>
</head>
<body>
<form method="post" name="PacketForm" id="PacketForm" action="base_qry_main.php" style="margin:0 auto" target="main">
<input type='hidden' name="search" value="1" />
<input type='hidden' name="gbhide" value="1" />
<input type='hidden' name="mode"   value="" id="mode" />
<?php
echo '<TABLE WIDTH="95%" cellspacing=5 class="transparent query">
      <TR>
      <TD class="left uppercase"><B>' . gettext("Sensor") . ': </B></TD>
        <TD class="left">';
$cs->criteria['sensor']->PrintForm("","","");
echo '</TD></TR>';

echo '<TR>
      <TD class="left uppercase"><B>' . gettext("Event Time") . ':</B></TD>
      <TD class="left">';
$cs->criteria['time']->PrintForm("","","");
echo '</TD></TR>';

echo '<TR>
      <TD class="left uppercase"><B>Priority:</B></TD>
      <TD class="left uppercase">';
echo '<B>Risk: </B>';
$cs->criteria['ossim_risk_a']->PrintForm("","","");
echo '<B>Priority: </B>';
$cs->criteria['ossim_priority']->PrintForm("","","");
/* DEPRECATED
echo '<B>Type: </B>';
$cs->criteria['ossim_type']->PrintForm();
*/
echo '<BR><B>Asset: </B>';
$cs->criteria['ossim_asset_dst']->PrintForm("","","");
echo '<B>Reliability: </B>';
$cs->criteria['ossim_reliability']->PrintForm("","","");
echo '</TD></TR>';
echo '
</TABLE>
<ul id="zMenu" style="text-align:left">';
echo '
<p>    </p>
<li>
<ul style="padding-left:20px">
<!-- ************ IP Criteria ******************** -->
<P>

<div class="av_toggle uppercase" data-options-title="'._('IP Filter').'" data-options-hidden="'.$hide_ip_criteria.'">

<TABLE WIDTH="90%" BORDER=0 class="transparent query">';
echo '<TR><TD><B>' . gettext("Address") . ':</B>';
echo '    <TD class="left">';
$cs->criteria['ip_addr']->PrintForm("","","");
/* DEPRECATED
echo '<TR><TD><B>' . gettext("Misc") . ':</B>';
echo '    <TD>';
$cs->criteria['ip_field']->PrintForm();
*/
echo '
   <TR><TD><B>Layer-4:</B>
       <TD class="left">';
$cs->criteria['layer4']->PrintForm("","","");
echo '
   </TABLE>

</div>

</ul>
<p>  </p>
</li>';
if ($cs->criteria['layer4']->Get() == "TCP") {
    echo '
    <p></p>
<li>
      <ul style="padding-left:5px">
<!-- ************ TCP Criteria ******************** -->
<P>

<div class="av_toggle" data-options-title="'._('TCP Filter').'" data-options-hidden="false">

<TABLE WIDTH="90%" BORDER=0 class="transparent query uppercase">';
    echo '<TR><TD><B>' . gettext("Port") . ':</B>';
    echo '    <TD class="left">';
    // Set cnt = 1 when search error warning to restore the wrong search
    if ($cs->criteria['tcp_port']->criteria_cnt < 1) {
        $cs->criteria['tcp_port']->criteria_cnt = 1;
    }
    $cs->criteria['tcp_port']->PrintForm();
    /*
    echo '
  <TR>
      <TD VALIGN=TOP><B>' . gettext("Flags") . ':</B>';
    $cs->criteria['tcp_flags']->PrintForm();*/
    /*
    echo '<TR><TD><B>' . gettext("Misc") . ':</B>';
    echo '    <TD>';
    $cs->criteria['tcp_field']->PrintForm();*/
    echo '
</TABLE>

</div>

</ul>
<p>  </p>
</li>';
}
if ($cs->criteria['layer4']->Get() == "UDP") {
    echo '
      <p></p>
<li>
      <ul style="padding-left:5px">
<!-- ************ UDP Criteria ******************** -->
<P>

<div class="av_toggle" data-options-title="'._('UDP Filter').'" data-options-hidden="false">

<TABLE WIDTH="100%" BORDER=0 class="transparent query uppercase">';
    echo '<TR><TD><B>' . gettext("Port") . ':</B>';
    echo '    <TD class="left">';
    // Set cnt = 1 when search error warning to restore the wrong search
    if ($cs->criteria['udp_port']->criteria_cnt < 1) {
        $cs->criteria['udp_port']->criteria_cnt = 1;
    }
    $cs->criteria['udp_port']->PrintForm();
    /*
    echo '<TR><TD><B>' . gettext("Misc") . ':</B>';
    echo '    <TD>';
    $cs->criteria['udp_field']->PrintForm();*/
    echo '
</TABLE>

</div>

</ul>
<p>
  </p>
</li>';
}
if ($cs->criteria['layer4']->Get() == "ICMP") {
    echo '
        <p></p>
<li> <a href="#">' . gettext("ICMP Filter") . '</a>
      <ul>
<!-- ************ ICMP Criteria ******************** -->
<P>

<TABLE WIDTH="100%" BORDER=0 class="query uppercase">';
    echo '<TR><TD><B>' . gettext("Misc") . ':</B>';
    echo '    <TD>';
    $cs->criteria['icmp_field']->PrintForm();
    echo '
  </TD></TR>
</TABLE>
</ul>
<p>  </p>
</li>';
}
echo '
      <p></p>
<li>
<ul style="padding-left:20px">
<!-- ************ Payload Criteria ******************** -->
<P>

<div class="av_toggle uppercase" data-options-title="'._('Payload Filter').'" data-options-hidden="'.$hide_payload_criteria.'">

<TABLE WIDTH="90%" BORDER=0 class="transparent query uppercase">
  <TR>
      <TD class="left" style="width:5px"></TD>
      <TD class="left">';
$cs->criteria['data']->PrintForm("","","");
echo '
  </TR>
</TABLE>

</div>

</ul>

<ul style="padding-left:20px">
<!-- ************ Event Taxonomy Criteria ******************** -->
<P>

<div class="av_toggle uppercase" data-options-title="'._('Event Taxonomy Filter').'" data-options-hidden="'.$hide_tax_criteria.'">

<br>
<TABLE BORDER=0 class="transparent query uppercase tax">
  <TR>
      <TD class="left uppercase" style="padding-left:20px"><B>'._("Product Type").':</B></TD>
      <TD class="left">
        <select name="sourcetype"><option value=""></option>';
        $srctypes = GetSourceTypes($db);

        foreach ($srctypes as $srctype_id => $srctype_name) echo "<option value=\"$srctype_id\"".(($_SESSION["sourcetype"]==$srctype_id) ? " selected" : "").">" ._($srctype_name) ."</option>\n";
echo '
        </select>
        <br/>
      </TD>
  </TR>
  <TR>
      <TD class="left uppercase" style="padding-left:20px"><B>'._("Event Category").':</B></TD>
      <TD class="left">
        <select id="category-select" name="category[0]"><option value=""></option>';
        foreach ($categories as $idcat => $category) echo "<option value=\"$idcat\"".(($_SESSION["category"][0]!=0 && $_SESSION["category"][0]==$idcat) ? " selected" : "").">" . _($category) . "</option>\n";
echo '
        </select>
      </TD>
  </TR>';
$is_active = $_SESSION["category"][0] > 0;
echo '
  <TR id="subcategory-tr" class="'.($is_active ?: "hidden").'">
      <TD class="left uppercase" style="padding-left:20px"><B>'._("Event Sub Category").':</B></TD>
      <TD class="left">
        <select id="subcategory-select" name="category[1]"><option value=""></option>';
        if ($is_active && is_array($subcategories[$_SESSION["category"][0]]))
        {
            foreach ($subcategories[$_SESSION["category"][0]] as $idscat => $subcategory)
            {
                echo "<option value=\"$idscat\"".(($_SESSION["category"][1]!=0 && $_SESSION["category"][1]==$idscat) ? " selected" : "").">$subcategory</option>\n";
            }
        }
echo '
        </select>
      </TD>
  </TR>';
echo '
</TABLE>

</div>

</ul>

<p>  </p>
</li></ul>';
echo '<ul><INPUT TYPE="hidden" NAME="new" VALUE="1">';
echo '<P>
        <CENTER>
        <TABLE class="transparent" BORDER=0>
        <TR><TD>
            <FONT>';
echo '<CENTER><BR/><INPUT TYPE="submit" class="button" NAME="submit" VALUE="' . gettext("Query DB") . '"></CENTER>
             </FONT>
             </TD>
        </TR>
        </TABLE>
        </CENTER>
        </ul>';
?>
<!-- ************ JavaScript for Hiding Details ******************** -->
<script type="text/javascript">
// <![CDATA[

function showHide(){
    //from the LI tag check for UL tags:
    el = this.parentNode
    //Loop for UL tags:
    for(var i=0;i<el.childNodes.length;i++){
        temp = el.childNodes[i]
        if(temp && temp["tagName"] && temp.tagName.toLowerCase() == "ul"){
            //Check status:
            if(temp.style.display=="none"){
                temp.style.display = ""
            }else{
                temp.style.display = "none"
            }
        }
    }
    return false
}
// ]]>
</script>
<br><br>
</form>
</body>
</html>
