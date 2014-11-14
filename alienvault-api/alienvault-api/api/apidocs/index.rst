=========
Resources
=========

.. toctree::
   :maxdepth: 2

   sensors
   system
   events
   messages
   apps

General notes
=============

This is the REST API for managing and retrieving Alienvault USM information

URL template
------------

The URLs all follow pattern::

    /av/api/<version>/<resource>/<optional resource_id>?<optional_args>

Authentication
--------------

TODO: explain once authentication is implemented.

Data formatting
---------------

Only `JSON`_ is supported. The is reflected by an HTTP header of ``Content-Type:
application/json``. As per `rfc4627`_, JSON is always encoded Unicode with a
default encoding of UTF-8. So it is fine to include non-ASCII in the messages.

For maximum compatibility, normalize to http://unicode.org/reports/tr15 (Unicode
Normalization Form C) (NFC) before UTF-8 encoding.

Date format
-----------

All dates passed to and from the API are strings in the following format::

    2012-02-09 15:06 +00:00
    2012-02-09 15:06:31 +01:00
    2012-02-09 15:06:31.428 -03:00

Error handling
--------------

Errors are indicated using standard `HTTP error codes`_. Additional information
is usually included in the returned JSON. Specific meanings for the error codes
are given below.

TODO: list them

.. _HTTP error codes: http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
.. _JSON: http://json.org
.. _rfc4627: http://www.ietf.org/rfc/rfc4627.txt
