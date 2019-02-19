#!/bin/bash
TEST_MODULE_BIN=./test-module
$TEST_MODULE_BIN -m av_mail.py -a "host=smtp.gmail.com \
								   port=465 \
								   sender=cuenta.test.av@gmail.com \
								   recipients=crosa@alienvault.com,cristobalrosa@gmail.com \
								   subject=\"Titulo del correo\"
								   body=\"Un body como otro cualquiera\"
								   user=cuenta.test.av@gmail.com \
								   passwd=alien4ever \
								   use_ssl=True"