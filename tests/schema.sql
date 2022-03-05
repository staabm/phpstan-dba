CREATE DATABASE IF NOT EXISTS `phpstan_dba`;

USE `phpstan_dba`;

CREATE TABLE `ada` (
    `adaid` int(11) NOT NULL,
    `gesperrt` tinyint(1) NOT NULL DEFAULT '0',
    `email` varchar(100) NOT NULL DEFAULT '',
    `freigabe1u1` smallint(1) NOT NULL
) ENGINE=MyISAM;

ALTER TABLE `ada`
    ADD PRIMARY KEY (`adaid`);

ALTER TABLE `ada`
    MODIFY `adaid` int(11) NOT NULL AUTO_INCREMENT;


CREATE TABLE `ak` (
    `akid` int(11) NOT NULL DEFAULT '0',
    `eladaid` int(11) DEFAULT NULL,
    `eadavk` decimal(12,2) NOT NULL
) ENGINE=MyISAM;

ALTER TABLE `ak`
    ADD PRIMARY KEY (`akid`);


CREATE TABLE `typemix` (
   `pid` int NOT NULL,
   `c_char5` char(5) NOT NULL,
   `c_varchar255` varchar(255) NOT NULL,
   `c_varchar25` varchar(25) DEFAULT NULL,
   `c_varbinary255` varbinary(255) NOT NULL,
   `c_varbinary25` varbinary(25) DEFAULT NULL,
   `c_date` date DEFAULT NULL,
   `c_time` time DEFAULT NULL,
   `c_datetime` datetime DEFAULT NULL,
   `c_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
   `c_year` year DEFAULT NULL,
   `c_tiny_text` tinytext,
   `c_medium_text` mediumtext,
   `c_text` text,
   `c_long_text` longtext,
   `c_enum` enum('small','medium','large') NOT NULL,
   `c_set` set('small','medium','large') NOT NULL,
   `c_bit` bit(7) DEFAULT NULL,
   `c_int` int NOT NULL,
   `c_tinyint` tinyint NOT NULL,
   `c_smallint` smallint NOT NULL,
   `c_mediumint` mediumint NOT NULL,
   `c_bigint` bigint NOT NULL,
   `c_double` double NOT NULL,
   `c_real` double NOT NULL,
   `c_boolean` tinyint(1) NOT NULL,
   `c_blob` blob NOT NULL,
   `c_tinyblob` tinyblob NOT NULL,
   `c_mediumblog` mediumblob NOT NULL,
   `c_longblob` longblob NOT NULL,
   `c_unsigned_tinyint` tinyint UNSIGNED NOT NULL,
   `c_unsigned_int` int UNSIGNED NOT NULL,
   `c_unsigned_smallint` smallint UNSIGNED NOT NULL,
   `c_unsigned_mediumint` mediumint UNSIGNED NOT NULL,
   `c_unsigned_bigint` bigint UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `typemix`
    ADD PRIMARY KEY (`pid`);

ALTER TABLE `typemix`
    MODIFY `pid` int NOT NULL AUTO_INCREMENT;

CREATE TABLE `cmsdomain` (
    `id` int(11) NOT NULL,
    `cmsdomainid` int(11) NOT NULL,
    `url` varchar(255) COLLATE latin1_german1_ci NOT NULL,
    `standard` tinyint(1) NOT NULL
) ENGINE=MyISAM;

ALTER TABLE `cmsdomain`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cmsdomainid` (`cmsdomainid`),
  ADD KEY `url` (`url`);

ALTER TABLE `cmsdomain`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
