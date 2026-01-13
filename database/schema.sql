-- Sistema Administrativo MVC - Schema do Banco de Dados
-- Versão: 1.1.0
-- Compatível com MySQL 5.7+ e MariaDB 10.2+
-- Suporte a prefixos de tabelas via {prefix}

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Tabela de Gêneros
CREATE TABLE IF NOT EXISTS `{prefix}genders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translate` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dh` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dh_update` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_genders_name` (`name`),
  KEY `idx_genders_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela de Níveis de Acesso
CREATE TABLE IF NOT EXISTS `{prefix}levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translate` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dh` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dh_update` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_levels_name` (`name`),
  KEY `idx_levels_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela de Status
CREATE TABLE IF NOT EXISTS `{prefix}status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translate` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'secondary',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dh` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dh_update` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_name` (`name`),
  KEY `idx_status_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela de Períodos Escolares
CREATE TABLE IF NOT EXISTS `{prefix}school_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translate` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_id` int(11) DEFAULT 1,
  `dh` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dh_update` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_school_periods_name` (`name`),
  KEY `idx_school_periods_status` (`status_id`),
  KEY `idx_school_periods_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela de Matérias Escolares
CREATE TABLE IF NOT EXISTS `{prefix}school_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translate` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_id` int(11) DEFAULT 1,
  `dh` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dh_update` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_school_subjects_name` (`name`),
  KEY `idx_school_subjects_status` (`status_id`),
  KEY `idx_school_subjects_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela de usuários (estrutura completa)
CREATE TABLE IF NOT EXISTS `{prefix}users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alias` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender_id` int(11) DEFAULT NULL,
  `phone_home` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_mobile` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_message` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `google_access_token` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_refresh_token` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_token_expires` timestamp NULL DEFAULT NULL,
  `google_calendar_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_signature` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Assinatura HTML para mensagens',
  `signature_include_logo` tinyint(1) DEFAULT 0 COMMENT 'Incluir logo na assinatura',
  `permissions_updated_at` timestamp NULL DEFAULT NULL COMMENT 'Última atualização das permissões individuais',
  `unique_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_access` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `level_id` int(11) NOT NULL DEFAULT 11,
  `status_id` int(11) NOT NULL DEFAULT 1,
  `register_id` int(11) DEFAULT NULL,
  `dh` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dh_update` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_unique_code_unique` (`unique_code`),
  UNIQUE KEY `users_cpf_unique` (`cpf`),
  KEY `idx_users_level` (`level_id`),
  KEY `idx_users_gender` (`gender_id`),
  KEY `idx_users_status` (`status_id`),
  KEY `idx_users_register` (`register_id`),
  KEY `idx_users_created_at` (`dh`),
  KEY `idx_users_deleted_at` (`deleted_at`),
  KEY `idx_users_cpf` (`cpf`),
  KEY `idx_users_last_access` (`last_access`),
  KEY `idx_users_password_reset` (`password_reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela de Turmas Escolares
CREATE TABLE IF NOT EXISTS `{prefix}school_teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serie_id` int(11) DEFAULT NULL,
  `period_id` int(11) DEFAULT NULL,
  `education_id` int(11) DEFAULT NULL,
  `status_id` int(11) DEFAULT 1,
  `public_link_token` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Token único para link público',
  `public_link_enabled` tinyint(1) DEFAULT 0,
  `public_link_expires_at` date DEFAULT NULL,
  `dh` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dh_update` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_teams_public_link_token_unique` (`public_link_token`),
  KEY `idx_school_teams_serie` (`serie_id`),
  KEY `idx_school_teams_period` (`period_id`),
  KEY `idx_school_teams_education` (`education_id`),
  KEY `idx_school_teams_status` (`status_id`),
  KEY `idx_school_teams_deleted` (`deleted_at`),
  KEY `idx_school_teams_public_link` (`public_link_enabled`, `public_link_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela de Horários Escolares
CREATE TABLE IF NOT EXISTS `{prefix}school_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL COMMENT '1=Seg, 2=Ter, 3=Qua, 4=Qui, 5=Sex, 6=Sab, 7=Dom',
  `class_number` tinyint(4) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `dh` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dh_update` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_school_schedules_team` (`team_id`),
  KEY `idx_school_schedules_teacher` (`teacher_id`),
  KEY `idx_school_schedules_subject` (`subject_id`),
  KEY `idx_school_schedules_day_class` (`day_of_week`, `class_number`),
  KEY `idx_school_schedules_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela de Logs do Sistema (Avançada)
CREATE TABLE IF NOT EXISTS `{prefix}system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `context` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `memory_usage` bigint DEFAULT NULL,
  `execution_time` decimal(10,6) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_system_logs_level` (`level`),
  KEY `idx_system_logs_channel` (`channel`),
  KEY `idx_system_logs_created` (`created_at`),
  KEY `idx_system_logs_user` (`user_id`),
  KEY `idx_system_logs_ip` (`ip_address`),
  KEY `idx_system_logs_level_created` (`level`, `created_at`),
  KEY `idx_system_logs_channel_created` (`channel`, `created_at`),
  CONSTRAINT `fk_system_logs_user` FOREIGN KEY (`user_id`) REFERENCES `{prefix}users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela de Auditoria
CREATE TABLE IF NOT EXISTS `{prefix}audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_data` json DEFAULT NULL,
  `new_data` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_user` (`user_id`),
  KEY `idx_audit_logs_action` (`action`),
  KEY `idx_audit_logs_table` (`table_name`),
  KEY `idx_audit_logs_record` (`record_id`),
  KEY `idx_audit_logs_created` (`created_at`),
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `{prefix}users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- FOREIGN KEYS
-- --------------------------------------------------------

-- Foreign keys para users
ALTER TABLE `{prefix}users` 
  ADD CONSTRAINT `fk_users_level` FOREIGN KEY (`level_id`) REFERENCES `{prefix}levels` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_gender` FOREIGN KEY (`gender_id`) REFERENCES `{prefix}genders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_status` FOREIGN KEY (`status_id`) REFERENCES `{prefix}status` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_register` FOREIGN KEY (`register_id`) REFERENCES `{prefix}users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Foreign keys para school_periods
ALTER TABLE `{prefix}school_periods` 
  ADD CONSTRAINT `fk_school_periods_status` FOREIGN KEY (`status_id`) REFERENCES `{prefix}status` (`id`) ON UPDATE CASCADE;

-- Foreign keys para school_subjects
ALTER TABLE `{prefix}school_subjects` 
  ADD CONSTRAINT `fk_school_subjects_status` FOREIGN KEY (`status_id`) REFERENCES `{prefix}status` (`id`) ON UPDATE CASCADE;

-- Foreign keys para school_teams
ALTER TABLE `{prefix}school_teams` 
  ADD CONSTRAINT `fk_school_teams_period` FOREIGN KEY (`period_id`) REFERENCES `{prefix}school_periods` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_school_teams_status` FOREIGN KEY (`status_id`) REFERENCES `{prefix}status` (`id`) ON UPDATE CASCADE;

-- Foreign keys para school_schedules
ALTER TABLE `{prefix}school_schedules` 
  ADD CONSTRAINT `fk_school_schedules_team` FOREIGN KEY (`team_id`) REFERENCES `{prefix}school_teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_school_schedules_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `{prefix}users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_school_schedules_subject` FOREIGN KEY (`subject_id`) REFERENCES `{prefix}school_subjects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;