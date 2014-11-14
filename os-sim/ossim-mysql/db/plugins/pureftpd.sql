-- Pure-FTPd
-- plugin_id: 1616

-- Check sid relation from pure-ftpd source code at file:
--  [ pure-ftpd-1.0.29/src/messages_en.h ]

DELETE FROM plugin WHERE id = "1616";
DELETE FROM plugin_sid WHERE plugin_id = "1616";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1616, 1, 'pureftpd', 'Pure-FTPd: FTP Server');

-- Failsave event
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 999, "Pure-FTPd: Generic Event", 1, 1);

-- MSG_TIMEOUT: Timeout
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 1, "Pure-FTPd: MSG_TIMEOUT", 1, 1);
-- MSG_CAPABILITIES: Unable to switch capabilities
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 2, "Pure-FTPd: MSG_CAPABILITIES", 1, 1);
-- MSG_CLIENT_CLOSED_CNX: Client closed the connection
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 3, "Pure-FTPd: MSG_CLIENT_CLOSED_CNX", 1, 1);
-- MSG_CLIENT_READ_ERR: Read error from the client
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 4, "Pure-FTPd: MSG_CLIENT_READ_ERR", 1, 1);
-- MSG_CANT_OPEN_CNX: Can't open connection
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 5, "Pure-FTPd: MSG_CANT_OPEN_CNX", 1, 1);
-- MSG_CANT_CREATE_DATA_SOCKET: Can't create the data socket
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 6, "Pure-FTPd: MSG_CANT_CREATE_DATA_SOCKET", 1, 1);
-- MSG_DEBUG_CLIENT_IS: The client address is
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 7, "Pure-FTPd: MSG_DEBUG_CLIENT_IS", 1, 1);
-- MSG_SYNTAX_ERROR_IP: Syntax error in IP address
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 8, "Pure-FTPd: MSG_SYNTAX_ERROR_IP", 1, 1);
-- MSG_PORT_SUCCESSFUL: PORT command successful
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 9, "Pure-FTPd: MSG_PORT_SUCCESSFUL", 1, 1);
-- MSG_ONLY_IPV4V6: Only IPv4 and IPv6 are supported (1,2)
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 10, "Pure-FTPd: MSG_ONLY_IPV4V6", 1, 1);
-- MSG_ONLY_IPV4: Only IPv4 is supported (1)
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 11, "Pure-FTPd: MSG_ONLY_IPV4", 1, 1);
-- MSG_TIMEOUT_PARSER: Timeout - try typing a little faster next time
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 12, "Pure-FTPd: MSG_TIMEOUT_PARSER", 1, 1);
-- MSG_LINE_TOO_LONG: Line too long
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 13, "Pure-FTPd: MSG_LINE_TOO_LONG", 1, 1);
-- MSG_LOG_OVERFLOW: The client tried to overflow the command line buffer
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 14, "Pure-FTPd: MSG_LOG_OVERFLOW", 1, 1);
-- MSG_GOODBYE: Goodbye. You uploaded %llu and downloaded %llu kbytes.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 15, "Pure-FTPd: MSG_GOODBYE", 1, 1);
-- MSG_DEBUG_COMMAND: Command
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 16, "Pure-FTPd: MSG_DEBUG_COMMAND", 1, 1);
-- MSG_IS_YOUR_CURRENT_LOCATION: is your current location
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 17, "Pure-FTPd: MSG_IS_YOUR_CURRENT_LOCATION", 1, 1);
-- MSG_NOT_LOGGED_IN: You aren't logged in
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 18, "Pure-FTPd: MSG_NOT_LOGGED_IN", 1, 1);
-- MSG_AUTH_UNIMPLEMENTED: This security scheme is not implemented
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 19, "Pure-FTPd: MSG_AUTH_UNIMPLEMENTED", 1, 1);
-- MSG_NO_FILE_NAME: No file name
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 20, "Pure-FTPd: MSG_NO_FILE_NAME", 1, 1);
-- MSG_NO_DIRECTORY_NAME: No directory name
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 21, "Pure-FTPd: MSG_NO_DIRECTORY_NAME", 1, 1);
-- MSG_NO_RESTART_POINT: No restart point
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 22, "Pure-FTPd: MSG_NO_RESTART_POINT", 1, 1);
-- MSG_ABOR_SUCCESS: Since you see this ABOR must've succeeded
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 23, "Pure-FTPd: MSG_ABOR_SUCCESS", 1, 1);
-- MSG_MISSING_ARG: Missing argument
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 24, "Pure-FTPd: MSG_MISSING_ARG", 1, 1);
-- MSG_GARBAGE_FOUND: Garbage found after value
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 25, "Pure-FTPd: MSG_GARBAGE_FOUND", 1, 1);
-- MSG_VALUE_TOO_LARGE: Value too large
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 26, "Pure-FTPd: MSG_VALUE_TOO_LARGE", 1, 1);
-- MSG_IDLE_TIME: Idle time set to %lu seconds
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 27, "Pure-FTPd: MSG_IDLE_TIME", 1, 1);
-- MSG_SITE_HELP: The following SITE commands are recognized
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 28, "Pure-FTPd: MSG_SITE_HELP", 1, 1);
-- MSG_BAD_CHMOD: Invalid permissions
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 29, "Pure-FTPd: MSG_BAD_CHMOD", 1, 1);
-- MSG_UNKNOWN_EXTENSION: is an unknown extension
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 30, "Pure-FTPd: MSG_UNKNOWN_EXTENSION", 1, 1);
-- MSG_XDBG_OK: XDBG command succeeded, debug level is now %d
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 31, "Pure-FTPd: MSG_XDBG_OK", 1, 1);
-- MSG_UNKNOWN_COMMAND: Unknown command
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 32, "Pure-FTPd: MSG_UNKNOWN_COMMAND", 1, 1);
-- MSG_TIMEOUT_NOOP: Timeout (no operation for %lu seconds)
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 33, "Pure-FTPd: MSG_TIMEOUT_NOOP", 1, 1);
-- MSG_TIMEOUT_DATA: Timeout (no new data for %lu seconds)
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 34, "Pure-FTPd: MSG_TIMEOUT_DATA", 1, 1);
-- MSG_SLEEPING: Zzz...
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 35, "Pure-FTPd: MSG_SLEEPING", 1, 1);
-- MSG_ALREADY_LOGGED: You're already logged in
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 36, "Pure-FTPd: MSG_ALREADY_LOGGED", 1, 1);
-- MSG_ANY_PASSWORD: Any password will work
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 37, "Pure-FTPd: MSG_ANY_PASSWORD", 1, 1);
-- MSG_ANONYMOUS_LOGGED: Anonymous user logged in
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 38, "Pure-FTPd: MSG_ANONYMOUS_LOGGED", 1, 1);
-- MSG_ANONYMOUS_LOGGED_VIRTUAL: Anonymous user logged in the virtual FTP
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 39, "Pure-FTPd: MSG_ANONYMOUS_LOGGED_VIRTUAL", 1, 1);
-- MSG_USER_OK: User %s OK. Password required
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 40, "Pure-FTPd: MSG_USER_OK", 1, 1);
-- MSG_CANT_DO_TWICE: We can't do that in the current session
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 41, "Pure-FTPd: MSG_CANT_DO_TWICE", 1, 1);
-- MSG_UNABLE_SECURE_ANON: Unable to set up secure anonymous FTP
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 42, "Pure-FTPd: MSG_UNABLE_SECURE_ANON", 1, 1);
-- MSG_BANDWIDTH_RESTRICTED: Your bandwidth usage is restricted
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 43, "Pure-FTPd: MSG_BANDWIDTH_RESTRICTED", 1, 1);
-- MSG_NO_PASSWORD_NEEDED: Any password will work
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 44, "Pure-FTPd: MSG_NO_PASSWORD_NEEDED", 1, 1);
-- MSG_NOTRUST: Sorry, but I can't trust you
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 45, "Pure-FTPd: MSG_NOTRUST", 1, 1);
-- MSG_WHOAREYOU: Please tell me who you are
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 46, "Pure-FTPd: MSG_WHOAREYOU", 1, 1);
-- MSG_AUTH_FAILED: Login authentication failed
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 47, "Pure-FTPd: MSG_AUTH_FAILED", 1, 1);
-- MSG_AUTH_TOOMANY: Too many authentication failures
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 48, "Pure-FTPd: MSG_AUTH_TOOMANY", 1, 1);
-- MSG_NO_HOMEDIR: Home directory not available - aborting
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 49, "Pure-FTPd: MSG_NO_HOMEDIR", 1, 1);
-- MSG_NO_HOMEDIR2: %s does not exist or is unreachable
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 50, "Pure-FTPd: MSG_NO_HOMEDIR2", 1, 1);
-- MSG_START_SLASH: Starting in /
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 51, "Pure-FTPd: MSG_START_SLASH", 1, 1);
-- MSG_USER_GROUP_ACCESS: User %s has group access to
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 52, "Pure-FTPd: MSG_USER_GROUP_ACCESS", 1, 1);
-- MSG_FXP_SUPPORT: This server supports FXP transfers
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 53, "Pure-FTPd: MSG_FXP_SUPPORT", 1, 1);
-- MSG_RATIO: You must respect a %u:%u (UL/DL) ratio
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 54, "Pure-FTPd: MSG_RATIO", 1, 1);
-- MSG_CHROOT_FAILED: Unable to set up a secure chroot() jail
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 55, "Pure-FTPd: MSG_CHROOT_FAILED", 1, 1);
-- MSG_CURRENT_DIR_IS: OK. Current directory is %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 56, "Pure-FTPd: MSG_CURRENT_DIR_IS", 1, 1);
-- MSG_CURRENT_RESTRICTED_DIR_IS: OK. Current restricted directory is %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 57, "Pure-FTPd: MSG_CURRENT_RESTRICTED_DIR_IS", 1, 1);
-- MSG_IS_NOW_LOGGED_IN: %s is now logged in
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 58, "Pure-FTPd: MSG_IS_NOW_LOGGED_IN", 1, 1);
-- MSG_CANT_CHANGE_DIR: Can't change directory to %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 59, "Pure-FTPd: MSG_CANT_CHANGE_DIR", 1, 1);
-- MSG_PATH_TOO_LONG: Path too long
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 60, "Pure-FTPd: MSG_PATH_TOO_LONG", 1, 1);
-- MSG_CANT_PASV: You cannot use PASV on IPv6 connections. Use EPSV instead.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 61, "Pure-FTPd: MSG_CANT_PASV", 1, 1);
-- MSG_CANT_PASSIVE: Unable to open a passive connection
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 62, "Pure-FTPd: MSG_CANT_PASSIVE", 1, 1);
-- MSG_PORTS_BUSY: All reserved TCP ports are busy
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 63, "Pure-FTPd: MSG_PORTS_BUSY", 1, 1);
-- MSG_GETSOCKNAME_DATA: Unable to identify the local data socket
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 64, "Pure-FTPd: MSG_GETSOCKNAME_DATA", 1, 1);
-- MSG_GETPEERNAME: Unable to identify the local socket
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 65, "Pure-FTPd: MSG_GETPEERNAME", 1, 1);
-- MSG_INVALID_IP: Sorry, invalid address given
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 66, "Pure-FTPd: MSG_INVALID_IP", 1, 1);
-- MSG_NO_EPSV: Please use an IPv6-conformant client with EPSV support
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 67, "Pure-FTPd: MSG_NO_EPSV", 1, 1);
-- MSG_BAD_PORT: Sorry, but I won't connect to ports < 1024
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 68, "Pure-FTPd: MSG_BAD_PORT", 1, 1);
-- MSG_NO_FXP: I won't open a connection to %s (only to %s)
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 69, "Pure-FTPd: MSG_NO_FXP", 1, 1);
-- MSG_FXP: FXP transfer: from %s to %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 70, "Pure-FTPd: MSG_FXP", 1, 1);
-- MSG_NO_DATA_CONN: No data connection
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 71, "Pure-FTPd: MSG_NO_DATA_CONN", 1, 1);
-- MSG_ACCEPT_FAILED: The connection couldn't be accepted
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 72, "Pure-FTPd: MSG_ACCEPT_FAILED", 1, 1);
-- MSG_ACCEPT_SUCCESS: Accepted data connection
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 73, "Pure-FTPd: MSG_ACCEPT_SUCCESS", 1, 1);
-- MSG_CNX_PORT_FAILED: Could not open data connection to port %d
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 74, "Pure-FTPd: MSG_CNX_PORT_FAILED", 1, 1);
-- MSG_CNX_PORT: Connecting to port %d
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 75, "Pure-FTPd: MSG_CNX_PORT", 1, 1);
-- MSG_ANON_CANT_MKD: Sorry, anonymous users are not allowed to create directories
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 76, "Pure-FTPd: MSG_ANON_CANT_MKD", 1, 1);
-- MSG_ANON_CANT_RMD: Sorry, anonymous users are not allowed to remove directories
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 77, "Pure-FTPd: MSG_ANON_CANT_RMD", 1, 1);
-- MSG_ANON_CANT_RENAME: Anonymous users are not allowed to move/rename files
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 78, "Pure-FTPd: MSG_ANON_CANT_RENAME", 1, 1);
-- MSG_ANON_CANT_CHANGE_PERMS: Anonymous users can not change perms
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 79, "Pure-FTPd: MSG_ANON_CANT_CHANGE_PERMS", 1, 1);
-- MSG_GLOB_NO_MEMORY: Out of memory during globbing of %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 80, "Pure-FTPd: MSG_GLOB_NO_MEMORY", 1, 1);
-- MSG_PROBABLY_DENIED: (This probably means \"Permission denied\")
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 81, "Pure-FTPd: MSG_PROBABLY_DENIED", 1, 1);
-- MSG_GLOB_READ_ERROR: Read error during globbing of %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 82, "Pure-FTPd: MSG_GLOB_READ_ERROR", 1, 1);
-- MSG_GLOB_NO_MATCH: No match for %s in %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 83, "Pure-FTPd: MSG_GLOB_NO_MATCH", 1, 1);
-- MSG_CHMOD_FAILED: Could not change perms on %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 84, "Pure-FTPd: MSG_CHMOD_FAILED", 1, 1);
-- MSG_CHMOD_SUCCESS: Permissions changed on %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 85, "Pure-FTPd: MSG_CHMOD_SUCCESS", 1, 1);
-- MSG_CHMOD_TOTAL_FAILURE: Sorry, but I couldn't change any permission
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 86, "Pure-FTPd: MSG_CHMOD_TOTAL_FAILURE", 1, 1);
-- MSG_ANON_CANT_DELETE: Anonymous users can not delete files
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 87, "Pure-FTPd: MSG_ANON_CANT_DELETE", 1, 1);
-- MSG_ANON_CANT_OVERWRITE: Anonymous users may not overwrite existing files
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 88, "Pure-FTPd: MSG_ANON_CANT_OVERWRITE", 1, 1);
-- MSG_DELE_FAILED: Could not delete %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 89, "Pure-FTPd: MSG_DELE_FAILED", 1, 1);
-- MSG_DELE_SUCCESS: Deleted %s%s%s%s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 90, "Pure-FTPd: MSG_DELE_SUCCESS", 1, 1);
-- MSG_DELE_TOTAL_FAILURE: No file deleted
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 91, "Pure-FTPd: MSG_DELE_TOTAL_FAILURE", 1, 1);
-- MSG_LOAD_TOO_HIGH: "The load was %3.2f when you connected. We do not allow downloads\n" \
--                    "by anonymous users when the load is that high. Uploads are always\n" \
--                    "allowed."
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 92, "Pure-FTPd: MSG_LOAD_TOO_HIGH", 1, 1);
-- MSG_OPEN_FAILURE: Can't open %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 93, "Pure-FTPd: MSG_OPEN_FAILURE", 1, 1);
-- MSG_OPEN_FAILURE2: Can't open that file
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 94, "Pure-FTPd: MSG_OPEN_FAILURE2", 1, 1);
-- MSG_STAT_FAILURE: Can't find file size
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 95, "Pure-FTPd: MSG_STAT_FAILURE", 1, 1);
-- MSG_STAT_FAILURE2: Can't check for file existence
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 96, "Pure-FTPd: MSG_STAT_FAILURE2", 1, 1);
-- MSG_REST_TOO_LARGE_FOR_FILE: Restart offset %lld is too large for file size %lld.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 97, "Pure-FTPd: MSG_REST_TOO_LARGE_FOR_FILE", 1, 1);
-- MSG_REST_RESET: Restart offset reset to 0
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 98, "Pure-FTPd: MSG_REST_RESET", 1, 1);
-- MSG_NOT_REGULAR_FILE: I can only retrieve regular files
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 99, "Pure-FTPd: MSG_NOT_REGULAR_FILE", 1, 1);
-- MSG_NOT_MODERATED: This file has been uploaded by an anonymous user. It has not
--                    yet been approved for downloading by the site administrators.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 100, "Pure-FTPd: MSG_NOT_MODERATED", 1, 1);
-- MSG_RATIO_DENIAL: Sorry, but the upload/download ratio is %u:%u .
--                   You currently uploaded %llu Kb and downloaded %llu Kb.
--                   Please upload some goodies and try leeching later.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 101, "Pure-FTPd: MSG_RATIO_DENIAL", 1, 1);
-- MSG_NO_MORE_TO_DOWNLOAD: Nothing left to download
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 102, "Pure-FTPd: MSG_NO_MORE_TO_DOWNLOAD", 1, 1);
-- MSG_WINNER: The computer is your friend. Trust the computer
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 103, "Pure-FTPd: MSG_WINNER", 1, 1);
-- MSG_KBYTES_LEFT: %.1f kbytes to download
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 104, "Pure-FTPd: MSG_KBYTES_LEFT", 1, 1);
-- MSG_ABORTED: Transfer aborted
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 105, "Pure-FTPd: MSG_ABORTED", 1, 1);
-- MSG_DATA_WRITE_FAILED: Error during write to data connection
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 106, "Pure-FTPd: MSG_DATA_WRITE_FAILED", 1, 1);
-- MSG_DATA_READ_FAILED: Error during read from data connection
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 107, "Pure-FTPd: MSG_DATA_READ_FAILED", 1, 1);
-- MSG_MMAP_FAILED: Unable to map the file into memory
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 108, "Pure-FTPd: MSG_MMAP_FAILED", 1, 1);
-- MSG_WRITE_FAILED: Error during write to file
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 109, "Pure-FTPd: MSG_WRITE_FAILED", 1, 1);
-- MSG_TRANSFER_RATE_M: %.3f seconds (measured here), %.2f Mbytes per second
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 110, "Pure-FTPd: MSG_TRANSFER_RATE_M", 1, 1);
-- MSG_TRANSFER_RATE_K: %.3f seconds (measured here), %.2f Kbytes per second
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 111, "Pure-FTPd: MSG_TRANSFER_RATE_K", 1, 1);
-- MSG_TRANSFER_RATE_B: %.3f seconds (measured here), %.2f bytes per second
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 112, "Pure-FTPd: MSG_TRANSFER_RATE_B", 1, 1);
-- MSG_SPACE_FREE_M: %.1f Mbytes free disk space
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 113, "Pure-FTPd: MSG_SPACE_FREE_M", 1, 1);
-- MSG_SPACE_FREE_K: %f Kbytes free disk space
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 114, "Pure-FTPd: MSG_SPACE_FREE_K", 1, 1);
-- MSG_DOWNLOADED: downloaded
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 115, "Pure-FTPd: MSG_DOWNLOADED", 1, 1);
-- MSG_REST_NOT_NUMERIC: REST needs a numeric parameter
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 116, "Pure-FTPd: MSG_REST_NOT_NUMERIC", 1, 1);
-- MSG_REST_ASCII_STRICT: Reply marker must be 0 in ASCII mode
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 117, "Pure-FTPd: MSG_REST_ASCII_STRICT", 1, 1);
-- MSG_REST_ASCII_WORKAROUND: Restarting at %lld. But we're in ASCII mode
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 118, "Pure-FTPd: MSG_REST_ASCII_WORKAROUND", 1, 1);
-- MSG_REST_SUCCESS: Restarting at %lld
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 119, "Pure-FTPd: MSG_REST_SUCCESS", 1, 1);
-- MSG_SANITY_DIRECTORY_FAILURE: Prohibited directory name
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 120, "Pure-FTPd: MSG_SANITY_DIRECTORY_FAILURE", 1, 1);
-- MSG_SANITY_FILE_FAILURE: Prohibited file name: %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 121, "Pure-FTPd: MSG_SANITY_FILE_FAILURE", 1, 1);
-- MSG_MKD_FAILURE: Can't create directory
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 122, "Pure-FTPd: MSG_MKD_FAILURE", 1, 1);
-- MSG_MKD_SUCCESS: The directory was successfully created
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 123, "Pure-FTPd: MSG_MKD_SUCCESS", 1, 1);
-- MSG_RMD_FAILURE: Can't remove directory
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 124, "Pure-FTPd: MSG_RMD_FAILURE", 1, 1);
-- MSG_RMD_SUCCESS: The directory was successfully removed
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 125, "Pure-FTPd: MSG_RMD_SUCCESS", 1, 1);
-- MSG_TIMESTAMP_FAILURE: Can't get a time stamp
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 126, "Pure-FTPd: MSG_TIMESTAMP_FAILURE", 1, 1);
-- MSG_MODE_ERROR: Only ASCII and binary modes are supported
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 127, "Pure-FTPd: MSG_MODE_ERROR", 1, 1);
-- MSG_CREATE_FAILURE: Unable to create file
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 128, "Pure-FTPd: MSG_CREATE_FAILURE", 1, 1);
-- MSG_ABRT_ONLY: ABRT is the only legal command while uploading
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 129, "Pure-FTPd: MSG_ABRT_ONLY", 1, 1);
-- MSG_UPLOAD_PARTIAL: partially uploaded
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 130, "Pure-FTPd: MSG_UPLOAD_PARTIAL", 1, 1);
-- MSG_REMOVED: removed
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 131, "Pure-FTPd: MSG_REMOVED", 1, 1);
-- MSG_UPLOADED: uploaded
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 132, "Pure-FTPd: MSG_UPLOADED", 1, 1);
-- MSG_GMTIME_FAILURE: Couldn't get the local time
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 133, "Pure-FTPd: MSG_GMTIME_FAILURE", 1, 1);
-- MSG_TYPE_8BIT_FAILURE: Only 8-bit bytes are supported, we're not 10 years ago
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 134, "Pure-FTPd: MSG_TYPE_8BIT_FAILURE", 1, 1);
-- MSG_TYPE_UNKNOWN: Unknown TYPE
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 135, "Pure-FTPd: MSG_TYPE_UNKNOWN", 1, 1);
-- MSG_TYPE_SUCCESS: TYPE is now
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 136, "Pure-FTPd: MSG_TYPE_SUCCESS", 1, 1);
-- MSG_STRU_FAILURE: Only F(ile) is supported
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 137, "Pure-FTPd: MSG_STRU_FAILURE", 1, 1);
-- MSG_MODE_FAILURE: Please use S(tream) mode
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 138, "Pure-FTPd: MSG_MODE_FAILURE", 1, 1);
-- MSG_RENAME_ABORT: Aborting previous rename operation
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 139, "Pure-FTPd: MSG_RENAME_ABORT", 1, 1);
-- MSG_RENAME_RNFR_SUCCESS: RNFR accepted - file exists, ready for destination
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 140, "Pure-FTPd: MSG_RENAME_RNFR_SUCCESS", 1, 1);
-- MSG_FILE_DOESNT_EXIST: Sorry, but that file doesn't exist
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 141, "Pure-FTPd: MSG_FILE_DOESNT_EXIST", 1, 1);
-- MSG_RENAME_ALREADY_THERE: RENAME Failed - destination file already exists
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 142, "Pure-FTPd: MSG_RENAME_ALREADY_THERE", 1, 1);
-- MSG_RENAME_NORNFR: Need RNFR before RNTO
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 143, "Pure-FTPd: MSG_RENAME_NORNFR", 1, 1);
-- MSG_RENAME_FAILURE: Rename/move failure
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 144, "Pure-FTPd: MSG_RENAME_FAILURE", 1, 1);
-- MSG_RENAME_SUCCESS: File successfully renamed or moved
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 145, "Pure-FTPd: MSG_RENAME_SUCCESS", 1, 1);
-- MSG_NO_SUPERSERVER: Please run pure-ftpd within a super-server (like tcpserver)
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 146, "Pure-FTPd: MSG_NO_SUPERSERVER", 1, 1);
-- MSG_NO_FTP_ACCOUNT: Unable to find the 'ftp' account
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 147, "Pure-FTPd: MSG_NO_FTP_ACCOUNT", 1, 1);
-- MSG_CONF_ERR: Configuration error
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 148, "Pure-FTPd: MSG_CONF_ERR", 1, 1);
-- MSG_NO_VIRTUAL_FILE: Missing virtual users file name
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 149, "Pure-FTPd: MSG_NO_VIRTUAL_FILE", 1, 1);
-- MSG_ILLEGAL_THROTTLING: Illegal value for throttling
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 150, "Pure-FTPd: MSG_ILLEGAL_THROTTLING", 1, 1);
-- MSG_ILLEGAL_TRUSTED_GID: Illegal trusted gid for chroot
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 151, "Pure-FTPd: MSG_ILLEGAL_TRUSTED_GID", 1, 1);
-- MSG_ILLEGAL_USER_LIMIT: Illegal user limit
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 152, "Pure-FTPd: MSG_ILLEGAL_USER_LIMIT", 1, 1);
-- MSG_ILLEGAL_FACILITY: Unknown facility name
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 153, "Pure-FTPd: MSG_ILLEGAL_FACILITY", 1, 1);
-- MSG_ILLEGAL_CONFIG_FILE_LDAP: Invalid LDAP configuration file
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 154, "Pure-FTPd: MSG_ILLEGAL_CONFIG_FILE_LDAP", 1, 1);
-- MSG_ILLEGAL_LOAD_LIMIT: Illegal load limit
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 155, "Pure-FTPd: MSG_ILLEGAL_LOAD_LIMIT", 1, 1);
-- MSG_ILLEGAL_PORTS_RANGE: Illegal ports range
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 156, "Pure-FTPd: MSG_ILLEGAL_PORTS_RANGE", 1, 1);
-- MSG_ILLEGAL_LS_LIMITS: Illegal 'ls' limits
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 157, "Pure-FTPd: MSG_ILLEGAL_LS_LIMITS", 1, 1);
-- MSG_ILLEGAL_FORCE_PASSIVE: Illegal forced IP for passive connections
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 158, "Pure-FTPd: MSG_ILLEGAL_FORCE_PASSIVE", 1, 1);
-- MSG_ILLEGAL_RATIO: Illegal upload/download ratio
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 159, "Pure-FTPd: MSG_ILLEGAL_RATIO", 1, 1);
-- MSG_ILLEGAL_UID_LIMIT: Illegal uid limit
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 160, "Pure-FTPd: MSG_ILLEGAL_UID_LIMIT", 1, 1);
-- MSG_ILLEGAL_OPTION: Unknown run-time option
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 161, "Pure-FTPd: MSG_ILLEGAL_OPTION", 1, 1);
-- MSG_LDAP_MISSING_BASE: Missing LDAPBaseDN in the LDAP configuration file
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 162, "Pure-FTPd: MSG_LDAP_MISSING_BASE", 1, 1);
-- MSG_LDAP_WRONG_PARMS: Wrong LDAP parameters
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 163, "Pure-FTPd: MSG_LDAP_WRONG_PARMS", 1, 1);
-- MSG_NEW_CONNECTION: New connection from %s
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 164, "Pure-FTPd: MSG_NEW_CONNECTION", 1, 1);
-- MSG_WELCOME_TO: Welcome to
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 165, "Pure-FTPd: MSG_WELCOME_TO", 1, 1);
-- MSG_MAX_USERS: %lu users (the maximum) are already logged in, sorry
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 166, "Pure-FTPd: MSG_MAX_USERS", 1, 1);
-- MSG_NB_USERS: You are user number %d of %d allowed.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 167, "Pure-FTPd: MSG_NB_USERS", 1, 1);
-- MSG_WELCOME_TIME: Local time is now %02d:%02d. Server port: %u.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 168, "Pure-FTPd: MSG_WELCOME_TIME", 1, 1);
-- MSG_ANONYMOUS_FTP_ONLY: Only anonymous FTP is allowed here
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 169, "Pure-FTPd: MSG_ANONYMOUS_FTP_ONLY", 1, 1);
-- MSG_RATIOS_EVERYONE: RATIOS ARE ENABLED FOR EVERYONE:
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 170, "Pure-FTPd: MSG_RATIOS_EVERYONE", 1, 1);
-- MSG_RATIOS_ANONYMOUS: ANONYMOUS USERS ARE SUBJECT TO AN UL/DL RATIO:
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 171, "Pure-FTPd: MSG_RATIOS_ANONYMOUS", 1, 1);
-- MSG_RATIOS_RULE: to download %u Mb, uploading %u Mb of goodies is mandatory.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 172, "Pure-FTPd: MSG_RATIOS_RULE", 1, 1);
-- MSG_INFO_IDLE_M: You will be disconnected after %lu minutes of inactivity.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 173, "Pure-FTPd: MSG_INFO_IDLE_M", 1, 1);
-- MSG_INFO_IDLE_S: You will be disconnected after %lu seconds of inactivity.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 174, "Pure-FTPd: MSG_INFO_IDLE_S", 1, 1);
-- MSG_CANT_READ_FILE: Sorry, we were unable to read [%s]
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 175, "Pure-FTPd: MSG_CANT_READ_FILE", 1, 1);
-- MSG_LS_TRUNCATED: Output truncated to %u matches
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 176, "Pure-FTPd: MSG_LS_TRUNCATED", 1, 1);
-- MSG_LS_SUCCESS: %u matches total
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 177, "Pure-FTPd: MSG_LS_SUCCESS", 1, 1);
-- MSG_LOGOUT: Logout.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 178, "Pure-FTPd: MSG_LOGOUT", 1, 1);
-- MSG_AUTH_FAILED_LOG: Authentication failed for user [%s]
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 179, "Pure-FTPd: MSG_AUTH_FAILED_LOG", 1, 1);
-- MSG_ILLEGAL_UMASK: Invalid umask
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 180, "Pure-FTPd: MSG_ILLEGAL_UMASK", 1, 1);
-- MSG_STANDALONE_FAILED: Unable to start a standalone server
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 181, "Pure-FTPd: MSG_STANDALONE_FAILED", 1, 1);
-- MSG_NO_ANONYMOUS_LOGIN: This is a private system - No anonymous login
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 182, "Pure-FTPd: MSG_NO_ANONYMOUS_LOGIN", 1, 1);
-- MSG_ANONYMOUS_ANY_PASSWORD: Any password will work
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 183, "Pure-FTPd: MSG_ANONYMOUS_ANY_PASSWORD", 1, 1);
-- MSG_MAX_USERS_IP: Too many connections (%lu) from this IP
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 184, "Pure-FTPd: MSG_MAX_USERS_IP", 1, 1);
-- MSG_ACTIVE_DISABLED: Active mode is disabled
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 185, "Pure-FTPd: MSG_ACTIVE_DISABLED", 1, 1);
-- MSG_TRANSFER_SUCCESSFUL: File successfully transferred
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 186, "Pure-FTPd: MSG_TRANSFER_SUCCESSFUL", 1, 1);
-- MSG_NO_DISK_SPACE: Disk full - please upload later
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 187, "Pure-FTPd: MSG_NO_DISK_SPACE", 1, 1);
-- MSG_OUT_OF_MEMORY: Out of memory
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 188, "Pure-FTPd: MSG_OUT_OF_MEMORY", 1, 1);
-- MSG_ILLEGAL_TRUSTED_IP: Illegal trusted IP address
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 189, "Pure-FTPd: MSG_ILLEGAL_TRUSTED_IP", 1, 1);
-- MSG_NO_ASCII_RESUME: ASCII resume is unsafe, please delete the file first
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 190, "Pure-FTPd: MSG_NO_ASCII_RESUME", 1, 1);
-- MSG_UNKNOWN_ALTLOG: Unknown logging format
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 191, "Pure-FTPd: MSG_UNKNOWN_ALTLOG", 1, 1);
-- MSG_ACCOUNT_DISABLED: Can't login as [%s]: account disabled
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 192, "Pure-FTPd: MSG_ACCOUNT_DISABLED", 1, 1);
-- MSG_SQL_WRONG_PARMS: Wrong SQL parameters
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 193, "Pure-FTPd: MSG_SQL_WRONG_PARMS", 1, 1);
-- MSG_ILLEGAL_CONFIG_FILE_SQL: Invalid SQL configuration file
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 194, "Pure-FTPd: MSG_ILLEGAL_CONFIG_FILE_SQL", 1, 1);
-- MSG_SQL_MISSING_SERVER: Missing server in the SQL configuration file
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 195, "Pure-FTPd: MSG_SQL_MISSING_SERVER", 1, 1);
-- MSG_SQL_DOWN: The SQL server seems to be down
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 196, "Pure-FTPd: MSG_SQL_DOWN", 1, 1);
-- MSG_ILLEGAL_QUOTA: Invalid quota
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 197, "Pure-FTPd: MSG_ILLEGAL_QUOTA", 1, 1);
-- MSG_QUOTA_FILES: %llu files used (%d%%) - authorized: %llu files
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 198, "Pure-FTPd: MSG_QUOTA_FILES", 1, 1);
-- MSG_QUOTA_SIZE: %llu Kbytes used (%d%%) - authorized: %llu Kb
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 199, "Pure-FTPd: MSG_QUOTA_SIZE", 1, 1);
-- MSG_QUOTA_EXCEEDED: Quota exceeded: [%s] won't be saved
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 200, "Pure-FTPd: MSG_QUOTA_EXCEEDED", 1, 1);
-- MSG_AUTH_UNKNOWN: Unknown authentication method
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 201, "Pure-FTPd: MSG_AUTH_UNKNOWN", 1, 1);
-- MSG_PDB_BROKEN: Unable to read the indexed puredb file (or old format detected) - Try pure-pw mkdb
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 202, "Pure-FTPd: MSG_PDB_BROKEN", 1, 1);
-- MSG_ALIASES_ALIAS: %s is an alias for %s.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 203, "Pure-FTPd: MSG_ALIASES_ALIAS", 1, 1);
-- MSG_ALIASES_UNKNOWN: Unknown alias %s.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 204, "Pure-FTPd: MSG_ALIASES_UNKNOWN", 1, 1);
-- MSG_ALIASES_BROKEN_FILE: Damaged aliases file
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 205, "Pure-FTPd: MSG_ALIASES_BROKEN_FILE", 1, 1);
-- MSG_ALIASES_LIST: The following aliases are available :
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 206, "Pure-FTPd: MSG_ALIASES_LIST", 1, 1);
-- MSG_PERUSER_MAX: I can't accept more than %lu connections as the same user
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 207, "Pure-FTPd: MSG_PERUSER_MAX", 1, 1);
-- MSG_IPV6_OK: IPv6 connections are also welcome on this server.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 208, "Pure-FTPd: MSG_IPV6_OK", 1, 1);
-- MSG_TLS_INFO: SSL/TLS: Enabled %s with %s, %d secret bits cipher
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 209, "Pure-FTPd: MSG_TLS_INFO", 1, 1);
-- MSG_TLS_WEAK: SSL/TLS: Cipher too weak
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 210, "Pure-FTPd: MSG_TLS_WEAK", 1, 1);
-- MSG_TLS_NEEDED: Sorry, cleartext sessions are not accepted on this server.
--                 Please reconnect using SSL/TLS security mechanisms.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 211, "Pure-FTPd: MSG_TLS_NEEDED", 1, 1); \
-- MSG_ILLEGAL_CHARSET: Illegal charset
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 212, "Pure-FTPd: MSG_ILLEGAL_CHARSET", 1, 1);
-- MSG_TLS_NO_CTX: SSL/TLS: Context not found. Exiting.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 213, "Pure-FTPd: MSG_TLS_NO_CTX", 1, 1);
-- MSG_PROT_OK: Data protection level set to \"%s\"
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 214, "Pure-FTPd: MSG_PROT_OK", 1, 1);
-- MSG_PROT_PRIVATE_NEEDED: Data connection cannot be opened with this PROT setting.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 215, "Pure-FTPd: MSG_PROT_PRIVATE_NEEDED", 1, 1);
-- MSG_PROT_UNKNOWN_LEVEL: Protection level %s not understood. Fallback to \"%s\"
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 216, "Pure-FTPd: MSG_PROT_UNKNOWN_LEVEL", 1, 1);
-- MSG_PROT_BEFORE_PBSZ: PROT must be preceded by a successful PBSZ command
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 217, "Pure-FTPd: MSG_PROT_BEFORE_PBSZ", 1, 1);
-- MSG_WARN_LDAP_USERPASS_EMPTY: LDAP returned no userPassword attribute, check LDAP access rights.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 218, "Pure-FTPd: MSG_WARN_LDAP_USERPASS_EMPTY", 1, 1);
-- MSG_LDAP_INVALID_AUTH_METHOD: Invalid LDAPAuthMethod in the configuration file. Should be 'bind' or 'password'.
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1616, 219, "Pure-FTPd: MSG_LDAP_INVALID_AUTH_METHOD", 1, 1);

