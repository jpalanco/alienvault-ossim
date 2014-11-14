#!/usr/bin/python
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2013 AlienVault
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
# Error:
# 800 - Attachment file doesn't exit
# 801 - Unknown error sending the email
# 802 - Attachament Error.
import smtplib
from email.mime.text import MIMEText
from email.MIMEBase import MIMEBase
from email.MIMEMultipart import MIMEMultipart
from email.Utils import formatdate
from email import Encoders
from optparse import OptionParser
import os
import logging
class AVMailLogger(object):
    """Logger class for the Alienvault Mail utility"""
    logger = logging.getLogger("AVMailTool")
    logger.setLevel(logging.INFO)
    FORMAT = "avmailtool: %(asctime)s %(module)s %(levelname)s - %(threadName)s   %(message)s"
    __formatter = logging.Formatter(FORMAT)
    #Console handler
    __streamhandler = None
    __streamhandler = logging.StreamHandler()
    __streamhandler.setFormatter(__formatter)
    logger.addHandler(__streamhandler)

    @staticmethod
    def remove_console_handler():
        """Remove the streamhandler"""
        if AVMailLogger.__streamhandler:
            AVMailLogger.logger.removeHandler(AVMailLogger.__streamhandler) 
    @staticmethod
    def add_file_handler(filename, log_level=None):
        """Adds a file handler
        filename: Log file path
        log_level: Log level"""
        folder = file.rstrip(os.path.basename(filename))
        if not os.path.isdir(folder):
            try:
                os.makedirs(folder,0755)
            except OSError,e:
                print "AVMailLogger: Error adding the file handler. %s" % str(e)
                return
        try:
            handler = logging.FileHandler(filename)
            handler.setFormatter(AVMailLogger.__formatter)
            if log_level:
                handler.setLevel(handler)
            AVMailLogger.logger.addHandler(handler)
        except IOError,e:
            print "AVMailLogger [2]: Error adding the file handler:  %s" % str(e)
            return

logger = AVMailLogger.logger
VERSION="0.0.1"
class AVMail:

    def __init__(self, smtp_server,smtp_server_port, smtp_user, smtp_password, use_postfix,use_ssl):
        """Constructor
        @param server: The email server
        @param user: the email server user
        @param passwd: the email user passwd
        @param use_postfix: Send the email using postfix
        @param use_ssl: Use a ssl connection
        """
        self.__server = smtp_server
        self.__passwd = smtp_password
        self.__user = smtp_user
        self.__port = smtp_server_port
        self.__use_postfix = use_postfix
        self.__use_ssl = use_ssl
        if smtp_server is None or smtp_server == '':
            if not use_postfix:
                logger.warning("Use postfix = No, but the given server is None, then it will be used postfix")
            self.__use_postfix = True
    def check_connection(self):
        """Check the connection with the current params
        Returns a tuple of (code, msg) where code=0 if success, 
        otherwise it gives you an error code.
        """
        paramsok = (0, "OK")
        try:
            if self.__use_postfix:
                logger.info("CheckConn: Connection using a local connection")
                if self.__use_ssl:
                    smtp = smtplib.SMTP_SSL(timeout=10)
                else:
                    smtp = smtplib.SMTP(timeout=10)
                #SMTP Reply codes: http://tools.ietf.org/html/rfc821.html#page-34
                (conncode, msg) = smtp.connect()
                logger.debug("Conncode: %s, msg: %s" %(conncode,msg))
                # Maybe it could be interesting to control the return codes in order to detect 
                # unexpected situations
                smtp.close()
                if conncode != 220:
                    return paramsok(conncode, msg)
                logger.info("CheckConn:OK")
            else:
                logger.info("Connection to %s:%s" %(self.__server, int(self.__port)))
                if self.__use_ssl:
                    smtp = smtplib.SMTP_SSL(self.__server,int(self.__port),timeout=10)
                else:
                    smtp = smtplib.SMTP(self.__server,int(self.__port),timeout=10)
                #Try using TLS:
                try:
                    if not self.__use_ssl:
                        logger.debug("EHLO....")
                        (code,msg) = smtp.ehlo()
                        logger.debug("EHLO ...%s,%s" %(code,msg))
                        (code,msg) = smtp.starttls()
                        logger.debug("STARTTLS: %s:%s" % (code,msg))
                        (code,msg) = smtp.ehlo()
                        logger.debug("EHLO ... %s:%s"% (code,msg))
                except smtplib.SMTPException:
                    logger.warning("CheckConn: SMTP Server doesn't support TLS connections")
                except smtplib.SMTPAuthenticationError,e:
                    logger.warning("CheckConn: Wrong User/Password")
                (conncode,msg) = smtp.login(self.__user,self.__passwd)
                smtp.close()
                if conncode != 235: 
                    logger.warning("Login not accepted: %s - %s" % (conncode,msg))
                    return (conncode,msg)
        except smtplib.SMTPAuthenticationError,e:
            paramsok = (1100, "AuthenticationError: %s" % str(e))
            logger.error("SMTP Authentication error, %s" % str(e))
        except Exception,e:
            paramsok=(1101,"CheckConn Unknown Error: %s" % str(e))
            logger.error("CheckConn Unknown Error: %s" % str(e))
        return paramsok


    def __sendmail_postfix(self, sender, recipients,message):
        """Tries to send an email using postfix local server
        """
        ret = (0,"OK")
        try:
            logger.info("Trying to send emain using our local server.....")
            if self.__use_ssl:
                smtp = smtplib.SMTP_SSL(timeout=10)
            else:
                smtp = smtplib.SMTP(timeout=10)
            logger.info("Connected to our local smtp")
            smtp.connect()
            logger.info("Connected....OK")
            dd = smtp.sendmail(sender, recipients, message.as_string())
            print "Resultado ", dd
            smtp.close()
        except Exception, e:
            logger.error("An error occurred by sending email: %s" % str(e))
            ret=(801,"Error sending email: %s" % str(e))
        return ret


    def __sendmail_using_relay_conf(self, sender, recipients, message):
        """Tries to send an email using the relay info """
        # Send the message via our own SMTP server.
        ret = (0,"OK")
        try:
            if self.__use_ssl:
                smtp = smtplib.SMTP_SSL(self.__server,self.__port,timeout=10)
            else:
                smtp = smtplib.SMTP(self.__server, self.__port,timeout=10)
            logger.info("Trying to send mail...Connection to the SMTP server..")
            try:
                if not self.__use_ssl:
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
            
        except Exception, e:
            logger.error("An error occurred by sending email: %s" % str(e))
            ret=(801,"Error sending email: %s" % str(e))
        return ret

    def sendmail(self, mail_from, mail_to, mail_cc, mail_bcc, mail_subject, mail_message, mail_attachements):
        """Send the email using the given 
        """
        ret = (0,"OK")
        # Create a text/plain message
        msg = MIMEMultipart()

        #msg = MIMEText(mail_message, 'plain', 'latin-1')
        logger.info("CCC From: %s" % mail_from)
        msg['Subject'] = mail_subject
        msg['From'] = mail_from
        msg['To'] = ", ".join(mail_to)
        msg['CC'] = ", ".join(mail_cc)
        msg['BCC'] = ", ".join(mail_bcc)
        msg.attach(MIMEText(mail_message))
        try:
            for f in mail_attachements:
                part = MIMEBase('application',"octet-stream")
                thefile = open(f, "rb")
                part.set_payload( thefile.read() )
                thefile.close()
                Encoders.encode_base64(part)
                part.add_header('Content-Disposition', 'attachment; filename="%s"' % os.path.basename(f))
                msg.attach(part)
        except Exception,e:
            logger.error("Error adding the attachements %s" % str(e))
            return (802,  "Attachemnt Error: %s" % str(e))

        if self.__user == None or self.__user =="" or self.__user == "unconfigured":
            self.__use_postfix = True
        if self.__use_postfix:
            ret = self.__sendmail_postfix(mail_from,mail_to, msg)
        else:
            ret = self.__sendmail_using_relay_conf(mail_from,mail_to, msg)
            if ret[0]!=0:
                logger.info("Send the email using the relay server has failded. Trying it by using our local postfix.. %s",ret[1] )
                ret = self.__sendmail_postfix(mail_from, mail_to, msg)
        logger.info("Email sent ...... %s" % str(ret))
        return ret

if __name__=="__main__":
    logger.setLevel(logging.INFO)
    logger.info("Alienvault Mail utility")
    parser = OptionParser(usage="%prog [-c configuration_file] [-f from ] [-t to] [-o bco] [-b bc] \
[-d attachments_list] [-m message] [-s subject] [-p port] \
[-a smtp_server_address] [-k user_password] [-u user] [-l postfix]",
                          version="Alienvault Mail Tool %s" % VERSION)
    #parser.add_option("-c", "--configfile", dest="config_file", action="store", help="Read the smtp server configuration from a given file",
    #                metavar="FILE")
    parser.add_option("-f", "--from", dest="mail_from", action="store", help="Mail from")
    parser.add_option("-t", "--to", dest="mail_to", action="store",help="Mail to. It could  be a comma separated list")
    parser.add_option("-o", "--bcc", dest="mail_bcc", action="store", help="Mail BCC (Blind Carbon Copy). It could be a comma separated list ")
    parser.add_option("-b", "--cc", dest="mail_cc", action="store", help="Mail CC (Carbon Copy). It could be a comma separated list")
    parser.add_option("-d", "--attachments", dest="mail_attachements", help="A comma separated list of attachments files")
    parser.add_option("-m", "--message", dest="mail_message",help="Mail message")
    parser.add_option("-s", "--subject", dest="mail_subject", help="The mail subject")
    parser.add_option("-p", "--port", dest="smtp_server_port", help="smtp Server port")
    parser.add_option("-a", "--address", dest="smtp_server_address", help="smtp Server address")
    parser.add_option("-k", "--key",dest="smtp_user_password", help="smtp User Password")
    parser.add_option("-u", "--user", dest="smtp_user", help="smtp User")
    parser.add_option("-l", "--local_postfix", dest="use_postfix", help="Use local postfix", action="store_true")
#    parser.add_option("-x", "--html", dest="use_html", help="Use html text", action="store_true")
    parser.add_option("-y", "--ssl" , dest="use_ssl", help="Use SSL", action="store_true")
    (options,args) = parser.parse_args()
    #def __init__(self, smtp_server,smtp_server_port, smtp_user, smtp_passord, use_postfix):

    mail = AVMail(options.smtp_server_address,
                 options.smtp_server_port, 
                 options.smtp_user,
                 options.smtp_user_password,
                 options.use_postfix,
                 options.use_ssl)

    (code,msg) = mail.check_connection()
    if code != 0:
        logger.error("Can't connect to the smtp Server")
        exit(code)
    logger.info("Login Accepted: %s" % msg)
    #Test Envio.-
    to_list = options.mail_from.split(',')
    cc_list = []
    bcc_list = []
    attach_list = []
    if options.mail_cc:
        cc_list = options.mail_cc.split(',')
    if options.mail_bcc:
        bcc_list = options.mail_bcc.split(',')
    if options.mail_attachements:
        attach_list = options.mail_attachements.split(',')
        for attach in attach_list:
            if not os.path.isfile(attach):
                logger.error("Attachement file doesn't exist -> %s" % attach)
                exit(800) 
    code= mail.sendmail(options.mail_from, options.mail_to.split(','), cc_list,bcc_list, options.mail_subject, options.mail_message,attach_list)
    del mail
# vim:ts=4 sts=4 tw=79 expandtab:
