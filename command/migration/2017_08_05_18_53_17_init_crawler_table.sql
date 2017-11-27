# up

CREATE TABLE IF NOT EXISTS `crawler_bittrex_abnormal_volume` (
`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
`symbol` VARCHAR(45) NULL,
`volume` VARCHAR(45) NULL,
`volume_at` datetime NULL,
`before_avg_volume` VARCHAR(45) NULL,
`description` VARCHAR(500) NULL,
`rank` VARCHAR(500) NULL,
`at` datetime NULL,
PRIMARY KEY (`id`)
) engine=MyISAM default charset=utf8;

# down

DROP TABLE IF EXISTS `crawler_bittrex_abnormal_volume`;
