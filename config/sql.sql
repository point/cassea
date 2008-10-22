--
-- Table structure for table `user`
--
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(22) character set utf8 collate utf8_unicode_ci NOT NULL,
  `email` varchar(128) character set utf8 collate utf8_unicode_ci NOT NULL,
  `password` varchar(32) NOT NULL,
  `sold` varchar(16) NOT NULL,
  `status` enum('0','1','2','3','4','5') default NULL,
  `kap1` int(11) default NULL,
  `kap2` int(11) default NULL,
  `is_delete` tinyint(1) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `key_status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
-- --------------------------------------------------------
--
-- Table structure for table `user_session`
--
CREATE TABLE IF NOT EXISTS `user_session` (
  `id` varchar(32) NOT NULL,
  `user_id` int(11) NOT NULL default '0',
  `user_ip` varchar(32) default NULL,
  `user_port` varchar(10) default NULL,
  `cast` varchar(64) default NULL,
  `start` int(11) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `langs`
--

CREATE TABLE IF NOT EXISTS `langs` (
  `lang_id` int(11) NOT NULL,
  `package` varchar(255) NOT NULL,
  `k` varchar(255) NOT NULL,
  `v` text,
  PRIMARY KEY  (`lang_id`,`package`,`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `language`
--

CREATE TABLE IF NOT EXISTS `language` (
  `id` int(11) NOT NULL,
  `short_name` varchar(255) default NULL,
  `name` varchar(255) default NULL,
  `default` enum('1','0') NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `langs`
--
ALTER TABLE `langs`
  ADD CONSTRAINT `r_langs_to_language` FOREIGN KEY (`lang_id`) REFERENCES `language` (`id`) ON DELETE CASCADE;
