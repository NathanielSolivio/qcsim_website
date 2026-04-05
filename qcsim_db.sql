CREATE DATABASE IF NOT EXISTS qcsim_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qcsim_db;

-- ── USERS ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    last_name       VARCHAR(100)  NOT NULL,
    first_name      VARCHAR(100)  NOT NULL,
    email           VARCHAR(191)  NOT NULL UNIQUE,
    phone_number    VARCHAR(30)   DEFAULT NULL,
    school          VARCHAR(200)  DEFAULT NULL,
    password        VARCHAR(255)  NOT NULL,
    role            ENUM('student','instructor','admin') NOT NULL DEFAULT 'student',
    is_verified     TINYINT(1)    NOT NULL DEFAULT 0,
    verify_token    VARCHAR(128)  DEFAULT NULL,
    token_expires   DATETIME      DEFAULT NULL,
    profile_pic     VARCHAR(255)  DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed: one default admin (password: Admin@1234)
INSERT IGNORE INTO users (last_name, first_name, email, password, role, is_verified)
VALUES ('Admin', 'QCSim', 'admin@qcsim.edu', '$2y$10$WIjQ9H4VWmfHAMd.ZiG9e.FZbDMJqz.8nX0G/pJsq3GYQrVbH/cLG', 'admin', 1);

-- ── LEARNING MATERIALS ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS learningMaterialsTable (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255)  NOT NULL,
    description     TEXT          DEFAULT NULL,
    file_path       VARCHAR(500)  NOT NULL,
    file_type       VARCHAR(50)   DEFAULT NULL,
    file_size       BIGINT        DEFAULT NULL,
    category        VARCHAR(100)  DEFAULT NULL,
    uploaded_by     INT           NOT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
