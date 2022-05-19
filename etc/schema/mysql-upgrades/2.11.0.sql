ALTER TABLE `icingaweb_group` CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE `icingaweb_group_membership` CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE `icingaweb_user` CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE `icingaweb_user_preference` CONVERT TO CHARACTER SET utf8mb4;

ALTER TABLE `icingaweb_group`
    MODIFY COLUMN `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `icingaweb_group_membership`
    MODIFY COLUMN `username` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `icingaweb_user`
    MODIFY COLUMN `name` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL;

ALTER TABLE `icingaweb_user_preference`
    MODIFY COLUMN `username` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
    MODIFY COLUMN `section`  varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
    MODIFY COLUMN `name`     varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL;

CREATE TABLE `icingaweb_dashboard_owner` (
    `id`        int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`  varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_dashboard_user_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `icingaweb_dashboard_home` (
    `id`        int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`   int(10) UNSIGNED NOT NULL,
    `name`      varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`     varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `priority`  tinyint NOT NULL,
    `type`      enum('public', 'private', 'shared') DEFAULT 'private',
    `disabled`  tinyint DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_dashboard_home_name` (`user_id`, `name`),
    KEY `fk_dashboard_home_dashboard_user` (`user_id`),
    CONSTRAINT `fk_dashboard_home_dashboard_user` FOREIGN KEY (`user_id`)
      REFERENCES `icingaweb_dashboard_owner` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `icingaweb_dashboard` (
    `id`        binary(20) NOT NULL,
    `home_id`   int(10) UNSIGNED NOT NULL,
    `name`      varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`     varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `priority`  tinyint NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_dashboard_dashboard_home` (`home_id`),
    CONSTRAINT `fk_dashboard_dashboard_home` FOREIGN KEY (`home_id`)
      REFERENCES `icingaweb_dashboard_home` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `icingaweb_dashlet` (
    `id`            binary(20) NOT NULL,
    `dashboard_id`  binary(20) NOT NULL,
    `name`          varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`         varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `url`           varchar(2048) NOT NULL COLLATE utf8mb4_bin,
    `priority`      tinyint NOT NULL,
    `disabled`      enum ('n', 'y') DEFAULT 'n',
    PRIMARY KEY (`id`),
    KEY `fk_dashlet_dashboard` (`dashboard_id`),
    CONSTRAINT `fk_dashlet_dashboard` FOREIGN KEY (`dashboard_id`)
      REFERENCES `icingaweb_dashboard` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `icingaweb_module_dashlet` (
    `id`            binary(20) NOT NULL,
    `name`          varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`         varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `module`        varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `pane`          varchar(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `url`           varchar(2048) NOT NULL COLLATE utf8mb4_bin,
    `description`   text DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `priority`      tinyint DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_module_dashlet_name` (`name`),
    INDEX `idx_module_dashlet_pane` (`pane`),
    INDEX `idx_module_dashlet_module` (`module`),
    INDEX `idx_module_dashlet_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `icingaweb_system_dashlet` (
    `id`                int(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `dashlet_id`        binary(20) NOT NULL,
    `module_dashlet_id` binary(20) DEFAULT NULL,
    CONSTRAINT `fk_dashlet_system_dashlet` FOREIGN KEY (`dashlet_id`)
      REFERENCES `icingaweb_dashlet` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dashlet_system_module_dashlet` FOREIGN KEY (`module_dashlet_id`)
      REFERENCES `icingaweb_module_dashlet` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
