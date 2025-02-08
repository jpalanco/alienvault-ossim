<?php
require_once __DIR__ . '/provider.inc';

class CentralConsoleProvider extends Provider
{
    public function status()
    {
        return $this->conn->do_request($this->common_url . '/status', null, 'GET');
    }

    public function connect($token)
    {
        return $this->conn->do_request($this->common_url . '/connect', array("token" => $token), 'POST');
    }

    public function disconnect()
    {
        return $this->conn->do_request($this->common_url . '/disconnect', null, 'POST');
    }
}
