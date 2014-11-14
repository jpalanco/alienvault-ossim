=======
Sensors
=======

Add, remove, or update the entry for a given sensor

GET
===

DESCRIPTION
    Retrieve information on a given sensor

URL STRUCTURE
    .. code-block:: guess

        https://192.168.5.118:7000/av/api/1.0/sensors
		
    :version: API version.
    :sensor_id: Sensor ID

VERSIONS
    v1

ERRORS
    :404: No sensor of that sensor_id

EXAMPLES
    * `GET sensorlist`_
    * `Redirect to documentation`_


Examples
========

GET sensorlist 
~~~~~~~~~~~~~~~~~~

    .. command-output:: curl -u "admin:alien4ever" -i --insecure -H "Accept: application/json"  https://192.168.5.118:7000/av/api/1.0/sensors


Redirect to documentation
~~~~~~~~~~~~~~~~~~~~~~~~~

A GET with an HTTP Accept Header preferring HTML over JSON will redirect to the
on-line documentation.

    .. command-output:: curl -u "admin:alien4ever" -i --insecure https://192.168.5.118:7000/av/api/1.0/sensors
