from behave import then
import json


from commom import dereference_step_parameters_and_data
@then ('JSON key "{key}" has the value "{s}"')
@dereference_step_parameters_and_data
def then_json_has_value (context,key,s):
    j = json.loads(context.result.getvalue())
    print (j)
    obj = j
    for path in key.split("."):
        assert obj.get(path) is not None,"Bad key"
        obj = obj.get(path)
    json_s = json.loads(s)
    assert json_s == obj, "JSON %s:  Item [%s] = %s not found" % (str(j),key,(str(json_s)))
