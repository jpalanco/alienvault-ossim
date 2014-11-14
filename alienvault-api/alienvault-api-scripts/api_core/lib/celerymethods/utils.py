import time
from celery.utils.log import get_logger
from celery.task.control import inspect
import ast
from celery.utils.log import get_logger
from ansiblemethods.system.system import reconfigure, ansible_get_process_pid
logger = get_logger("celery")

class JobResult():
    def __init__(self, result, message, log_file):
        self.result = result
        self.message = message
        self.log_file = log_file
    @property
    def serialize(self):
        return {'result':self.result, 'message':self.message, 'log_file':self.log_file}

def exist_task_running(task_type, current_task_request, param_to_compare=None, argnum=0):
    """Check if there is any task of type 'task_type' running for the given system. 
       If the param_to_compare is None, returns only if there is a task of type <task_type> running. 
       In order to find a task running in a <param_to_compare>, you should specify, which argument of the
       task represent the <param_to_compare>, for example:
       If the task was launched by running: alienvualt_reconfigure("192.168.5.134") -> args[0] will be 
       the system ip, so you should specify argnum=0

       Args:
         task_type (str): The kind of task to look for (usually the method name)
         current_task_request(): The current task request (current_task.request from the caller)
         param_to_compare (str or None): Parameter to compare whithin the task, for example the system ip or the system id. 
         argnum (int): The argument number where we can find the system ip if needed.
       Returns:
         rt (True or False): True when a task matching the given criteria is running, false otherwise.
    """
    rt = True
    try:
        # Get the current task_id
        current_task_id= current_task_request.id #alienvault_reconfigure.request.id
        i = inspect()
        current_task_start_time = time.time()
        task_list = []
        # Retrieve the list of active tasks. 
        active_taks = i.active()
        for node, tasks_list in active_taks.iteritems():
            for task in tasks_list:
                # Is this task of the given type?
                if task['id'] == current_task_id:
                    current_task_start_time = float(task['time_start'])
                if task['name'].find(task_type) > 0:
                    task_list.append(task)

        previous_task_running = False
        for task in task_list:
            #1 - Is my own task?
            if task['id'] == current_task_id:
                continue
            task_start_time = task['time_start']
            #2 - if not, Does the task started before the current one?
            started_before_the_current_one = task_start_time!=current_task_start_time and task_start_time < current_task_start_time
            if started_before_the_current_one and param_to_compare is None: #An existing task is running
                previous_task_running  = True
                break
                 
            #3 - Does the task running in the same system?
            task_param_value = ast.literal_eval(task['args'])[argnum]
            if str(task_param_value) == str(param_to_compare) and started_before_the_current_one:
                previous_task_running = True
                break


        if previous_task_running:
            logger.info("A %s is running....waiting [%s]" % (task_type,current_task_id))

    except Exception, e:
        logger.error("An error occurred %s" % (str(e)))
        return True
    return  previous_task_running
   

def find_task_in_worker_list(celery_task_list, my_task):
    """
    Check if there is any task of type 'type' running or pending in a worker list.
    The celerey_task_list correspond to the list given from the call:
        i = inspect()
        i.active().values() or i.scheduled().values() or i.reserved().values()
    The task has the following format:
        {'task': <name of the celery task>, 'process': <name of the process>, 'param_value': <task condition>, 'param_argnum': <position of the condition>}
        
    If the 'param_value' is None, returns only if there is a task of type 'task' found within the given list.
    In order to find a task running in a <param_value>, you should specify, which argument of the
    task represent the <param_value>, for example:
    If the task was launched by running: alienvualt_reconfigure("192.168.5.134") -> args[0] will be
    the system ip, so you should specify 'param_argnum':0
    
    Args:
        celery_task_list (list) : The list of task from a worker list
        my_task (dict)          : The task we want to look for.
    
    Returns:
        sucsess (bool) : True if the task was found in the list, False otherwise
        job_id (str)   : Job ID of the task

    """
    sucsess = False
    job_id = 0
    
    for tasks_list in celery_task_list:
        for task in tasks_list:
            if task['name'].find(my_task['task']) > 0:
                if my_task['param_value'] is None:
                    sucsess = True
                    job_id = task['id']
                    break
                else:
                    try:
                        task_param_value = ast.literal_eval(task['args'])[my_task['param_argnum']]
                    except IndexError:
                        task_param_value = ''
                        
                    if str(task_param_value) == str(my_task['param_value']):
                        sucsess = True
                        job_id = task['id']
                        break
    
    return sucsess, job_id
            
            
def get_task_status(system_id, system_ip, task_list):
    """
    Check if there is any task within the 'task_list' running or pending for the given system.
    
    The format of the list of tasks to check is the following:
    {
        <Name of the task>: {'task': <name of the celery task>, 'process': <name of the process>, 'param_value': <task condition>, 'param_argnum': <position of the condition>}
    }   
    
    Args:
        system_id (str) : The system_id where you want to check if it's running
        system_ip (str) : The system_ip where you want to check if it's running
        task_list (dict): The list of task to check.
    
    Returns:
        success (bool) : True if successful, False otherwise
        result (dict)  : Dic with the status and the job id for each task.

    """
    result = {}
    
    try:
        i = inspect()
        # Retrieve the list of active tasks.
        running_tasks = i.active().values()
        # Retrieve the list of pending tasks.
        pending_tasks = i.scheduled().values() + i.reserved().values()
    except Exception, e:
        logger.error("[celery.utils.get_task_status]: An error occurred: %s" % (str(e)))
        return False, {}
    
    #For each task we are going to check its status
    for name, my_task in task_list.iteritems():
        #Default status is not running
        result[name] = {"job_id": 0, "job_status": "not_running"}
        
        #Is the task in the list of active tasks?
        success, job_id = find_task_in_worker_list(running_tasks, my_task)
        if success:
            result[name]['job_status'] = "running"
            result[name]['job_id'] = job_id
            continue
        
        #Is the task in the list of pending tasks?
        success, job_id = find_task_in_worker_list(pending_tasks, my_task)
        if success:
            result[name]['job_status'] = "pending"
            result[name]['job_id'] = job_id
            continue
        
        #Is the task process running?
        success, pid = ansible_get_process_pid(system_ip, my_task['process'])
        if success and pid:
            result[name]['job_status'] = "running"
            result[name]['job_id'] = pid
            continue
        
    return  True, result
