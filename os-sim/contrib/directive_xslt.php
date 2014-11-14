<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('av_init.php');
Session::logcheck("MenuCorrelation", "CorrelationDirectives");
?>

<?php
require_once ('ossim_db.inc');
$level = $_GET["level"];
$directive_id = $_GET["directive"];
$array_params = array(
    'level' => $level,
    'directive_id' => $directive_id
);
$db = new ossim_db();
$conn = $db->connect();
$XML_FILE = '/etc/ossim/server/directives.xml';
if (version_compare(PHP_VERSION, '5', '>=') && extension_loaded('xsl')) require_once ('xslt-php4-to-php5.php');
$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	  <xsl:output method="html" version="4.0" encoding="ISO-8859-1" indent="yes" 
	       omit-xml-declaration="yes" media-type="text/html"/>
	  
	  <xsl:param name="level" select="1" />
	  <xsl:param name="directive_id" select="1" />
	  
	  <xsl:template match="/">
	      <html>
	      <head>
	          <title> Directives Editor </title>
                  <META HTTP-EQUIV="Pragma" CONTENT="no-cache"/>
                  <link rel="stylesheet" href="../style/style.css"/>
                  <link rel="stylesheet" href="../style/directives.css"/>
              </head>

	      <script language="JavaScript1.5" type="text/javascript">
	      &lt;!--

	      function Menus(Objet)
	      {
	      	VarDIV=document.getElementById(Objet);
		if(VarDIV.className=="menucache") {
		    VarDIV.className="menuaffiche";
		} else {
		    VarDIV.className="menucache";
	    	}
	      }	
	      //-->
              </script>
		
              <body>
	          <h1 align="center">Directive view</h1>
		  <xsl:apply-templates select="/directives/directive[@id=$directive_id]" />
	      </body>
	      </html>
	  </xsl:template>
	  
          <xsl:template match="directive">
	          <table align="center">
		  <tr><xsl:element name="th">
		      <xsl:attribute name="colspan">
		          <xsl:value-of select="$level + 12" />
		      </xsl:attribute>
		      Rules (Directives <xsl:value-of select="@name" />)
		      </xsl:element>
		  </tr>
		  <tr><th colspan="$level"></th>
		      <th>Name</th>
		      <th>Priority</th><th>Reliability</th>
		      <th>Time_out</th><th>Occurences</th>
		      <th>From</th><th>To</th>
		      <th>Port_from</th><th>Port_to</th>
		      <th>Plugin_ID</th><th>Plugin_SID</th>
		  </tr>
	          <xsl:apply-templates select=".//rule"/>
		  </table>
          </xsl:template>

	  <xsl:template match="rule">
	      <tr><xsl:element name="td">
	          <xsl:attribute name="colspan">
		     <xsl:value-of select="$level" />
		  </xsl:attribute>
	          </xsl:element>
	          <td><xsl:value-of select="@name" /></td>
		  <td><xsl:value-of select="@priority" /></td>
		  <td><xsl:value-of select="@reliability" /></td>
		  <td><xsl:value-of select="@time_out" /></td>
		  <td><xsl:value-of select="@occurence" /></td>
		  <td><xsl:value-of select="@from" /></td>
		  <td><xsl:value-of select="@to" /></td>
		  <td><xsl:value-of select="@port_from" /></td>
		  <td><xsl:value-of select="@port_to" /></td>
		  <td id="plugin_id"><xsl:value-of select="@plugin_id" /></td>
		  <td id="plugin_sid"><xsl:value-of select="translate(@plugin_sid,\',\',\' \')" /></td>
	      </tr>
	     <!-- <xsl:apply-templates select="//rule" /> -->
	  </xsl:template>

	  </xsl:stylesheet>';
$argument = array(
    '/_xsl' => $xsl
);
$xh = xslt_create();
$html = xslt_process($xh, $XML_FILE, 'arg:/_xsl', NULL, $argument, $array_params);
xslt_free($xh);
print $html;
?>
