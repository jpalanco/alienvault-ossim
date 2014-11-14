-- MS Exchange 
-- plugin_id: 1603
--

DELETE FROM plugin WHERE id = 1603;
DELETE FROM plugin_sid WHERE plugin_id=1603;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1603, 1, 'Exchange', 'Exchange Message Tracking');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 5000, NULL, NULL, 1, 1, 'Exchange: The message was received from a server, a connector, or a gateway.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1, NULL, NULL, 1, 1, 'Exchange: An X.400 probe was received from a gateway, a link, or a message transfer agent (MTA).');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 2, NULL, NULL, 1, 1, 'Exchange: A delivery receipt or a non-delivery report (NDR) was received from a server, a connector, or a gateway. ');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 4, NULL, NULL, 1, 1, 'Exchange: The message was sent by the client.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 5, NULL, NULL, 1, 1, 'Exchange: An X.400 probe was received from a user.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 6, NULL, NULL, 1, 1, 'Exchange: An X.400 probe was sent to a gateway, a link, or an MTA.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 7, NULL, NULL, 1, 1, 'Exchange: The message was sent to a server, a connector, or a gateway.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 8, NULL, NULL, 1, 1, 'Exchange: A delivery receipt or an NDR was sent to a server, a connector, or a gateway.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 9, NULL, NULL, 1, 1, 'Exchange: The message was delivered to a mailbox or a public folder.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 10, NULL, NULL, 1, 1, 'Exchange: A delivery receipt or an NDR was delivered to a mailbox.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 18, NULL, NULL, 1, 1, 'Exchange: StartAssocByMTSUser');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 23, NULL, NULL, 1, 1, 'Exchange: ReleaseAssocByMTSUse');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 28, NULL, NULL, 1, 1, 'Exchange: The message was sent to mailboxes other than the mailboxes of the recipients.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 29, NULL, NULL, 1, 1, 'Exchange: The message was routed to an alternative path.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 31, NULL, NULL, 1, 1, 'Exchange: An X.400 message was downgraded to 1984 format before relay.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 33, NULL, NULL, 1, 1, 'Exchange: The number of delivery receipts or of NDRs exceeded a threshold and the reports were deleted.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 34, NULL, NULL, 1, 1, 'Exchange: A delivery receipt or an NDR was created.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 43, NULL, NULL, 1, 1, 'Exchange: A delivery receipt or an NDR could not be routed and was deleted from the queue.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 50, NULL, NULL, 1, 1, 'Exchange: The administrator deleted an X.400 message that was queued for a gateway.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 51, NULL, NULL, 1, 1, 'Exchange: The administrator deleted an X.400 probe that was queued for a gateway.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 52, NULL, NULL, 1, 1, 'Exchange: The administrator deleted an X.400 report that was queued for a gateway.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1000, NULL, NULL, 1, 1, 'Exchange: The sender and the recipient are on the same server.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1001, NULL, NULL, 1, 1, 'Exchange: Mail was received from another MAPI system across a connector or across a gateway.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1002, NULL, NULL, 1, 1, 'Exchange: Mail was sent to another MAPI system across a connector or across a gateway.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1003, NULL, NULL, 1, 1, 'Exchange: The message was sent through a gateway.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1004, NULL, NULL, 1, 1, 'Exchange: The message was received from a gateway.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1005, NULL, NULL, 1, 1, 'Exchange: A delivery receipt or an NDR was received from a gateway. ');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1006, NULL, NULL, 1, 1, 'Exchange: A delivery receipt or an NDR was sent through a gateway.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1007, NULL, NULL, 1, 1, 'Exchange: A gateway generated an NDR for a message.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1010, NULL, NULL, 1, 1, 'Exchange: Outgoing mail was queued for delivery by the Internet Mail Service.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1011, NULL, NULL, 1, 1, 'Exchange: Outgoing mail was transferred to an Internet recipient.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1012, NULL, NULL, 1, 1, 'Exchange: Incoming mail was received from by the Internet Mail Service.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1013, NULL, NULL, 1, 1, 'Exchange: Incoming mail that was received by the Internet Mail Service was transferred to the information store.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1014, NULL, NULL, 1, 1, 'Exchange: An Internet message is being rerouted or forwarded to the correct location.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1015, NULL, NULL, 1, 1, 'Exchange: A delivery receipt or an NDR was received by the Internet Mail Service ');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1016, NULL, NULL, 1, 1, 'Exchange: A delivery receipt or an NDR was sent to the Internet Mail Service.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1017, NULL, NULL, 1, 1, 'Exchange: A delivery receipt or an NDR was created.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1018, NULL, NULL, 1, 1, 'Exchange: The receipt or the NDR could not be delivered and was absorbed. (You cannot send an NDR for an NDR.)');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1019, NULL, NULL, 1, 1, 'Exchange: A new message is submitted to Advanced Queuing.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1020, NULL, NULL, 1, 1, 'Exchange: A message is about to be sent over the wire by SMTP.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1021, NULL, NULL, 1, 1, 'Exchange: The message was transferred to the Badmail folder.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1022, NULL, NULL, 1, 1, 'Exchange: A fatal Advanced Queuing error occurred. Information about the failure was written to the Event Manager.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1023, NULL, NULL, 1, 1, 'Exchange: A message was successfully delivered by a store drive (logged by Advanced Queue).');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1024, NULL, NULL, 1, 1, 'Exchange: Advanced Queuing submitted a message to the categorizer.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1025, NULL, NULL, 1, 1, 'Exchange: A new message was submitted to Advanced Queuing.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1027, NULL, NULL, 1, 1, 'Exchange: A message was submitted to the store driver by the MTA.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1028, NULL, NULL, 1, 1, 'Exchange: The store driver successfully delivered a message (logged by store driver). ');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1029, NULL, NULL, 1, 1, 'Exchange: The store driver transferred the message to the MTA.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1030, NULL, NULL, 1, 1, 'Exchange: All recipients were sent an NDR.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1031, NULL, NULL, 1, 1, 'Exchange: The outgoing message was successfully transferred.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1032, NULL, NULL, 1, 1, 'Exchange: SMTP message scheduled to retry categorization');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1033, NULL, NULL, 1, 1, 'Exchange: SMTP message categorized and queued for routing');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1034, NULL, NULL, 1, 1, 'Exchange: SMTP message routed and queued for remote delivery');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1035, NULL, NULL, 1, 1, 'Exchange: SMTP message scheduled to retry routing');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1036, NULL, NULL, 1, 1, 'Exchange: SMTP message queued for local delivery ');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1037, NULL, NULL, 1, 1, 'Exchange: SMTP message scheduled to retry local delivery');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1038, NULL, NULL, 1, 1, 'Exchange: SMTP message routed and queued for gateway delivery');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1039, NULL, NULL, 1, 1, 'Exchange: SMTP message deleted by Intelligent Message Filtering');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1040, NULL, NULL, 1, 1, 'Exchange: SMTP message rejected by Intelligent Message Filtering');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1041, NULL, NULL, 1, 1, 'Exchange: SMTP message archived by Intelligent Message Filtering');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1603, 1042, NULL, NULL, 1, 1, 'Exchange: Message redirected to the alternate recipient');



