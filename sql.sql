-- phpMyAdmin SQL Dump
-- version 2.11.5
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Nov 18, 2008 at 06:30 PM
-- Server version: 5.0.67
-- PHP Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `intvideo`
--

-- --------------------------------------------------------

--
-- Table structure for table `acl`
--

DROP TABLE IF EXISTS `acl`;
CREATE TABLE IF NOT EXISTS `acl` (
  `user_id` int(11) NOT NULL,
  `groups` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `acl`
--

INSERT INTO `acl` (`user_id`, `groups`) VALUES
(8, 'user');

-- --------------------------------------------------------

--
-- Table structure for table `help`
--

DROP TABLE IF EXISTS `help`;
CREATE TABLE IF NOT EXISTS `help` (
  `oid` int(11) NOT NULL,
  `controller` varchar(255) NOT NULL,
  `page` varchar(255) NOT NULL,
  KEY `controller` (`controller`,`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `help`
--

INSERT INTO `help` (`oid`, `controller`, `page`) VALUES
(18, 'admin', 'advview'),
(19, 'admin', 'finance'),
(20, 'admin', 'index'),
(21, 'billy', 'index'),
(22, 'help', 'edit'),
(23, 'index', 'base'),
(24, 'index', 'header'),
(25, 'index', 'index'),
(26, 'index', 'registration'),
(27, 'index', 'registration_confirm'),
(28, 'index', 'registration_done'),
(29, 'marketing', 'marketing'),
(30, 'marketing', 'penalty'),
(31, 'news', 'index'),
(32, 'news', 'index1'),
(33, 'news', 'newsadd'),
(34, 'news', 'newsedit'),
(35, 'news', 'newslist'),
(36, 'news', 'newslist1'),
(37, 'pager', 'createmsg'),
(38, 'point', 'ajax'),
(39, 'point', 'base2'),
(40, 'point', 'index'),
(41, 'point', 'index'),
(42, 'point', 'qwe'),
(43, 'user', 'LoginForm'),
(44, 'user', 'LogoutForm'),
(45, 'user', 'RegistrationConfirm'),
(46, 'user', 'RegistrationDone'),
(47, 'user', 'RegistrationForm'),
(48, 'user', 'UserInfo'),
(49, 'user', 'freecells'),
(50, 'user', 'getInvite'),
(51, 'user', 'invite'),
(52, 'user', 'moderation'),
(53, 'user', 'nouserfound'),
(54, 'user', 'useradd'),
(55, 'user', 'userfound'),
(56, 'user', 'usersearch'),
(57, 'wm', 'index'),
(58, 'yaexpert', 'index');

-- --------------------------------------------------------

--
-- Table structure for table `langs`
--

DROP TABLE IF EXISTS `langs`;
CREATE TABLE IF NOT EXISTS `langs` (
  `lang_id` int(11) NOT NULL,
  `package` varchar(255) NOT NULL,
  `k` varchar(255) NOT NULL,
  `v` text,
  PRIMARY KEY  (`lang_id`,`package`,`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `langs`
--

INSERT INTO `langs` (`lang_id`, `package`, `k`, `v`) VALUES
(1, 'advview', 'AC_LENGTH_MAX', 'Максимальная продолжительность РК'),
(1, 'advview', 'AC_LENGTH_MIN', 'Минимальная продолжительность РК'),
(1, 'advview', 'AC_VOTE_ANSWERS', 'Максимальное количество ответов в голосовании в РК'),
(1, 'advview', 'AC_VOTE_QUESTIONS', 'Максимальное количество вопросов голосования в РК'),
(1, 'common', 'ago', 'назад'),
(1, 'common', 'date_in', 'через'),
(1, 'common', 'day_1', 'день'),
(1, 'common', 'day_2', 'дня'),
(1, 'common', 'day_3', 'дней'),
(1, 'common', 'DELETE', 'удалить'),
(1, 'common', 'EDIT', 'редактировать'),
(1, 'common', 'GOLD_STATUS', 'Gold-Star'),
(1, 'common', 'GREEN_STATUS', 'Green-Star'),
(1, 'common', 'hour', 'час'),
(1, 'common', 'hours', 'чаосв'),
(1, 'common', 'hour_1', 'час'),
(1, 'common', 'hour_2', 'часа'),
(1, 'common', 'hour_3', 'часов'),
(1, 'common', 'INDIGO_STATUS', 'Indigo-Star'),
(1, 'common', 'minutes', 'минут'),
(1, 'common', 'minutes_1', 'минуту'),
(1, 'common', 'minutes_2', 'минуты'),
(1, 'common', 'minutes_3', 'минут'),
(1, 'common', 'NEXT', 'Продолжить'),
(1, 'common', 'NULL_STATUS', 'None-Star'),
(1, 'common', 'SEARCH', 'Искать'),
(1, 'common', 'seconds', 'секунд'),
(1, 'common', 'seconds_1', 'секунду'),
(1, 'common', 'seconds_2', 'секунды'),
(1, 'common', 'seconds_3', 'секунд'),
(1, 'common', 'SILVER_STATUS', 'Silver-Star'),
(1, 'common', 'SUPER_STATUS', 'Super-Star'),
(1, 'common', 'today', 'сегодня'),
(1, 'common', 'tomorrow', 'завтра'),
(1, 'common', 'UPDATE', 'Сохранить'),
(1, 'common', 'WIDGET_BACK_LINK', '[Назад]'),
(1, 'common', 'yesterday', 'вчера'),
(1, 'common', 'Yesterday_at', 'Вчера в'),
(1, 'marketing', 'cSc1', 'Благодарность спонсору 1-го уровня'),
(1, 'marketing', 'cSc2', 'Благодарность спонсору 2-го уровня'),
(1, 'marketing', 'cSc3', 'Благодарность спонсору 3-го уровня'),
(1, 'marketing', 'cSc4', 'Благодарность спонсору 5-го уровня'),
(1, 'marketing', 'cSc5', 'Благодарность спонсору 5-го уровня'),
(1, 'marketing', 'cScI', 'Благодарность бонусу бесконечности'),
(1, 'marketing', 'cU', 'Личный доход рекламодателя'),
(1, 'marketing', 'maxtdstat', 'Максимальное кол-во статических ЦГ'),
(1, 'marketing', 'maxtgdyn', 'Максимальное кол-во динамических ЦГ'),
(1, 'marketing', 'maxtgmix', 'Максимальное кол-во смешанных ЦГ'),
(1, 'marketing', 'PARAM', 'Параметры'),
(1, 'marketing', 'paydelay', 'Кол-во дней задержки оплаты статуса'),
(1, 'marketing', 'payment', 'Ежегодный взнос'),
(1, 'marketing', 'PENALTY_KPA', 'КПА'),
(1, 'marketing', 'PENALTY_SALARY', 'Доход'),
(1, 'marketing', 'pm', 'Оплата за секунду ролика'),
(1, 'marketing', 'sSv1', 'Покупка статуса: спонсору 1-го уровня'),
(1, 'marketing', 'sSv2', 'Покупка статуса: спонсору 2-го уровня'),
(1, 'marketing', 'sSv3', 'Покупка статуса: спонсору 3-го уровня'),
(1, 'marketing', 'sSv4', 'Покупка статуса: спонсору 4-го уровня'),
(1, 'marketing', 'sSv5', 'Покупка статуса: спонсору 5-го уровня'),
(1, 'marketing', 'sSvI', 'Покупка статуса: бонусу бесконечности'),
(1, 'marketing', 'VALUE', 'Значение'),
(1, 'marketing', 'vC', 'Просмотр РК: благодарность рекламодателю'),
(1, 'marketing', 'vR1', 'Выплаты с реферала 1 уровня'),
(1, 'marketing', 'vR2', 'Выплаты с реферала 2 уровня'),
(1, 'marketing', 'vR3', 'Выплаты с реферала 3 уровня'),
(1, 'marketing', 'vR4', 'Выплаты с реферала 4 уровня'),
(1, 'marketing', 'vR5', 'Выплаты с реферала 5 уровня'),
(1, 'marketing', 'vRI', 'Выплаты с бонуса бесконечности'),
(1, 'marketing', 'vSv1', 'Просмотр РК: благодарность спонсору 1-го уровня'),
(1, 'marketing', 'vSv2', 'Просмотр РК: благодарность спонсору 2-го уровня'),
(1, 'marketing', 'vSv3', 'Просмотр РК: благодарность спонсору 3-го уровня'),
(1, 'marketing', 'vSv4', 'Просмотр РК: благодарность спонсору 4-го уровня'),
(1, 'marketing', 'vSv5', 'Просмотр РК: благодарность спонсору 5-го уровня'),
(1, 'marketing', 'vSvI', 'Просмотр РК: благодарность бонусу бесконечности'),
(1, 'marketing', 'vU', 'Высплаты с личного просмотра'),
(1, 'news', 'ACTION', 'Действие'),
(1, 'news', 'CONTENT', 'Содержание'),
(1, 'news', 'DATE', 'Дата'),
(1, 'news', 'IMAGE', 'Изображение'),
(1, 'news', 'LINK_FULL', 'Подробнее'),
(1, 'news', 'NEWS_ADD', 'Создать новость'),
(1, 'news', 'SHORT_CONTENT', 'Краткое содержание'),
(1, 'news', 'TITLE', 'Название'),
(1, 'pager', 'DATE', 'Дата'),
(1, 'pager', 'FROM', 'Отправитель'),
(1, 'pager', 'LISTOFINBOX', 'Список входящих сообщений'),
(1, 'pager', 'LISTOFOUTBOX', 'Список исходящих сообщений'),
(1, 'pager', 'MENU_CREATEMSG', 'Написать'),
(1, 'pager', 'MENU_INBOX', 'Входящие'),
(1, 'pager', 'MENU_OUTBOX', 'Исходящие'),
(1, 'pager', 'MSG_COUNT', 'Количество сообщений в списке'),
(1, 'pager', 'RECIEVER', 'Получатель'),
(1, 'pager', 'SEND', 'Отправить'),
(1, 'pager', 'SEND_NEW_MSG', 'Отправить новое сообщение'),
(1, 'pager', 'SUBJECT', 'Тема'),
(1, 'pager', 'TEXT', 'Сообщение'),
(1, 'pager', 'TO', 'Кому'),
(1, 'pager', 'VIEW_MSG', 'Просмотр сообщения'),
(1, 'user', 'BAN', 'Забанить'),
(1, 'user', 'BLACKLIST', 'Черный список'),
(1, 'user', 'CELLNUM', '# ячейчки'),
(1, 'user', 'ENTER_EMAIL', 'Введите email'),
(1, 'user', 'FREECELLS', 'Свободные ячейки'),
(1, 'user', 'IB', 'бб'),
(1, 'user', 'LEVEL', 'ур.'),
(1, 'user', 'MOVIECOUNT', 'Кол-во роликов'),
(1, 'user', 'SALLARY', 'доход'),
(1, 'user', 'STATUS', 'Статус'),
(1, 'user', 'UNBAN', 'Разбанить'),
(1, 'user', 'USERCOUNT', 'кол-во людей'),
(1, 'user', 'USERNAME', 'Пользователь'),
(1, 'user', 'USER_FOUND', 'Найден пользователь'),
(1, 'user', 'USER_SEARCH', 'Поиск пользователя'),
(1, 'wm', 'OUTPUT_DAY', 'Регулярность вывода денег (в днях)'),
(1, 'wm', 'OUTPUT_TIME', 'Время вывода денег (час:мин)');

-- --------------------------------------------------------

--
-- Table structure for table `language`
--

DROP TABLE IF EXISTS `language`;
CREATE TABLE IF NOT EXISTS `language` (
  `id` int(11) NOT NULL,
  `short_name` varchar(255) default NULL,
  `name` varchar(255) default NULL,
  `default` enum('1','0') NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `language`
--

INSERT INTO `language` (`id`, `short_name`, `name`, `default`) VALUES
(1, 'ru', 'Русский', '1');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(128) character set utf8 collate utf8_unicode_ci NOT NULL,
  `email` varchar(128) character set utf8 collate utf8_unicode_ci NOT NULL,
  `password` varchar(32) NOT NULL,
  `sold` varchar(16) NOT NULL,
  `state` enum('active','ban','delete') NOT NULL default 'active',
  `status` enum('0','1','2','3','4','5') default '0',
  `kap1` int(11) default '0',
  `kap2` int(11) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `key_status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Òàáëèöà ïîëüçîâàòåëåé\r\n';

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `login`, `email`, `password`, `sold`, `state`, `status`, `kap1`, `kap2`) VALUES
(1, 'billy@intvideo.tv', 'alexeymirniy@gmail.com', 'billy', 'billy', 'active', '2', NULL, NULL),
(3, 't.remayeva11@gmail.com', 't.remayeva11@gmail.com', 'tastro', '', 'active', '0', 0, 0),
(4, 't.remayeva5@gmail.com', 't.remayeva5@gmail.com', 'qweqwe', '', 'delete', '0', 0, 0),
(5, 'newuser@user.new', '', 'newuser', '', 'active', '0', 0, 0),
(6, 't.remayeva@gmail.com', 't.remayeva@gmail.com', 'qweqwe', '', 'active', '0', 0, 0),
(8, 'point@gmail.com', 't.remayeva@gmail.com', 'point', '', 'active', '0', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_ban`
--

DROP TABLE IF EXISTS `user_ban`;
CREATE TABLE IF NOT EXISTS `user_ban` (
  `uid` int(11) NOT NULL,
  `adc_ban_count` int(11) default '0',
  PRIMARY KEY  (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Òàáëèöà çàáàíåííûõ ïîëüçîâàòåëåé è ïîëüçîâàòåëåé ó êîòîðûõ á';

--
-- Dumping data for table `user_ban`
--

INSERT INTO `user_ban` (`uid`, `adc_ban_count`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_invite`
--

DROP TABLE IF EXISTS `user_invite`;
CREATE TABLE IF NOT EXISTS `user_invite` (
  `die_time` int(11) NOT NULL,
  `inviter` int(11) NOT NULL,
  `invite` varchar(32) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`invite`),
  KEY `invite_time` (`die_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

--
-- Dumping data for table `user_invite`
--

INSERT INTO `user_invite` (`die_time`, `inviter`, `invite`) VALUES
(1225571126, 1, '0a4b57a36af0e151541042fd11f598f9'),
(1225571106, 1, '2cc33aec7d86b9886044c669d66db20e'),
(1225571125, 1, '4c281f43b61e787c2d170e8f72c15ab6'),
(1225571127, 1, '57ebd7b03f4de20f6924ce74ece20eb6'),
(1225571116, 1, '661030a82bf1afecf74b736d95e058f8'),
(1225571387, 1, '69275473db80c06b123ca7851a98b6b1'),
(1225619113, 1, '911493d65044ebbda26ec68abdf8886f'),
(1225571126, 1, 'a0c882cf7e625c0d28cebf401172ad9e'),
(1225571262, 1, 'aae64be7bee159403c36018c93882dae'),
(1225571255, 1, 'aecca1309acc0576a88e6116e38e2a41'),
(1225571094, 1, 'b749c8474c5b8b2c24ddf21d6aa9b38f'),
(1225571125, 1, 'c319fa3a06a005b017e823522e48cb0b'),
(1225571126, 1, 'de46c4873a5d75db5037723c32c48494'),
(1225571265, 1, 'f9bbc56d666e45e852a71377f8674af6');

-- --------------------------------------------------------

--
-- Table structure for table `user_registration`
--

DROP TABLE IF EXISTS `user_registration`;
CREATE TABLE IF NOT EXISTS `user_registration` (
  `regkey` varchar(32) NOT NULL,
  `regdate` date NOT NULL,
  `login` varchar(22) character set utf8 collate utf8_unicode_ci NOT NULL,
  `inviter` int(11) NOT NULL default '1',
  `email` varchar(128) character set utf8 collate utf8_unicode_ci NOT NULL,
  `password` varchar(32) NOT NULL,
  `sold` varchar(16) NOT NULL,
  `firstname` varchar(128) NOT NULL,
  `lastname` varchar(128) NOT NULL,
  `country` int(11) NOT NULL,
  `region` int(11) NOT NULL,
  `city` int(11) NOT NULL,
  `birthday` date NOT NULL,
  `email2` varchar(128) NOT NULL,
  PRIMARY KEY  (`regkey`),
  KEY `regdate` (`regdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Òàáëèöà ïîëüçîâàòåëåé\r\n';

--
-- Dumping data for table `user_registration`
--


-- --------------------------------------------------------

--
-- Table structure for table `user_session`
--

DROP TABLE IF EXISTS `user_session`;
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
-- Dumping data for table `user_session`
--

INSERT INTO `user_session` (`id`, `user_id`, `user_ip`, `user_port`, `cast`, `start`, `time`) VALUES
('782bde7567cbda8fc9b907b8ae67ea09', 8, '192.168.20.2', NULL, '2e8528b5fde0671a88c146997eeb059a', 0, 1227025033);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `acl`
--
ALTER TABLE `acl`
  ADD CONSTRAINT `r_acl_to_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `langs`
--
ALTER TABLE `langs`
  ADD CONSTRAINT `r_langs_to_language` FOREIGN KEY (`lang_id`) REFERENCES `language` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_ban`
--
ALTER TABLE `user_ban`
  ADD CONSTRAINT `r_user_ban_to_user` FOREIGN KEY (`uid`) REFERENCES `user` (`id`) ON DELETE CASCADE;
