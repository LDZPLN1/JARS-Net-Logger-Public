USE `%DB_NAME%`;

DROP TABLE IF EXISTS `auth`;
CREATE TABLE `auth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` tinytext DEFAULT NULL,
  `password` tinytext DEFAULT NULL,
  `admin` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `USERNAME_U` (`username`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `auth` WRITE;
INSERT INTO `auth` VALUES
(NULL,'ADMIN','$2y$12$z2ZwsFrzl5LRJ4YngtIqpup92Yr.pkVkea5TI0/W1NO8UEdU0x.g6',1);
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `net_id` int(10) unsigned NOT NULL,
  `net_control` varchar(6) DEFAULT NULL,
  `sequence` int(10) unsigned DEFAULT NULL,
  `callsign` varchar(6) DEFAULT NULL,
  `announcement` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `mobile` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `portable` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `echolink` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `short_time` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `in_out` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `coupin` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `date` date DEFAULT NULL,
  `dow` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nets`;
CREATE TABLE `nets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `net_name` varchar(64) DEFAULT NULL,
  `band` varchar(5) DEFAULT NULL,
  `mode` varchar(12) DEFAULT NULL,
  `submode` varchar(6) DEFAULT NULL,
  `frequency` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `net_name` (`net_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `visitors`;
CREATE TABLE `visitors` (
  `callsign` varchar(6) NOT NULL,
  `preferred_name` varchar(64) DEFAULT NULL,
  `location` varchar(64) DEFAULT NULL,
  `notes` varchar(64) DEFAULT NULL,
  `lid` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`callsign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `announcement` tinytext DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
