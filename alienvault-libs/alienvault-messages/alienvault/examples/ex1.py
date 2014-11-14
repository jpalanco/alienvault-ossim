import os
import sys

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

# Create the .pot files:
# xgettext  --language=Python --output=locales/ex1.pot $(find . -name "*.py")
# Generate the .po
# cd ./locales && msginit --input=ex1.pot --locale=en_US
# Generate spanish translation:
#msginit --input=ex1.pot --locale=es_ES
# Generate the .mo:
# cd ./locales && msgfmt en_US.po --output-file en_US.mo
# refs: http://wiki.maemo.org/Internationalize_a_Python_application

