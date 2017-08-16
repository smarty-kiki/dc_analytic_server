# up
CREATE TABLE IF NOT EXISTS `crawler_announcement` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NULL,
    `url` VARCHAR(255) NULL,
    `web` VARCHAR(255) NULL,
    `at` VARCHAR(45) NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `url_UNIQUE` (`url` ASC)
) engine=MyISAM default charset=utf8;

# down
DROP TABLE IF EXISTS `crawler_announcement`;
