CREATE DATABASE IF NOT EXISTS `phpstan-dba`;

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
