# up
CREATE TABLE IF NOT EXISTS `crawler_yunbi_vencny_k_1` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `at` VARCHAR(45) NULL,
    `max` VARCHAR(45) NULL,
    `min` VARCHAR(45) NULL,
    `last` VARCHAR(45) NULL,
    `first` VARCHAR(45) NULL,
    `vol` VARCHAR(45) NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `at_UNIQUE` (`at` ASC)
) engine=MyISAM default charset=utf8;

CREATE TABLE IF NOT EXISTS `crawler_yunbi_vencny_k_5` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `at` VARCHAR(45) NULL,
    `max` VARCHAR(45) NULL,
    `min` VARCHAR(45) NULL,
    `last` VARCHAR(45) NULL,
    `first` VARCHAR(45) NULL,
    `vol` VARCHAR(45) NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `at_UNIQUE` (`at` ASC)
) engine=MyISAM default charset=utf8;

CREATE TABLE IF NOT EXISTS `crawler_yunbi_vencny_k_15` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `at` VARCHAR(45) NULL,
    `max` VARCHAR(45) NULL,
    `min` VARCHAR(45) NULL,
    `last` VARCHAR(45) NULL,
    `first` VARCHAR(45) NULL,
    `vol` VARCHAR(45) NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `at_UNIQUE` (`at` ASC)
) engine=MyISAM default charset=utf8;

CREATE TABLE IF NOT EXISTS `crawler_yunbi_vencny_k_30` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `at` VARCHAR(45) NULL,
    `max` VARCHAR(45) NULL,
    `min` VARCHAR(45) NULL,
    `last` VARCHAR(45) NULL,
    `first` VARCHAR(45) NULL,
    `vol` VARCHAR(45) NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `at_UNIQUE` (`at` ASC)
) engine=MyISAM default charset=utf8;

CREATE TABLE IF NOT EXISTS `crawler_yunbi_vencny_k_60` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `at` VARCHAR(45) NULL,
    `max` VARCHAR(45) NULL,
    `min` VARCHAR(45) NULL,
    `last` VARCHAR(45) NULL,
    `first` VARCHAR(45) NULL,
    `vol` VARCHAR(45) NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `at_UNIQUE` (`at` ASC)
) engine=MyISAM default charset=utf8;

CREATE TABLE IF NOT EXISTS `crawler_yunbi_vencny_k_120` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `at` VARCHAR(45) NULL,
    `max` VARCHAR(45) NULL,
    `min` VARCHAR(45) NULL,
    `last` VARCHAR(45) NULL,
    `first` VARCHAR(45) NULL,
    `vol` VARCHAR(45) NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `at_UNIQUE` (`at` ASC)
) engine=MyISAM default charset=utf8;

CREATE TABLE IF NOT EXISTS `crawler_yunbi_vencny_k_240` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `at` VARCHAR(45) NULL,
    `max` VARCHAR(45) NULL,
    `min` VARCHAR(45) NULL,
    `last` VARCHAR(45) NULL,
    `first` VARCHAR(45) NULL,
    `vol` VARCHAR(45) NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `at_UNIQUE` (`at` ASC)
) engine=MyISAM default charset=utf8;

# down
DROP TABLE IF EXISTS `crawler_yunbi_vencny_k_1`;
DROP TABLE IF EXISTS `crawler_yunbi_vencny_k_5`;
DROP TABLE IF EXISTS `crawler_yunbi_vencny_k_15`;
DROP TABLE IF EXISTS `crawler_yunbi_vencny_k_30`;
DROP TABLE IF EXISTS `crawler_yunbi_vencny_k_60`;
DROP TABLE IF EXISTS `crawler_yunbi_vencny_k_120`;
DROP TABLE IF EXISTS `crawler_yunbi_vencny_k_240`;
