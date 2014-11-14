<?php
/**
* browser.inc
*
* Lightweight user agent sniffer class.
*
* Based on PHPSniffer (http://phpsniff.sourceforge.net/)
* and only supports the "basic" PHPSniffer features and sets the
* browser id, name, version, platform and os, based on the
* HTTP_USER_AGENT string.
*
*
*
* License:
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.

* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*
* @package    ossim-framework\Various
* @autor      Ivan Lutrov <ivan@lutrov.com>
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
*/


/**
*
* SAMPLE USAGE:
* 
* $ua = new Browser();
* echo "string=$ua->str\nid=$ua->id name=$ua->name ver=$ua->ver platform=$ua->platform os=$ua->os\n";
*/


class Browser {

   public $id = '';
   public $name = '';
   public $ver = '';
   public $platform = '';
   public $os = '';
   public $str = '';

   public $uainfo = array(
      'id' => '',
      'name' => '',
      'ver' => '',
      'platform' => '',
      'os' => '',
      'str' => ''
   );

   public $uastr = array(
      'accoona-ai-agent' => 'ac',
      'amaya' => 'am',
      'amiga-aweb' => 'aw',
      'amigavoyager' => 'av',
      'ask jeeves' => 'aj',
      'blogpulse' => 'bp',
      'browsex' => 'bx',
      'camino' => 'ca',
      'chimera' => 'ch',
      'crazy browser' => 'cz',
      'epiphany' => 'ep',
      'firebird' => 'fb',
      'firefox' => 'fx',
      'galeon' => 'ga',
      'gigabot' => 'gi',
      'googlebot' => 'gb',
      'hotjava' => 'hj',
      'ibrowse' => 'ib',
      'icab' => 'ic',
      'ichiro' => 'ir',
      'java' => 'jv',
      'k-meleon' => 'km',
      'konqueror' => 'kq',
      'krugle' => 'kr',
      'links' => 'li',
      'localcombot' => 'lc',
      'lynx' => 'lx',
      'microsoft internet explorer' => 'ie',
      'mj12bot' => 'mj',
      'mozilla' => 'mz',
      'mozilla firebird' => 'fb',
      'msie' => 'ie',
      'msnbot' => 'mb',
      'msnbot-media' => 'mm',
      'msnbot-products' => 'mp',
      'my agent' => 'ma',
      'ncsa mosaic' => 'mo',
      'netscape' => 'ns',
      'netscape6' => 'ns',
      'noxtrumbot' => 'nx',
      'omniweb' => 'ow',
      'opera' => 'op',
      'phoenix' => 'px',
      'psbot' => 'ps',
      'rufusbot' => 'rf',
      'safari' => 'sf',
      'sbider' => 'sb',
      'snapbot' => 'sp',
      'surveybot' => 'su',
      'technoratibot' => 'tb',
      'turnitinbot' => 'tt',
      'up.browser' => 'up',
      'w3c_validator' => 'wc',
      'wget' => 'wg',
      'yahoo-mmcrawler' => 'ym',
      'yahoo! slurp' => 'ys',
      'zyborg' => 'zb'
   );

   public $uaregex;

   public $mutant_uastr = array(
      'baiduspider' => '_b',
      'blogpulse' => '_p',
      'blogsnowbot' => '_n',
      'nextgensearchbot' => '_g',
   );

   public $mutant_uaregex;

   function __construct($str = null) {
      if (empty($str)) {
         if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $str = $_SERVER['HTTP_USER_AGENT'];
         } else {
            $str = getenv('HTTP_USER_AGENT');
         }
      }
      if (empty($str)) {
         return false;
      }
      $this->set_ua('str', $str);
      $this->get_uainfo();
   }

   function p($p = null) {
      if (empty($p)) {
         return $this->uainfo;
      } else {
         return $this->uainfo[strtolower($p)];
      }
   }

   function get_uainfo() {
      $regex_win = '/([^dar]win[dows]*)[\s]?([0-9a-z]*)[\w\s]?([a-z0-9.]*)/i';
      $regex_mac = '/(68[k0]{1,3})|(ppc mac os x)|([p\S]{1,5}pc)|(darwin)/i';
      $regex_os2 = '/os\/2|ibm-webexplorer/i';
      $regex_sunos = '/(sun|i86)[os\s]*([0-9]*)/i';
      $regex_irix = '/(irix)[\s]*([0-9]*)/i';
      $regex_hpux = '/(hp-ux)[\s]*([0-9]*)/i';
      $regex_aix = '/aix([0-9]*)/i';
      $regex_dec = '/dec|osfl|alphaserver|ultrix|alphastation/i';
      $regex_vms = '/vax|openvms/i';
      $regex_sco = '/sco|unix_sv/i';
      $regex_linux = '/x11|inux/i';
      $regex_bsd = '/(free)?(bsd)/i';
      $regex_amiga = '/amiga[os]?/i';
      $this->build_regex();
      if (preg_match_all($this->uaregex, $this->uainfo['str'], $results)) {
         $cnt = count($results[0]) - 1;
         $this->set_ua('id', $this->get_id($results[1][$cnt]));
         $this->set_ua('name', $results[1][$cnt]);
         $this->set_ua('ver', $results[2][$cnt] . $results[3][$cnt]);
      }
      if (preg_match_all($regex_win, $this->uainfo['str'], $match)) {
         $v1 = $match[2][count($match[0]) - 1];
         $v2 = $match[3][count($match[0]) - 1];
         if (stristr($v1, 'nt') && $v2 == 6.0) {
            $v1 = 'vista';
         } elseif (stristr($v1, 'nt') && $v2 == 5.2) {
            $v1 = 'xp';
         } elseif (stristr($v1, 'nt') && $v2 == 5.1) {
            $v1 = 'xp';
         } elseif (stristr($v1, 'nt') && $v2 == 5.01) {
            $v1 = '2000';
         } elseif ($v1 == '2000') {
            $v1 = '2000';
         } elseif (stristr($v1, 'nt') && $v2 == 5.0) {
            $v1 = '2000';
         } elseif (stristr($v1, '9x') && $v2 == 4.9) {
            $v1 = '98';
         } elseif (($v1 . $v2) == '16bit') {
            $v1 = '31';
         } else {
            $v1 .= $v2;
         }
         if (empty($v1)) {
            $v1 = 'win';
         }
         $this->set_ua('os', strtolower($v1));
         $this->set_ua('platform', 'win');
      } elseif (preg_match($regex_amiga, $this->uainfo['str'], $match)) {
         $this->set_ua('platform', 'amiga');
         if (stristr($this->uainfo['str'], 'morphos')) {
            $this->set_ua('os', 'morphos');
         } elseif (stristr($this->uainfo['str'], 'mc680x0')) {
            $this->set_ua('os', 'mc680x0');
         } elseif (stristr($this->uainfo['str'], 'ppc')) {
            $this->set_ua('os', 'ppc');
         } elseif (preg_match('/(amigaos [\.1-9]?)/i', $this->uainfo['str'], $match)) {
            $this->set_ua('os', $match[1]);
         }
      } elseif (preg_match($regex_os2, $this->uainfo['str'])) {
         $this->set_ua('os', 'os2');
         $this->set_ua('platform', 'os2');
      } elseif (preg_match($regex_mac, $this->uainfo['str'], $match)) {
         $this->set_ua('platform', 'mac');
         $os = !empty($match[1]) ? '68k' : '';
         $os = !empty($match[2]) ? 'osx' : $os;
         $os = !empty($match[3]) ? 'ppc' : $os;
         $os = !empty($match[4]) ? 'osx' : $os;
         $this->set_ua('os', $os);
      } elseif (preg_match($regex_sunos, $this->uainfo['str'], $match)) {
         $this->set_ua('platform', 'unix');
         if (!stristr('sun', $match[1])) {
            $match[1] = 'sun' . $match[1];
         }
         $this->set_ua('os', $match[1] . $match[2]);
      } elseif (preg_match($regex_irix, $this->uainfo['str'], $match)) {
         $this->set_ua('platform', 'unix');
         $this->set_ua('os', $match[1] . $match[2]);
      } elseif (preg_match($regex_hpux, $this->uainfo['str'], $match)) {
         $this->set_ua('platform', 'unix');
         $match[1] = str_replace('-', '', $match[1]);
         $match[2] = (int) $match[2];
         $this->set_ua('os', $match[1] . $match[2]);
      } elseif (preg_match($regex_aix, $this->uainfo['str'], $match)) {
         $this->set_ua('platform', 'unix');
         $this->set_ua('os', 'aix' . $match[1]);
      } elseif (preg_match($regex_dec, $this->uainfo['str'], $match)) {
         $this->set_ua('platform', 'unix');
         $this->set_ua('os', 'dec');
      } elseif (preg_match($regex_vms, $this->uainfo['str'], $match)) {
         $this->set_ua('platform', 'unix');
         $this->set_ua('os', 'vms');
      } elseif (preg_match($regex_sco, $this->uainfo['str'], $match)) {
         $this->set_ua('platform', 'unix');
         $this->set_ua('os', 'sco');
      } elseif (stristr($this->uainfo['str'], 'unix_system_v')) {
         $this->set_ua('platform', 'unix');
         $this->set_ua('os', 'unixware');
      } elseif (stristr($this->uainfo['str'], 'ncr')) {
         $this->set_ua('platform', 'unix');
         $this->set_ua('os', 'mpras');
      } elseif (stristr($this->uainfo['str'], 'reliantunix')) {
         $this->set_ua('platform', 'unix');
         $this->set_ua('os', 'reliant');
      } elseif (stristr($this->uainfo['str'], 'sinix')) {
         $this->set_ua('platform', 'unix');
         $this->set_ua('os', 'sinix');
      } elseif (preg_match($regex_bsd, $this->uainfo['str'], $match)) {
         $this->set_ua('platform', 'unix');
         $this->set_ua('os', $match[1] . $match[2]);
      } elseif (preg_match($regex_linux, $this->uainfo['str'], $match)) {
         $this->set_ua('platform', 'unix');
         $this->set_ua('os', 'linux');
      }
      if (strlen($this->id) == 0) {
         $this->build_mutant_regex();
         if (preg_match_all($this->mutant_uaregex, $this->uainfo['str'], $results)) {
            $cnt = count($results[0]) - 1;
            $this->set_ua('id', $this->get_id($results[1][$cnt]));
            $this->set_ua('name', $results[1][$cnt]);
         }
      }
   }

   function build_regex() {
      $uastr = '';
      while (list($k,) = each($this->uastr)) {
         if (!empty($uastr)) {
            $uastr .= '|';
         }
         $uastr .= $k;
      }
      $verstr = "[\/\sa-z(]*([0-9]+)([\.0-9a-z]+)?";
      $this->uaregex = "/($uastr)$verstr/i";
   }

   function build_mutant_regex() {
      $uastr = '';
      while (list($k,) = each($this->mutant_uastr)) {
         if (!empty($uastr)) {
            $uastr .= '|';
         }
         $uastr .= $k;
      }
      $this->mutant_uaregex = "/^($uastr).*\(/i";
   }

   function get_id($name) {
      return $this->uastr[strtolower($name)];
   }

   function set_ua($k, $v) {
      $k = strtolower($k);
      $v = strtolower($v);
      $this->uainfo[$k] = $v;
      switch ($k) {
         case 'id':
            $this->id = $v;
            break;
         case 'name':
            $this->name = $v;
            break;
         case 'ver':
            $this->ver = $v;
            break;
         case 'platform':
            $this->platform = $v;
            break;
         case 'os':
            $this->os = $v;
            break;
         case 'str':
            $this->str = $v;
            break;
      }
   }
}

?>