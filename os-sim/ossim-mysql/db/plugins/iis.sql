-- iis
-- plugin_id: 1502
--
DELETE FROM plugin WHERE id = "1502";
DELETE FROM plugin_sid where plugin_id = "1502";


INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1502, 1, 'iis', 'IIS');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1502, 200, NULL, NULL, 'IIS: OK', 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 201, NULL, NULL, 'IIS: Created');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 202, NULL, NULL, 'IIS: Accepted');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 203, NULL, NULL, 'IIS: Non-Authorative Information');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 204, NULL, NULL, 'IIS: No Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 205, NULL, NULL, 'IIS: Reset Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 206, NULL, NULL, 'IIS: Partial Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 300, NULL, NULL, 'IIS: Multiple Choices');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 301, NULL, NULL, 'IIS: Moved Permanently');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 302, NULL, NULL, 'IIS: Moved Temporarily');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 303, NULL, NULL, 'IIS: See Other');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 304, NULL, NULL, 'IIS: Not Modified');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 305, NULL, NULL, 'IIS: Use Proxy');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 400, NULL, NULL, 'IIS: Bad Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1502, 401, NULL, NULL, 'IIS: Authorization Required', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 402, NULL, NULL, 'IIS: Payment Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1502, 403, NULL, NULL, 'IIS: Forbidden', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 404, NULL, NULL, 'IIS: Not Found');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 405, NULL, NULL, 'IIS: Method Not Allowed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 406, NULL, NULL, 'IIS: Not Acceptable (encoding)');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 407, NULL, NULL, 'IIS: Proxy Authentication Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 408, NULL, NULL, 'IIS: Request Timed Out');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 409, NULL, NULL, 'IIS: Conflicting Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 410, NULL, NULL, 'IIS: Gone');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 411, NULL, NULL, 'IIS: Content Length Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 412, NULL, NULL, 'IIS: Precondition Failed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 413, NULL, NULL, 'IIS: Request Entity Too Long');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 414, NULL, NULL, 'IIS: Request URI Too Long');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 415, NULL, NULL, 'IIS: Unsupported Media Type');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 500, NULL, NULL, 'IIS: Internal Server Error');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 501, NULL, NULL, 'IIS: Not implemented');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 502, NULL, NULL, 'IIS: Bad Gateway');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 503, NULL, NULL, 'IIS: Service Unavailable');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 504, NULL, NULL, 'IIS: Gateway Timeout');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 505, NULL, NULL, 'IIS: HTTP Version Not Supported');


