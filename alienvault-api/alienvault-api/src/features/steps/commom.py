import decorator
import os
import subprocess as sub
import requests
import urllib

def _decode_parameter(value):
    """Get BDD step parameter, redirecting to env var if start with $."""
    if value.startswith('$'):
        return os.environ.get(value[1:], '')
    else:
        return value


def _get_data_from_context(context):
    """Use context.text as a template and render against any stored state."""
    return context.result.getvalue()

@decorator.decorator
def resolve_table_vars( f, context,*parameters):
    context.resolved_table = []
    if hasattr(context,'table'):
        for row in context.table:
            inrow = []
            for column in row:
                if column.strip()[0] == '$':
                    inrow.append(context.alienvault[column[1:].strip()])
                else:
                    inrow.append (column)
            context.resolved_table.append (inrow)

    f(context,*parameters)

@decorator.decorator
def dereference_step_parameters_and_data(f, context, *parameters):
    """Decorator to dereference step parameters and data.

    This involves two parts:

        1) Replacing step parameters with environment variable values if they
        look like an environment variable (start with a "$").

        2) Treating context.text as a Jinja2 template rendered against
        context.template_data, and putting the result in context.data.

    """
    decoded_parameters = map(_decode_parameter, parameters)
    context.data = _get_data_from_context(context)
    f(context, *decoded_parameters)


def make_request(context, url , request_type='GET', is_login=False):
    context.result.truncate(0)
    urlparams = urllib.urlencode(context.urlparams)
    if urlparams != '' and request_type != 'POST':
        url =  url + "?" + urlparams

    if is_login:
        context.cookies = {}

    headers = {"Accept": "application/json"}  # force a JSON response to allow correct handling and avoid the internal
                                              # server error (500)
    if request_type == 'GET':
        r = requests.get(url.encode('ascii'), verify=False, headers=headers, cookies=context.cookies)
    elif request_type == 'POST':
        r = requests.post(url.encode('ascii'), verify=False, data=context.urlparams, headers=headers, cookies=context.cookies)
    elif request_type == 'PUT':
        r = requests.put(url.encode('ascii'), verify=False, headers=headers, cookies=context.cookies)
    elif request_type == 'DELETE':
        r = requests.delete(url.encode('ascii'), verify=False, headers=headers, cookies=context.cookies)
    else:
        return None

    context.result.write(r.text)
    context.resultheader = r.headers
    context.resultcode = r.status_code
    context.cookies = requests.utils.dict_from_cookiejar(r.cookies)
    context.urlparams = {}
    return r


