--

-- Table structure for table `delayed_jobs`

--



DROP TABLE IF EXISTS `delayed_jobs`;

CREATE TABLE IF NOT EXISTS `delayed_jobs` (

  `id` int(11) NOT NULL AUTO_INCREMENT,

  `pid` int(11) DEFAULT NULL,

  `priority` int(11) NOT NULL,

  `handler` text NOT NULL,

  `run_at` timestamp NULL DEFAULT NULL,

  `locked_at` timestamp NULL DEFAULT NULL,

  `queue` text NOT NULL,

  `finished_at` timestamp NULL DEFAULT NULL,

  `failed_at` timestamp NULL DEFAULT NULL,

  `attempts` int(11) NOT NULL,

  `kill_at` time DEFAULT NULL,

  UNIQUE KEY `id` (`id`)

) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=290 ;



--
