Install:
python setup install
Usage:
from alienvault.i18n import AlienvaultMessageHandler, AlienvaultApps
MSG_001 = 1
MSG_002 = 2
MSG_003 = 3

app_messages = {
    MSG_001: "Message one",
    MSG_002: "Message two",
    MSG_003: "Message three",
}
# SetUP the library
if not AlienvaultMessageHandler.setup("ex1", "./locales/", AlienvaultApps.API, app_messages):
    print "Can't setup"
    exit(0)

print AlienvaultMessageHandler.error(MSG_001)
print AlienvaultMessageHandler.error(MSG_002)

Debianize:
This command builds all the debian folder files. (It's posible that you have to modify something)
python setup.py --command-packages=stdeb.command debianize
dpkg-buildpackage -us -uc

