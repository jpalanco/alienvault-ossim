-- modsecurity
-- plugin_id: 1561

DELETE FROM plugin WHERE id = "1561";
DELETE FROM plugin_sid where plugin_id = "1561";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1561, 1, 'modsecurity', 'ModSecurity');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 201, NULL, NULL, 'modsecurity: Created');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 202, NULL, NULL, 'modsecurity: Accepted');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 203, NULL, NULL, 'modsecurity: Non-Authorative Information');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 204, NULL, NULL, 'modsecurity: No Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 205, NULL, NULL, 'modsecurity: Reset Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 206, NULL, NULL, 'modsecurity: Partial Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 300, NULL, NULL, 'modsecurity: Multiple Choices');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 301, NULL, NULL, 'modsecurity: Moved Permanently');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 302, NULL, NULL, 'modsecurity: Moved Temporarily');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 303, NULL, NULL, 'modsecurity: See Other');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 304, NULL, NULL, 'modsecurity: Not Modified');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 305, NULL, NULL, 'modsecurity: Use Proxy');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 307, NULL, NULL, 'modsecurity: Temporary Redirect');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 400, NULL, NULL, 'modsecurity: Bad Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1561, 401, NULL, NULL, 'modsecurity: Unauthorized', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 402, NULL, NULL, 'modsecurity: Payment Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1561, 403, NULL, NULL, 'modsecurity: Forbidden', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 404, NULL, NULL, 'modsecurity: Not Found');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 405, NULL, NULL, 'modsecurity: Method Not Allowed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 406, NULL, NULL, 'modsecurity: Not Acceptable (encoding)');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 407, NULL, NULL, 'modsecurity: Proxy Authentication Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 408, NULL, NULL, 'modsecurity: Request Timed Out');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 409, NULL, NULL, 'modsecurity: Conflicting Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 410, NULL, NULL, 'modsecurity: Gone');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 411, NULL, NULL, 'modsecurity: Content Length Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 412, NULL, NULL, 'modsecurity: Precondition Failed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 413, NULL, NULL, 'modsecurity: Request Entity Too Long');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 414, NULL, NULL, 'modsecurity: Request URI Too Long');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 415, NULL, NULL, 'modsecurity: Unsupported Media Type');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 500, NULL, NULL, 'modsecurity: Internal Server Error');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 501, NULL, NULL, 'modsecurity: Not implemented');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 502, NULL, NULL, 'modsecurity: Bad Gateway');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 503, NULL, NULL, 'modsecurity: Service Unavailable');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 504, NULL, NULL, 'modsecurity: Gateway Timeout');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 505, NULL, NULL, 'modsecurity: HTTP Version Not Supported');
