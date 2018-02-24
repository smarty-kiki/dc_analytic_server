# up

CREATE TABLE IF NOT EXISTS `reminder` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `user_id` VARCHAR(255) NULL,
    `description` VARCHAR(500) NULL,
    `at` datetime NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id_at` (`user_id`, `at`)
) engine=MyISAM default charset=utf8;

# down

DROP TABLE IF EXISTS `reminder`;
