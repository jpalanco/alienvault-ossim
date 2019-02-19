======
Doctor
======
Check the system for misconfigurations, hardware failures and other issues.

GET
===

DESCRIPTION
    Retrieve the output from the Doctor subsystem

URL STRUCTURE
    .. code-block:: guess

		https://192.168.5.118:7000/av/api/1.0/doctor/[component_id]

    :version: API version
    :component_id: component unique identification number or empty to run on all registered components

VERSIONS
    v1

ERRORS
    :404: Component id does not exist
    :500: The Doctor returns an internal error

EXAMPLES
    * `GET`_
    * `GET component_id`_
    * `Redirect to documentation`_


Examples
========

GET
~~~

    curl -u "admin:alien4ever" -i --insecure -H "Accept: application/json"  https://192.168.230.22:7000/av/api/1.0/doctor/


GET component_id
~~~~~~~~~~~~~~~~

    curl -u "admin:alien4ever" -i --insecure -H "Accept: application/json"  https://192.168.230.22:7000/av/api/1.0/doctor/9b1df310-4cb1-32c9-bce3-5e4201bcc410


Redirect to documentation
~~~~~~~~~~~~~~~~~~~~~~~~~

A GET with an HTTP Accept Header preferring HTML over JSON will redirect to the
on-line documentation.

    curl -u "admin:alien4ever" -i --insecure https://192.168.230.22:7000/av/api/1.0/doctor/9b1df310-4cb1-32c9-bce3-5e4201bcc410
