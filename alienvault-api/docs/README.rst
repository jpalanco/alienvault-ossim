REST API Blueprint
==================

This is an exploration into how best to create a REST API using `Python
<http://www.python.org>`_ (version 2.7) and the excellent micro-web framework
`Flask <http://flask.pocoo.org>`_. It aims to be a pedagogical blueprint rather
than a library or utility. A more prosaic and honest statement of the goal is to
provide a clean exposition *with code* of *my* current (but evolving) tastes in
the design and structure of a REST API built with Python and Flask.

The scope includes automatic testing, documentation, authentication, capability
switching, data formats, mime types, and unicode. As the focus is on REST API
structure and expression, the scope does *not* include things like ORM's and
templating engines.

See the `Wiki <https://bitbucket.org/tcorbettclark/rest-api-blueprint/wiki/Home>`_ for discussion and explanation. Otherwise the `repository
code <https://bitbucket.org/tcorbettclark/rest-api-blueprint/src>`_ is
authoritative.

Installation
------------

Note that this is not a library so much as an approach to be read and copied.
However there are clearly parts which are usefully referenced (e.g. the BDD
`steps <https://bitbucket.org/tcorbettclark/rest-api-
blueprint/src/tip/restapiblueprint/features/steps/rest.py>`_ or the `lib
<https://bitbucket.org/tcorbettclark/rest-api-blueprint/src/tip/restapiblueprint/lib>`_). This is possible by installing as a package and importing. It is
available on `pypi/rest-api-blueprint <http://pypi.python.org/pypi/rest-api-blueprint/0.1>`_ and can be installed with:

::

    pip install rest-api-blueprint

Status
------

Reasonably complete now. See `open issues <https://bitbucket.org/tcorbettclark
/rest-api-blueprint/issues?status=new&status=open>`_.

Quick tour
----------

Start the example app server:

::

    ~/code/rest-api-blueprint$ python runserver.py
     * Running on http://127.0.0.1:5000/
     * Restarting with reloader
    ...

Add person details with the `example app <https://bitbucket.org/tcorbettclark/rest-api-blueprint/wiki/ExampleApp>`_:

::

    ~/code/rest-api-blueprint$ curl -X PUT localhost:5000/v1/people/fred -H 'Content-Type: application/json' -d '{"email": "a@b.c"}'
    {
      "status": "ok"
    }

Retrieve person details with the `example app`_:

::

    ~/code/rest-api-blueprint$ curl -X GET localhost:5000/v1/people/fred -H 'Accept: application/json'
    {
      "status": "ok",
      "result": {
        "comment": null,
        "name": "fred",
        "email": "a@b.c"
      }
    }

Run the BDD tests (`BDD details <https://bitbucket.org/tcorbettclark
/rest-api-blueprint/wiki/AutomaticTesting>`_):

::

    ~/code/rest-api-blueprint/restapiblueprint$ behave
    Feature: Delete a person # features/delete_person.feature:1
      As an API client
      I want to be able to remove a person

      Background: Reset and have a valid user  # features/delete_person.feature:5

      Scenario: Cannot delete a person before they exist                 # features/delete_person.feature:11
        Given I am using version "v1"                                    # features/steps/all.py:14
        And I have an empty database                                     # features/steps/all.py:19
        And I am a valid API user                                        # features/steps/all.py:27
        And I use an Accept header of "application/json"                 # features/steps/all.py:32
        When I send a DELETE request to "people/fred"                    # features/steps/all.py:101
        Then the response status should be "404"                         # features/steps/all.py:109
        And the JSON at path "status" should be "error"                  # features/steps/all.py:119
        And the JSON at path "message" should be "Person does not exist" # features/steps/all.py:119

      Scenario: Delete a person                                          # features/delete_person.feature:17
        Given I am using version "v1"                                    # features/steps/all.py:14
    ...

Make the API docs (`Doc details <https://bitbucket.org/tcorbettclark/rest-api-blueprint/wiki/ApiDocumentation>`_):

::

    ~/code/rest-api-blueprint$ ./make_apidocs.sh
    Making output directory...
    Running Sphinx v1.1.3
    loading pickled environment... not yet created
    building [html]: targets for 2 source files that are out of date
    updating environment: 2 added, 0 changed, 0 removed
    reading sources... [100%] people
    looking for now-outdated files... none found
    pickling environment... done
    checking consistency... done
    preparing documents... done
    writing output... [100%] people
    writing additional files... search
    copying static files... done
    dumping search index... done
    dumping object inventory... done
    build succeeded.
    Copying ansi stylesheet... done

Be redirected to the on-line docs:

::

    ~/code/rest-api-blueprint$ curl -X GET localhost:5000/v1/people/fred
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
    <title>Redirecting...</title>
    <h1>Redirecting...</h1>
    <p>You should be redirected automatically to target URL: <a href="static/apidocs/people.html">static/apidocs/people.html</a>.  If not click the link.

Interacting using `Slumber <http://slumber.in>`_:

::

    >>>import slumber
    >>>api=slumber.API('http://localhost:5000/v1/', append_slash=False)
    >>>api.people.tim.put({"email": "a@b.c"})
    True

    >>>api.people.tim.get()
    {u'result': {u'comment': None, u'email': u'a@b.c', u'name': u'tim'}, u'status': u'ok'}

To provide a template packaged structure, everything is packaged using
`distribute <http://packages.python.org/distribute/>`_.

To run the tests:

::

    python setup.py nosetests

To build a package for distribution and installation with pip etc:

::

    python setup.py sdist

The package is in the ``dist/`` directory, and can be installed with

::

    pip install rest-api-blueprint-0.1.tar.gz

To install during development:

::

    python setup.py develop

or

::

    pip install -e .

(which will also install any dependent packages.)

What's next?
------------

Intrigued? Read the `Wiki`_ and check out the `code
<https://bitbucket.org/tcorbettclark/rest-api-blueprint/src>`_.

Please send me feedback, raise bugs or requests using the bitbucket Issue
Tracker, or clone and improve (ideally with create pull requests) as per the
permissive BSD 2-Clause `license <https://bitbucket.org/tcorbettclark/rest-api-
blueprint/src/tip/LICENSE>`_.
