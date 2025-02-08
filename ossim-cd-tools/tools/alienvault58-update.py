#!/usr/bin/env python

# Script to update USM Appliance

from __future__ import print_function
import os
import stat
import sys
import subprocess
import argparse
import json
import time
import logging

# Errors
ERROR_UPDATE_RUNNING = "1"
ERROR_VULN_SCAN_RUNNING = "2"
ERROR_REBOOT_NEEDED = "3"
ERROR_SIGNATURE_NOT_FOUND = "4"
ERROR_SIGNATURE_INVALID = "5"
ERROR_APT_LOCKED = "6"
ERROR_IN_UPDATE_FILES = "7"
EXIT_CODE_ON_ERROR = 99

# Files and script names
TMP_PATH = "/usr/share/ossim-installer/temp/"
PROGRAM_NAME = "alienvault58-update"
UPDATE_SCRIPT = "alienvault58_update-script"
UPDATE_FEED_SCRIPT = "update_feed.sh"
ERROR_JSON_FILE = "update-errors.json"
ERRORS_TEXT_FILE = TMP_PATH + ERROR_JSON_FILE
SUMMARY_FILE = TMP_PATH + "update_summary_errors"
DATASERVER = "http://data.alienvault.com/alienvault58/RELEASES"
LOG_FOLDER = "/var/log/alienvault/update/"
LOG_FILENAME_TEMPLATE = LOG_FOLDER + "alienvault-%s-orchestrator-%s.log"
BASH_LOGFILE_TEMPLATE = "alienvault-%s-%s.log"
OFFLINE_MOUNTPOINT = "/media"
OFFLINE_DEBIAN_MIRROR = OFFLINE_MOUNTPOINT + "/debian58"
OFFLINE_AV_REPO = OFFLINE_MOUNTPOINT + "/alienvault58"

# Miscalaneous
START_TIMESTAMP = str(int(time.time()))


class UpdateControl():

    @staticmethod
    def conditional_raw_input_static(message, continue_message, cli):
        if cli:
            return raw_input("%s %s" % (message, continue_message))
        else:
            print(message)


    def __init__(self, args):
        # Arguments sanity checks
        if args.feed and (args.force or args.offline):
            print("ERROR: Threat Intelligence update is not compatible with options --force or --offline")
            sys.exit(1)
        if args.force and not args.cli:
            print("ERROR: --force option must be used with --cli to allow user interaction on errors")
            sys.exit(1)

        self.feed_update = args.feed
        self.prechecks = args.prechecks
        if not self.prechecks and not self.feed_update:
            self.update = True
        else:
            self.update = False

        self.cli = args.cli
        self.offline = args.offline
        self.force = args.force
        self.verbose = args.verbose
        self.console_output = args.console

        if self.force:
            print("WARNING! Forced mode enabled!")

    def is_cli(self):
        return self.cli
    def is_update(self):
        return self.update
    def is_feed_update(self):
        return self.feed_update
    def is_prechecks(self):
        return self.prechecks
    def is_offline(self):
        return self.offline
    def is_forced(self):
        return self.force
    def is_verbose(self):
        return self.verbose
    def has_console_output(self):
        return self.console_output


    def get_log_filename(self):
        if self.update or self.prechecks:
            return LOG_FILENAME_TEMPLATE % ('update', START_TIMESTAMP)
        elif self.feed_update:
            return LOG_FILENAME_TEMPLATE % ('feed_update', START_TIMESTAMP)

        print("ERROR: Missing argument (--update, --feed or --prechecks)")
        sys.exit(1)


    def get_bash_arguments(self):
        """
        Return an string with the arguments affecting the update bash script
        """
        bash_arguments = ""
        if self.verbose:
            bash_arguments += "--verbose "
        if self.console_output:
            bash_arguments += "--console"

        return bash_arguments


    def conditional_raw_input(self, message, continue_message="Press Enter to continue"):
        return UpdateControl.conditional_raw_input_static(message, continue_message, self.is_cli())


    def ask_and_check_answer(self, question):
        if self.is_cli():
            answer = self.conditional_raw_input(question, "Are you sure you want to continue?(y/n):")
        else:
            self.conditional_raw_input(question, "")
            return

        bad_answer = True
        while bad_answer:
            if answer.lower() not in ('y', 'n'):
                answer = self.conditional_raw_input(question + "\nPlease type 'y' for yes or 'n' for not:(y/n)")
            else:
                bad_answer = False

        if answer is "n":
            self.conditional_raw_input("Operation cancelled by user.")
            raise SystemExit(EXIT_CODE_ON_ERROR)


class Logger():
    def __init__(self, log_filename, console_output=False, file_level=logging.WARNING, console_level=logging.WARNING):
        self.logger = logging.getLogger("Default_logger")
        self.logger.setLevel(logging.DEBUG) # The logger logs everything, fine grain controlled by handlers

        self.formatter = logging.Formatter('%(levelname)s - %(message)s')

        # Default always active log to file
        self.handler = logging.FileHandler(log_filename)
        self.handler.setFormatter(self.formatter)
        self.handler.setLevel(file_level)
        self.logger.addHandler(self.handler)

        # Console handler may be enabled later
        self.console_handler = logging.StreamHandler()
        self.console_handler.setFormatter(self.formatter)
        self.console_handler.setLevel(console_level)
        self.enable_console_output(console_output)


    def enable_console_output(self, enable=True):
        if enable:
            self.logger.addHandler(self.console_handler)
        else:
            self.logger.removeHandler(self.console_handler)


    def __getattr__(self, name):
        """
        Pass calls to other functions straight to the inner logger
        """
        def logger_function(*args):
            return getattr(self.logger, name)(*args)
        return logger_function


class Update():

    @staticmethod
    def backup_previous_logs():
        create_command = "mkdir -p " + LOG_FOLDER + "backups"
        os.system(create_command)
        for file in os.listdir(LOG_FOLDER):
            if file.startswith("alienvault") and file.endswith(".log"):
                move_command = "mv " + LOG_FOLDER + file + " " + LOG_FOLDER + "/backups/" + file
                os.system(move_command)


    @staticmethod
    def check_update_running(cli):
        command = "ps -aux | grep " + PROGRAM_NAME + " | grep -v grep"
        process = subprocess.check_output(command, shell=True).count(PROGRAM_NAME)

        # Grep will skip itself and detect CLI launching the update and previous running update scripts
        # eg.
        # root     11806  0.0  0.0  26304  9544 pts/3    T    07:10   0:00 python /usr/bin/alienvault58-update.py
        # root     12424  0.0  0.0  11124  2796 pts/1    S+   07:13   0:00 /bin/sh -c clear; alienvault58-update.py
        # root     12426  0.1  0.0  26648  9424 pts/1    S+   07:13   0:00 python /usr/bin/alienvault58-update.py
        if process > 2:
            UpdateControl.conditional_raw_input_static(
                "ERROR: Update process already running",
                "Press Enter to continue",
                cli)
            raise SystemExit("Update already running")


    def __init__(self, control, logger):
        self.control = control
        self.logger = logger


    def __load_errors_text(self):
        self.retrieve_file(ERROR_JSON_FILE)

        with open(ERRORS_TEXT_FILE, 'r') as error_file:
            self.error_data = json.load(error_file)


    def __get_scripts_from_usb(self):
        command = "blkid -L Alienvault"
        try:
            usb = subprocess.check_output(command, shell=True)
        except:
            self.control.conditional_raw_input("ERROR: Please connect your USB before updating your Operating System.")
            raise SystemExit(EXIT_CODE_ON_ERROR)

        device = usb.split("\n")[0]
        mount_command = "mount " + device + " /media"
        cp_command = "cp /media/av_offline_update.sh /media/av_offline_update.sh.sig /media/update-errors.json " + TMP_PATH
        rename_script_command = "mv " + TMP_PATH + "av_offline_update.sh " + TMP_PATH + UPDATE_SCRIPT
        rename_sig_command = "mv " + TMP_PATH + "av_offline_update.sh.sig " + TMP_PATH + UPDATE_SCRIPT+".sig"
        permissions_cmd = 'chmod 755 ' + TMP_PATH + UPDATE_SCRIPT
        os.system(mount_command)
        os.system(cp_command)
        os.system(rename_script_command)
        os.system(rename_sig_command)
        os.system(permissions_cmd)


    def retrieve_file(self, filename):
        permissions_cmd = 'chmod 755 ' + TMP_PATH + filename
        if not self.control.is_offline():
            res1 = os.system('wget ' + DATASERVER + "/" + filename + ' -O ' + TMP_PATH + filename + " -o /dev/null")
            res2 = os.system('wget ' + DATASERVER + '/' + filename + '.sig -O ' + TMP_PATH + filename + ".sig" + " -o /dev/null")

            if res1 != 0 or res2 != 0:
                raise Exception('File {} cannot be downloaded.  Please, review your network configuration '
                                'and make sure that you have internet access'.format(filename))
            else:
                os.system(permissions_cmd)
        else:
            self.__get_scripts_from_usb()


    def retrieve_update_script(self):
        if self.control.is_update() or self.control.is_prechecks():
            filename = UPDATE_SCRIPT
        else:
            filename = UPDATE_FEED_SCRIPT

        self.retrieve_file(filename)


    def apt_status(self):
        self.logger.info("Checking apt status")
        locked = True
        iter = 0
        max_iter = 10
        while locked:
            try:
                aptstatus = subprocess.check_output("apt-get -s -d install ossim-cd-tools", shell=True)
            except subprocess.CalledProcessError as e:
                print(e.output)
                self.control.conditional_raw_input("Error installing package ossim-cd-tools.")
                raise SystemExit(EXIT_CODE_ON_ERROR)

            if ("dpkg was interrupted" in aptstatus):
                locked = True
                self.logger.error("Running 'dpkg --configure -a --force-confnew' to correct the problem")
                os.system("dpkg --configure -a --force-confnew")
            else:
                locked = False
            iter += 1
            if iter >= max_iter:
                self.control.conditional_raw_input(self.error_data[ERROR_APT_LOCKED])
                raise SystemExit(EXIT_CODE_ON_ERROR)
            time.sleep(5)


    def prepare_system(self):
        """
        Run basic prechecks and check apt status before upgrade/update operations
        """
        self.basic_prechecks()
        self.apt_status()


    def check_signature(self):
        filename = TMP_PATH
        if self.control.is_update() or self.control.is_prechecks():
            filename += UPDATE_SCRIPT
        else:
            filename += UPDATE_FEED_SCRIPT

        self.logger.debug("Checking signature of: " + filename)
        exists = os.path.isfile(filename)
        if exists:
            invalid_sig = os.system(
                'gpg --batch --verify --keyring /etc/apt/trusted.gpg ' + filename + '.sig' + ' ' + filename + " 2>/dev/null")
            if invalid_sig:
                self.parse_error_and_warning_codes(ERROR_SIGNATURE_INVALID, fail=True)
        else:
            self.parse_error_and_warning_codes(ERROR_SIGNATURE_NOT_FOUND, fail=True)


    def perform(self, command):
        self.logger.debug("About to run command: " + command)
        try:
            process = subprocess.Popen(command, shell=True)
            process.wait()
            error = process.returncode
            return error
        except Exception as e:
            self.control.conditional_raw_input("Exception performing command %s: %s" % (command, str(e)))
            raise SystemExit(EXIT_CODE_ON_ERROR)


    def perform_system_update(self):
        """
        Download the system update script if the online mode is enabled, check the signatures
        """
        msg = "Performing update (this may take a while)"
        print(msg)
        self.logger.info(msg)

        log_filename = BASH_LOGFILE_TEMPLATE % ("update", START_TIMESTAMP)
        mode = "--online"
        if self.control.is_offline():
            mode = "--offline"
        command = TMP_PATH + UPDATE_SCRIPT + " --logfile " + log_filename + " " + mode + " " + self.control.get_bash_arguments()
        update_error = self.perform(command)

        if update_error > 0:
            errors, warnings = self.process_precheck_summary_file()
            self.display_prechecks_summary(errors, warnings)
            self.control.conditional_raw_input("The process cannot continue because some errors were found.")
            raise SystemExit(EXIT_CODE_ON_ERROR)
        else:
            self.logger.info("Update successfully completed")


    def update_feed(self):
        msg = "Performing update (this may take a while)"
        print(msg)
        self.logger.warning(msg)

        command = TMP_PATH + UPDATE_FEED_SCRIPT + " " + self.control.get_bash_arguments()
        update_error = self.perform(command)

        if update_error > 0:
            errors, warnings = self.process_precheck_summary_file()
            self.display_prechecks_summary(errors, warnings)
            self.control.conditional_raw_input("The process cannot continue because some errors were found.")
            raise SystemExit(EXIT_CODE_ON_ERROR)
        else:
            self.logger.info("Feed update successfully completed")


    def print_update_not_available(self):
        print("=============================================================================")
        print("=                                                                           =")
        print("= Operating System upgrade is not available yet. Press [Enter] to continue. =")
        print("=                                                                           =")
        print("=============================================================================")
        self.control.conditional_raw_input("", "")

        os._exit(0)


    def parse_error_and_warning_codes(self, code, code_type="error", fail=False):
        if fail:
            print("\nThe process cannot continue because some errors were found:\n")

        code = str(code)
        if code_type == "error":
            if code in self.error_data["ERROR"]:
                text = self.error_data["ERROR"][code]
                self.logger.debug(text)
            else:
                text = "Unexpected Error: " + code
                self.logger.warning("Unexpected error code: " + code)

        else:
            if code in self.error_data["WARNING"]:
                text = self.error_data["WARNING"][code]
                self.logger.debug(text)
            else:
                text = "Unexpected Warning: " + code
                self.logger.warning("Unexpected warning code: " + code)

        if fail:
            print(text + "\n")
            self.control.conditional_raw_input("Please contact support@alienvault.com for help.")
            raise SystemExit(EXIT_CODE_ON_ERROR)

        return text


    def display_prechecks_summary(self, err_codes, warn_codes):
        """
        Process two lists with errors and warning codes to display them to the user with
        help strings from the JSON file
        """
        print("\nThe following WARNINGS have been found: ")
        if len(warn_codes) > 0:
            for w_code in warn_codes:
                text = self.parse_error_and_warning_codes(w_code, "warning", False)
                print("\t" + text)
        else:
            print("\tNone")

        print("\nThe following ERRORS have been found: ")
        if len(err_codes) > 0:
            for code in err_codes:
                text = self.parse_error_and_warning_codes(code, "error", False)
                print("\t" + text)

            print("\nPlease contact support@alienvault.com for help")
        else:
            print("\tNone")

        print("----------------------------------------")


    def process_precheck_summary_file(self):
        """
        Reads the summary file, output of the update bash script and generate two lists
        one with errors and another with warnings.

        Returns a tuple with the two lists ([errors], [warnings])
        """
        errors = []
        warnings = []
        if os.path.isfile(SUMMARY_FILE):
            with open(SUMMARY_FILE, 'r') as f:
                content = f.read()
            content = content.rstrip()
            summary = content.split(",")

            for element in summary:
                element = element.strip()
                error_element = element.split("-")
                if error_element[0] == "E":
                    errors.append(error_element[1])
                if error_element[0] == "W":
                    warnings.append(error_element[1])

        elif self.control.is_prechecks():
            self.control.conditional_raw_input("There is no summary file from the update script.")
            raise SystemExit(EXIT_CODE_ON_ERROR)

        return (errors, warnings)


    def perform_update_prechecks(self):
        """
        Run prechecks and allow to continue the operation if in force mode
        """
        log_filename = BASH_LOGFILE_TEMPLATE % ("prechecks", START_TIMESTAMP)
        command = TMP_PATH + UPDATE_SCRIPT + " --logfile " + log_filename + " --prechecks " + self.control.get_bash_arguments()
        self.logger.debug("About to run: " + command)
        process = subprocess.Popen(command, shell=True)
        process.wait()
        precheck_error = process.returncode

        if precheck_error == 100:
            self.print_update_not_available()

        errors, warnings = self.process_precheck_summary_file()
        self.display_prechecks_summary(errors, warnings)

        if not self.control.is_prechecks():
            if not self.control.is_forced() and len(errors) > 0:
                self.control.conditional_raw_input("\nThe process cannot continue because some errors were found.")
                raise SystemExit(EXIT_CODE_ON_ERROR)

            if self.control.is_forced() and len(errors) > 0:
                self.control.ask_and_check_answer("\nSome error were found in the prechecks.")

            elif len(warnings) > 0:
                self.control.ask_and_check_answer("\nSome warnings were found in the prechecks.")


    def basic_prechecks(self):
        self.check_vuln_scan_running()
        self.check_update_files()


    def check_vuln_scan_running(self):
        self.logger.info("Checking for running scans")
        command = "ps -aux | grep gvmd | grep -i handling | grep -v grep | wc -l"
        try:
            num_scanners = int(subprocess.check_output(command, shell=True))
        except subprocess.CalledProcessError as e:
            print(e.output)
            self.control.conditional_raw_input("GVM is running a scan")
            raise SystemExit(EXIT_CODE_ON_ERROR)
        if num_scanners > 0:
            self.logger.error(self.parse_error_and_warning_codes(ERROR_VULN_SCAN_RUNNING, fail=True))



    def check_update_files(self):
        self.logger.info("Checking update files")

        issue_detected = False
        # Check the presence of the offline repositories locations
        if self.control.is_offline():
            if not os.path.exists(OFFLINE_DEBIAN_MIRROR) or \
                    not os.path.exists(OFFLINE_AV_REPO):
                issue_detected = True

        # Check the presence of the JSON errors file
        if not os.path.exists(TMP_PATH + ERROR_JSON_FILE):
            self.logger.error(self.parse_error_and_warning_codes(ERROR_IN_UPDATE_FILES, fail=True))

        # Check the presence and permissions of the corresponding update script
        if self.control.is_update() or self.control.is_prechecks():
            update_script = TMP_PATH + UPDATE_SCRIPT
        else:
            update_script = TMP_PATH + UPDATE_FEED_SCRIPT

        if not os.path.exists(update_script):
            issue_detected = True
        else:
            st = os.stat(update_script)
            # Check for 755 permissions
            if oct(st.st_mode)[-3] != '7' and oct(st.st_mode)[-2] != '5' and oct(st.st_mode)[-1] != '5':
                issue_detected = True

        if issue_detected:
            self.logger.error(self.parse_error_and_warning_codes(ERROR_IN_UPDATE_FILES, fail=True))


    def go(self):
        try:
            print("Update process starting")

            self.__load_errors_text()

            self.retrieve_update_script()
            self.check_signature()

            if self.control.is_prechecks():
                self.perform_update_prechecks()
            elif self.control.is_update():
                self.perform_update_prechecks()
                self.prepare_system()
                self.perform_system_update()
            elif self.control.is_feed_update():
                self.basic_prechecks()
                self.update_feed()
        except:
            message = "\n\nUpdate Error: {}.\n\n".format(sys.exc_info()[1])

            self.control.conditional_raw_input(message)
            raise SystemExit(EXIT_CODE_ON_ERROR)

def main():

    parser = argparse.ArgumentParser()

    # Prevent --feed and --prechecks from been used at the same time
    group = parser.add_mutually_exclusive_group()
    group.add_argument("--feed", help="Perform a threat intelligence update", action="store_true")
    group.add_argument("--prechecks", help="Preckeck the system", action="store_true")
    parser.add_argument("--update", help="(DEPRECATED: default behaviour)", action="store_true")
    parser.add_argument("--cli", help="Run the script in interactive mode. (Stop on errors, wait for user inputs...)", action="store_true")
    parser.add_argument("--online", help="(DEPRECATED: default behaviour)", action="store_true")
    parser.add_argument("--offline", help="Perform an offline update", action="store_true")
    parser.add_argument("--force", help="Update even if the prechecks don't pass. Must be used with --cli", action="store_true")
    parser.add_argument("--verbose", help="Verbose mode", action="store_true", dest="verbose")
    parser.add_argument("-v", help="(DEPRECATED: use --verbose)", action="store_true", dest="verbose")
    parser.add_argument("--console", help="Log into standard output", action="store_true", dest="console")
    parser.add_argument("-c", help="(DEPRECATED: Use --console)", action="store_true", dest="console")
    parser.add_argument("--quiet", help="(DEPRECATED: default behaviour)", action="store_true")
    args = parser.parse_args()

    Update.check_update_running(args.cli)
    Update.backup_previous_logs()

    control = UpdateControl(args)
    log_file = control.get_log_filename()
    if control.is_verbose():
        logger = Logger(log_file, control.has_console_output(), file_level=logging.INFO, console_level=logging.INFO)
    else:
        logger = Logger(log_file, control.has_console_output())

    update_process = Update(control, logger)

    update_process.go()


if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        print("Ctrl-C captured, no action taken")
