SET FOREIGN_KEY_CHECKS=0;

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Table structure for table `ltc`
--

DROP TABLE IF EXISTS `ltc`;
CREATE TABLE IF NOT EXISTS `ltc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language` int(11) NOT NULL,
  `key` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`language`,`key`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

SET FOREIGN_KEY_CHECKS=1;

