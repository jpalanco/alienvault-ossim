"""
    Steps related to ossec functions
"""

from nose.tools import assert_equal,assert_not_equal
import behave
import uuid
import json

@behave.given(u'I create an ossec agent in sensor variable "{var_uuid}" with IP "{st_ip}", name variable "{var_agent_name}" and store agent_id in "{var_id}"')
def given_add_ossec_agent(context, var_uuid, st_ip, var_agent_name, var_id):
    """
        Create a local ossec agent with 
    """
    u = uuid.UUID(context.alienvault[var_uuid])
    url = "https://127.0.0.1:40011/av/api/1.0/sensor/" + str(u) + "/ossec/agent"
    context.urlparams['agent_name'] = var_agent_name
    context.urlparams['agent_ip'] = st_ip 
    context.execute_steps(unicode("When I send a PUT request to url \"%s\"" % url))
    result = context.result.getvalue()
    print ("CRG %s" % result)

    assert context.resultcode == 200, "Can't create ossec agent"
    try:
        j = json.loads(result)
        context.alienvault[var_id] = j['data']['agent_detail']['id']
    except KeyError:
        assert False, "Can't obtain agent_id"
    # Clear the params
    context.urlparams = {}
    
    
    
    
    
    
    
    

