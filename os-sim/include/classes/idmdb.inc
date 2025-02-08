<?php
/**
 * idmdb.inc
 *
 * File idmdb.inc is used to:
 *   - MongoDB connection and queries
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
 * @package    ossim-framework\Assets
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */


class Idmdb
{
    public $error;

    private $mongo_db;

    private $mdb;


    /**
     * Idmdb constructor.
     */
    public function __construct()
    {
        // Connect to Mongo
        $this->error = "";
        $this->mdb = NULL;
        $this->mongo_db = "inventory";

        if (!class_exists('MongoDB\Driver\Manager'))
        {
            $this->error = _("MongoDB\Driver\Manager class is not installed");
        }
        else
        {
            try
            {
                $this->mdb = new MongoDB\Driver\Manager("mongodb://localhost:27017/".$this->mongo_db);
            }
            catch (Exception $e)
            {
                $this->error = _("Cannot connect to MongoDB"). " ".$e->getMessage();
            }

            if (!$this->error && !$this->mdb)
            {
                $this->error = _("Cannot connect to MongoDB");
            }
        }
    }

    /**
     * Get the latest error message
     *
     *@return string
     */
    public function get_error(){
        return $this->error;
    }

    /**
     * This function returns FALSE if we cannot connect to MongoDB
     *
     * @return bool
     */
    public function is_available()
    {
        return (!empty($this->get_error())) ? FALSE : TRUE;
    }

    /**
     *  Get historic collections
     * @param $filter string Regex to filter collections by name
     *
     * @return array
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function get_collections($filter = '')
    {
        $command = Array (
            "listCollections" => 1,
            "nameOnly" => true
        );

        //Set filter
        if (!empty($filter)){
            $command["filter"] = Array(
                "name" => new MongoDB\BSON\Regex($filter)
            );
        }

        $collections = array();

        try {
            $command_obj = new MongoDB\Driver\Command($command);
            $res = $this->mdb->executeCommand($this->mongo_db, $command_obj);

            $collections = array();

            foreach($res as $collection) {
                $collections[$collection->name] = json_decode(json_encode($collection), true);
            }

            ksort($collections);
        }
        catch (Exception $e)
        {
            $this->error = _("Mongo command (listCollections) cannot be executed"). " ".$e->getMessage();
        }

        return $collections;
    }

    /**
     * This function returns the host properties (latest update) given a host ID and timestamp
     * @param $id           string Host ID
     * @param $timestamp    string Timestamp (For instance, 2020-07-14 12:24:03)
     * @return array|bool
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function get_properties ($id, $timestamp)
    {
        if (empty($id) || empty($timestamp))
        {
            return FALSE;
        }

        // Get collections
        $d_filter = array();
        $dates = array();
        $from = -7;

        while ($from<=0){
            $date = gmdate("Ymd",strtotime("$timestamp $from day GMT"));
            array_push($dates, $date);
            $from++;
        }

        $d_filter = "historic(".implode("|", $dates).")";

        $collections = $this->get_collections($d_filter);


        //Search by Host ID in the historic collections
        $historic_by_host = array();

        $h_filter = array (
            "host_id" => new MongoDB\BSON\Binary(pack("H*",$id), MongoDB\BSON\Binary::TYPE_UUID)
        );

        $options = array(
            "sort" => array(
                "date" => 1
            )
        );

        foreach ($collections as $collection){
            try {

                $query = new MongoDB\Driver\Query($h_filter, $options);
                $historic = $this->mdb->executeQuery('inventory.'.$collection['name'], $query)->toArray();

                foreach($historic as $h_data){
                    array_push($historic_by_host, $h_data);
                }
            }
            catch (Exception $e)
            {
                $this->error = _("Mongo query cannot be executed"). " ".$e->getMessage();
            }
        }

        $results = FALSE;
        if (!empty($historic_by_host)){
            //Latest update for the host data
            $results = self::get_latest_properties($historic_by_host);
        }

        return $results;
    }

    /**
     * This function returns an array with the latest properties for the host data
     *
     * @param $data array Historic host data
     * @return array
     */
    protected static function get_latest_properties($data){
        $results = array(
            "date"     => '',
            "hostname" => '',
            "mac"      => '',
            "ip"       => '',
            "ctx"      => '',
            "service"  => array(),
            "username" => array()
        );

        foreach ($data as $rs)
        {
            if (!empty($rs->date))
            {
                $date = $rs->date->jsonSerialize();
                $results["date"] = gmdate("Y-m-d H:i:s", $date['$timestamp']['t']);
            }

            if (!empty($rs->hostname))
            {
                $results["hostname"] = $rs->hostname;
            }

            if (!empty($rs->ip))
            {
                $results["ip"] = @inet_ntop($rs->ip->getData());
            }

            if (!empty($rs->ctx))
            {
                $results["ctx"] = strtoupper(bin2hex($rs->ctx->getData()));
            }

            if (!empty($rs->mac))
            {
                $results["mac"] = preg_replace("/(..)(..)(..)(..)(..)(..)/","$1:$2:$3:$4:$5:$6", bin2hex($rs->mac->getData()));
            }

            if (!empty($rs->ctx))
            {
                $results["ctx"] = strtoupper(bin2hex($rs->ctx->getData()));
            }

            if (!empty($rs->service))
            {
                $results["service"] = json_decode($rs->service, TRUE);
            }

            if (!empty($rs->username))
            {
                $results["username"] = json_decode($rs->username, TRUE);
            }
        }

        return $results;
    }
}

/* End of file idmdb.inc */
/* Location: ../include/classes/idmdb.inc */
