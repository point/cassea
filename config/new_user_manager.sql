SET FOREIGN_KEY_CHECKS=0;

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

SET AUTOCOMMIT=0;
START TRANSACTION;

--
-- База данных: `new_um`
--

-- --------------------------------------------------------

--
-- Структура таблицы `acl`
--

CREATE TABLE IF NOT EXISTS `acl` (
  `user_id` int(11) NOT NULL,
  `groups` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `acl`
--


-- --------------------------------------------------------

--
-- Структура таблицы `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `state` enum('active','banned','not_confirmed') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  `last_login` datetime NOT NULL,
  `date_joined` datetime NOT NULL,
  `single_access_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

--
-- Дамп данных таблицы `user`
--

INSERT INTO `user` (`id`, `login`, `email`, `password`, `salt`, `state`, `last_login`, `date_joined`, `single_access_token`) VALUES
(-1, '', '', '', '', 'active', '0000-00-00 00:00:00', '0000-00-00 00:00:00', ''),
(9, 'Vasya2', 'new_vasya@qwe.com', 'b5e30a0fcf0ea5ef98529c88fbc41a6a', 'saltsaltsalt', 'active', '2010-12-20 23:19:54', '1970-01-01 03:32:50', 'token'),
(10, 'Vasya3', 'new_vasya@qwe.com', 'b5e30a0fcf0ea5ef98529c88fbc41a6a', 'saltsaltsalt', 'active', '1970-01-01 03:33:30', '1970-01-01 03:32:50', 'token');

-- --------------------------------------------------------

--
-- Структура таблицы `user_one_time_token`
--

CREATE TABLE IF NOT EXISTS `user_one_time_token` (
  `token` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`token`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `user_one_time_token`
--

INSERT INTO `user_one_time_token` (`token`, `user_id`, `time`) VALUES
('dd3b8896bf78670e5b43078462b3a81e', 9, 1290622613);

-- --------------------------------------------------------

--
-- Структура таблицы `user_session`
--

CREATE TABLE IF NOT EXISTS `user_session` (
  `id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cast` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `time` int(11) NOT NULL DEFAULT '0',
  `remember_me` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `user_session`
--


--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `acl`
--
ALTER TABLE `acl`
  ADD CONSTRAINT `acl_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_one_time_token`
--
ALTER TABLE `user_one_time_token`
  ADD CONSTRAINT `user_one_time_token_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS=1;

COMMIT;
