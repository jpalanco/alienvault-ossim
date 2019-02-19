<?php
/**
* Webservice.inc
*
* File Webservice.inc is used to:
*   - Keep track of all the ticketing web services where the tickets are going to be related to.
*
* This file includes one class:
*   - Webservice
*
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
* @package    ossim-framework\Webservice
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

class Webservice{
       
    /**#@+
    * Attributes
    */
    
    /**
    * @var string  Web Service ID
    * @access protected 
    */
    protected $id;
    
    /**
    * @var string  Contexts associated to Web Service
    * @access protected 
    */
    protected $ctx;

    /**
    * @var string  Web Service name 
    * @access protected 
    */
    protected $name;
    
    /**
    * @var string  Web Service description
    * @access protected 
    */
    protected $descr;

    /**
    * @var string  Web Service type
    * @access protected 
    */
    protected $type;
    
    
    /**
    * @var string  Web Service source
    * @access protected 
    */
    protected $source;
    
    
    /**
    * @var string  Web Service url address
    * @access protected 
    */
    protected $url;
    
    
    /**
    * @var string  Web Service namespace
    * @access protected 
    */
    protected $namespace;
    
    
    /**
    * @var string  Web Service user
    * @access protected 
    */
    protected $user;
    
    
    /**
    * @var string  Web Service password
    * @access protected 
    */
    protected $pass;


    /**
    * @var int  Total number of Web Services
    * @access protected 
    */
    protected $count;
    
            
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $id            Webservice id
    * @param string  $ctx           Webservice ctx
    * @param string  $name          Webservice name
    * @param string  $descr         Webservice description
    * @param string  $type          Webservice type
    * @param string  $source        Webservice source
    * @param string  $url           Webservice url
    * @param string  $namespace     Webservice namespace
    * @param string  $user          Webservice user
    * @param string  $pass          Webservice password
    */

    public function __construct($id, $ctx, $name, $descr, $type, $source, $url, $namespace, $user, $pass, $count)
    {
        $this->id        = $id;
        $this->ctx       = $ctx;
        $this->name      = $name;
        $this->descr     = $descr;
        $this->type      = $type;
        $this->source    = $source;
        $this->url       = $url;
        $this->namespace = $namespace;
        $this->user      = $user;
        $this->pass      = $pass;
        $this->count     = $count;
    }
    
    
    /**
    * This function returns the Webservice id
    *
    * @return string
    */
    public function get_id()
    {
        return $this->id;
    }
    
    /**
    * This function returns the Webservice ctx
    *
    * @return string
    */
    public function get_ctx()
    {
        return $this->ctx;
    }
    
    /**
    * This function returns the Webservice name
    *
    * @return string
    */
    public function get_name()
    {
        return $this->name;
    }
    
    /**
    * This function returns the Webservice description
    *
    * @return string
    */
    public function get_descr()
    {
        return $this->descr;
    }
    
    /**
    * This function returns the Webservice type
    *
    * @return string
    */
    public function get_type()
    {
        return $this->type;
    }
    
    /**
    * This function returns the Webservice source
    *
    * @return string
    */
    function get_source()
    {
        return $this->source;
    }
    
    /**
    * This function returns the Webservice url
    *
    * @return string
    */
    public function get_url()
    {
        return $this->url;
    }
    
    /**
    * This function returns the Webservice namespace
    *
    * @return string
    */
    public function get_namespace()
    {
        return $this->namespace;
    }
    
    /**
    * This function returns the Webservice user
    *
    * @return string
    */
    public function get_user()
    {
        return $this->user;
    }
    
    /**
    * This function returns the Webservice pass
    *
    * @return string
    */
    public function get_pass()
    {
        return $this->pass;
    }

    /**
    * This function returns the total of webservices
    * @return int
    */
    public function get_count()
    {
        return $this->count;
    }

    public function get_list($conn, $args = '') 
    {
        $list = array(
            'status' => 'true', 
            'data'   => array()
        );

        $ctxs = Session::get_ctx_where();
        if ($ctxs != '')
        {
            $ctxs = " AND ctx in ($ctxs)";
        }

        $iffoundrows = preg_match("/LIMIT/i", $args) ? TRUE : FALSE;

        $select      = "*, HEX(id) AS id, HEX(ctx) AS ctx";
        $select      = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS ". $select : $select;

        $query       = ossim_query("SELECT $select FROM webservice WHERE 1=1 $ctxs $args");

        if (!$rs = $conn->Execute($query)) 
        {
            $list['status'] = FALSE;
        } 
        else 
        {
            $foundrows = $rs->_numOfRows;

            if ($iffoundrows) 
            {
                $rf = $conn->Execute("SELECT FOUND_ROWS() as total");
                
                if ($rf)
                {
                    $foundrows = $rf->fields["total"];
                }
            }

            $_data = array();
            
            while (!$rs->EOF) 
            {
                $_data[] = new self(
                    $rs->fields["id"],
                    $rs->fields["ctx"], 
                    $rs->fields["name"], 
                    $rs->fields["descr"], 
                    $rs->fields["type"], 
                    $rs->fields["source"], 
                    $rs->fields["url"], 
                    $rs->fields["namespace"], 
                    $rs->fields["user"], 
                    $rs->fields["pass"], 
                    $foundrows
                );

                $rs->MoveNext();
            }

            $list['data'] = $_data;
        }
        
        return $list;
    }


    public static function send_ticket_to_platform($conn, $incident_id, $description)
    {
        $conf = $GLOBALS['CONF'];

        if (!$conf)
        {
            $conf            = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }
        
        $ctx        = Session::get_default_ctx();

        $w_list     = self::get_list($conn, " AND ctx = UNHEX('$ctx') AND source = 'ticket'");

        if($w_list['status'])
        {
            $ws_id = array();
            foreach ($w_list['data'] as $ws)
            {
                $ws_id[] = $ws->get_id();
            }

            $ws_id = implode(',', $ws_id);
        }
        
        if(empty($ws_id))
        {
            return FALSE;
        }

        $ticket = Incident::get_object($conn, $incident_id);

        if($ticket === FALSE)
        {
            return FALSE;
        }

        $data = array(
            'ws_id'       => $ws_id,
            'ticket_id'   => $incident_id,
            'title'       => $ticket->get_title(),
            'date'        => $ticket->get_date(),
            'submitter'   => $ticket->get_submitter(),
            'in_charge'   => $ticket->get_in_charge(),
            'description' => Util::clean_wiki_tags($description),
            'status'      => $ticket->get_status(),
            'type'        => $ticket->get_ref(),
            'priority'    => $ticket->get_priority(),
            'action'      => 'insert'            
        );


        $data_to_send  = json_encode($data);

        /* get the port and IP address of the frameworkd */
        $address = '127.0.0.1';
        $port    = $conf->get_conf("frameworkd_port");
        $timeout = array('sec' => 5, 'usec' => 0);

        $socket  = socket_create(AF_INET, SOCK_STREAM, 0);

        if ($socket < 0) 
        {
            $msgerr = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
            return FALSE;
        }

        /* connect */
        socket_set_block($socket);      
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$timeout);
        socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,$timeout);

        $result = @socket_connect($socket, $address, $port);        
        if (!$result) 
        {
            $msgerr = _("Can't connect with frameworkd")." ($address:$port)";
            return FALSE;
        }

        /* Send data to framework */
        $in  = 'ws ws_data=' .$data_to_send . "\n";       

        if (socket_write($socket, $in, strlen($in)) === FALSE)
        {
            return FALSE;
        }

        return TRUE;
    }
}

/* End of file webservice.inc */
/* Location: ../include/classes/webservice.inc */
