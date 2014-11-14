from pySDEE import SDEE
import sys

subs = sys.argv[1]
sdee = SDEE(user='',password='' ,host='',method='https', force='yes')
sdee._subscriptionid = subs
sdee.close()
