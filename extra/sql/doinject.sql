-- phpMyAdmin SQL Dump
-- version 4.0.10.5
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2014-11-27 10:54:58
-- 服务器版本: 5.5.40-cll
-- PHP 版本: 5.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `ltreme_doinject`
--

-- --------------------------------------------------------

--
-- 表的结构 `doinject`
--

CREATE TABLE IF NOT EXISTS `doinject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT '取记录的键',
  `value` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `extra` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT '附加信息',
  PRIMARY KEY (`id`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='DoInject默认表' AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
