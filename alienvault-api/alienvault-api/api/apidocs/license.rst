=======
license
=======
Provides licenses actions to register the appliances.

trial
=====

DESCRIPTION
    Obtain the trial license to register the appliance

URL STRUCTURE
    .. code-block:: guess

		https://<server_ip>:4011/av/api/<version>/apps/license/trial?email=<email>

    :server_ip: The Server IP.
    :version: API version.
    :email: The registered email.

VERSIONS
    v1

ERRORS
    :404: No email

EXAMPLES
    * `trial example`_
    * `Redirect to documentation`_


pro
===

DESCRIPTION
    Obtain the pro license to register the appliance

URL STRUCTURE
    .. code-block:: guess

		https://<server_ip>:4011/av/api/<version>/apps/license/pro?key=<key>

    :server_ip: The Server IP.
    :version: API version.
    :key: The professional key.

VERSIONS
    v1

ERRORS
    :404: No key

EXAMPLES
    * `pro example`_
    * `Redirect to documentation`_


Examples
--------

trial example
~~~~~~~~~~~~~~~~~~

    curl -u "admin:admin" -i --insecure -H "Accept: application/json"  https://127.0.0.1:40011/av/api/1.0/apps/license/trial?email=test@test.com
    .. command-output:: curl -u "admin:admin" -i --insecure -H "Accept: application/json"  https://127.0.0.1:40011/av/api/1.0/apps/license/trial?email=test@test.com


pro example
~~~~~~~~~~~~~~~~~~

    curl -u "admin:admin" -i --insecure -H "Accept: application/json"  https://127.0.0.1:40011/av/api/1.0/apps/license/pro?key=test@test.com
    .. command-output:: curl -u "admin:admin" -i --insecure -H "Accept: application/json"  https://127.0.0.1:40011/av/api/1.0/apps/license/pro?email=test@test.com


Redirect to documentation
~~~~~~~~~~~~~~~~~~~~~~~~~

A GET with an HTTP Accept Header preferring HTML over JSON will redirect to the
on-line documentation.
