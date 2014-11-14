-- isa server
-- plugin_id: 1565

DELETE FROM plugin WHERE id = "1565";
DELETE FROM plugin_sid where plugin_id = "1565";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1565, 1, 'isa-server', 'Microsoft ISA Server');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1565, 1, NULL, NULL, 'isa-server: server error', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1565, 200, NULL, NULL, 'isa-server: OK', 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 201, NULL, NULL, 'isa-server: Created');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 202, NULL, NULL, 'isa-server: Accepted');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 203, NULL, NULL, 'isa-server: Non-Authorative Information');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 204, NULL, NULL, 'isa-server: No Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 205, NULL, NULL, 'isa-server: Reset Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 206, NULL, NULL, 'isa-server: Partial Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 300, NULL, NULL, 'isa-server: Multiple Choices');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 301, NULL, NULL, 'isa-server: Moved Permanently');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 302, NULL, NULL, 'isa-server: Moved Temporarily');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 303, NULL, NULL, 'isa-server: See Other');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 304, NULL, NULL, 'isa-server: Not Modified');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 305, NULL, NULL, 'isa-server: Use Proxy');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 307, NULL, NULL, 'isa-server: Temporary Redirect');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 400, NULL, NULL, 'isa-server: Bad Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1565, 401, NULL, NULL, 'isa-server: Unauthorized', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 402, NULL, NULL, 'isa-server: Payment Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1565, 403, NULL, NULL, 'isa-server: Forbidden', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 404, NULL, NULL, 'isa-server: Not Found');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 405, NULL, NULL, 'isa-server: Method Not Allowed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 406, NULL, NULL, 'isa-server: Not Acceptable (encoding)');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 407, NULL, NULL, 'isa-server: Proxy Authentication Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 408, NULL, NULL, 'isa-server: Request Timed Out');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 409, NULL, NULL, 'isa-server: Conflicting Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 410, NULL, NULL, 'isa-server: Gone');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 411, NULL, NULL, 'isa-server: Content Length Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 412, NULL, NULL, 'isa-server: Precondition Failed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 413, NULL, NULL, 'isa-server: Request Entity Too Long');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 414, NULL, NULL, 'isa-server: Request URI Too Long');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 415, NULL, NULL, 'isa-server: Unsupported Media Type');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 500, NULL, NULL, 'isa-server: Internal Server Error');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 501, NULL, NULL, 'isa-server: Not implemented');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 502, NULL, NULL, 'isa-server: Bad Gateway');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 503, NULL, NULL, 'isa-server: Service Unavailable');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 504, NULL, NULL, 'isa-server: Gateway Timeout');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1565, 505, NULL, NULL, 'isa-server: HTTP Version Not Supported');


