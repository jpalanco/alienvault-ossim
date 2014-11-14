#!/bin/bash
cd /var/lib/openvas/plugins
perl -i -npe 's/&(?!amp)/&amp;/g if (/script_xref/)' *.nasl
perl -i -npe 's/&(?!amp)/&amp;/g if (/script_xref/)' */*.nasl
perl -i -npe 's/&(?!amp)/&amp;/g if (/script_xref/)' */*/*.nasl
perl -i -npe 's/<(.*)>/&lt;$1&gt;/g if (/script_xref/)' *.nasl
