======
System
======
Retrieve information about the system. 

GET
===

DESCRIPTION
    Retrieve the system_id from a given system ip. 

URL STRUCTURE
    .. code-block:: guess

		https://192.168.5.118:7000/av/api/1.0/system/uuid/<system_ip>

    :version: API version.
    :system_ip: The system IP.

VERSIONS
    v1

ERRORS
    :404: No system IP

EXAMPLES
    * `GET systemid`_
    * `Redirect to documentation`_


Examples
========

GET systemid 
~~~~~~~~~~~~~~~~~~

    curl -u "admin:alien4ever" -i --insecure -H "Accept: application/json"  https://192.168.5.118:7000/av/api/1.0/system/uuid/192.168.230.5


Redirect to documentation
~~~~~~~~~~~~~~~~~~~~~~~~~

A GET with an HTTP Accept Header preferring HTML over JSON will redirect to the
on-line documentation.

    curl -u "admin:alien4ever" -i --insecure https://192.168.5.118:7000/av/api/1.0/system/uuid/192.168.230.5
