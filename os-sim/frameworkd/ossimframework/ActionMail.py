# -*- coding: utf-8 -*-
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#
import smtplib
from email.mime.text import MIMEText
#
# LOCAL IMPORTS
#
from Logger import Logger
#
# GLOBAL VARIABLES
#
logger = Logger.logger

class ActionMail:

    def __init__(self, server,server_port, user, passwd, use_postfix):
        """Constructor
        @param server: The email server
        @param user: the email server user
        @param passwd: the email user passwd
        @param use_postfix: Send the email using postfix
        """
        self.__server = server
        self.__passwd = passwd
        self.__user = user
        self.__port = server_port
        self.__use_postfix = use_postfix

    def __sendmail_postfix(self, sender, recipients,message):
        """Tries to send an email using postfix local server
        """
        ret = False
        try:
            logger.info("Trying to send emain using our local server.....")
            smtp = smtplib.SMTP(timeout=10)
            logger.info("Connected to our local smtp")
            smtp.connect()
            logger.info("Connected....OK")
            smtp.sendmail(sender, recipients, message.as_string())
            smtp.close()
            ret = True
        except Exception, e:
            logger.error("An error occurred by sending email: %s" % str(e))
        return ret


    def __sendmail_using_relay_conf(self, sender, recipients, message):
        """Tries to send an email using the relay info """
        # Send the message via our own SMTP server.
        ret = False
        try:
            smtp = smtplib.SMTP(self.__server, self.__port,timeout=10)
            logger.info("Trying to send mail...Connection to the SMTP server..")
            try:
                smtp.ehlo()
                smtp.starttls()
                smtp.ehlo()
            except smtplib.SMTPException: # STARTTLS not supported
                pass
            smtp.login(self.__user,self.__passwd)
            logger.info("Connected to the smtp")
            logger.info("Sending mail...")
            smtp.sendmail(sender, recipients, message.as_string())
            smtp.close()
            ret = True
        except Exception, e:
            logger.error("An error occurred by sending email: %s" % str(e))
        return ret

    def sendmail(self, sender, recipients, subject, message):
    
        # Create a text/plain message
        msg = MIMEText(message, 'plain', 'latin-1')

        msg['Subject'] = subject
        msg['From'] = sender
        msg['To'] = ", ".join(recipients) if type(recipients) is list else recipients
        ret = False
        if self.__user == None or self.__user =="" or self.__user == "unconfigured":
            self.__use_postfix = True
        if self.__use_postfix:
            ret = self.__sendmail_postfix(sender, recipients, msg)
            
        else:
            ret = self.__sendmail_using_relay_conf(sender, recipients, msg)
            if not ret:
                logger.info("Send the email using the relay server has failded. Trying it by using our local postfix..")
                ret = self.__sendmail_postfix(sender, recipients, msg)
        logger.info("Email sent ...... %s" % str(ret))

# vim:ts=4 sts=4 tw=79 expandtab:
