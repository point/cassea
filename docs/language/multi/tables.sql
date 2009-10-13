CREATE TABLE IF NOT EXISTS `language` (
    `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
    `short_name` varchar(2) NOT NULL,
    `name` varchar(255) NOT NULL,
    `default` enum('1','0') NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `short_name` (`short_name`)
)DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `langs` (
    `lang_id` tinyint unsigned NOT NULL,
    `package` varchar(96) NOT NULL,
    `k` varchar(200) NOT NULL,
    `v` text,
    PRIMARY KEY (`lang_id`,`package`,`k`)
)DEFAULT CHARSET=utf8;
