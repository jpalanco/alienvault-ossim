-- USB Udev Hardware detection
-- plugin_id: 1640

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1640, 1, 'usbudev', 'USB Udev Hardware detection');

INSERT IGNORE INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1640, 1, 'Usbudev: An USB Device was added to the system' , 3, 5);
INSERT IGNORE INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1640, 2, 'Usbudev: An USB Device was removed from the system' , 3, 5);
