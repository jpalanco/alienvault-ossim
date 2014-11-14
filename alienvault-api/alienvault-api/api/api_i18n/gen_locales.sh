#!/bin/bash

echo "Generation .pot"
xgettext  -a --language=Python --output=locales/alienvault_api.pot $(find . -name "*.py")
echo "Generate english translation"
cd ./locales && msginit --input=alienvault_api.pot --locale=en_US
#spanish translation
echo "Generate spanish translation"
msginit --input=alienvault_api.pot --locale=es_ES
echo "Generate mo"
mkdir -p ./en/LC_MESSAGES/
mkdir -p ./es/LC_MESSAGES/

msgfmt en_US.po --output-file ./en/LC_MESSAGES/alienvault_api.mo
msgfmt es.po --output-file ./es/LC_MESSAGES/alienvault_api.mo

