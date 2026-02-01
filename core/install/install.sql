-- Ascio Core shared settings table
-- Used by all Ascio modules: Domains, SSL, Monitoring, Defensive, TMCH

CREATE TABLE IF NOT EXISTS `mod_ascio_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `value` TEXT NULL,
    `role` ENUM('User', 'Admin', '') NOT NULL DEFAULT 'User',
    `description` VARCHAR(500) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key_name` (`name`),
    KEY `idx_name` (`name`),
    KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings (will not overwrite existing values)
INSERT IGNORE INTO `mod_ascio_settings` (`name`, `value`, `role`, `description`) VALUES
('Account', '', 'User', 'Live API account username'),
('Password', '', 'User', 'Live API account password'),
('AccountTesting', '', 'User', 'Test/Demo API account username'),
('PasswordTesting', '', 'User', 'Test/Demo API account password'),
('Environment', 'testing', 'User', 'Environment: testing or live'),
('DbVersion', '1.0', 'Admin', 'Database schema version');
