from smtpd import DebuggingServer
class SMTPDebugger(DebuggingServer):
    def __init__(*args,**kwargs):
        DebuggingServer.__init__(*args,**kwargs)
    def process_message(*args, **kwargs):
        for a in args:
            print(a)
        for k,v in kwargs.items():
            print("%s = %s" % (str(k),str(v)))
