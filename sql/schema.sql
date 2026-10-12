-- ContactBook database schema (database: kirbypar_contactbook)
-- Run in Bluehost phpMyAdmin (or mysql CLI) against the ContactBook database.

CREATE TABLE IF NOT EXISTS CB_User_Accounts (
    CB_User_ID              INT(4) NOT NULL AUTO_INCREMENT,
    CB_Username             VARCHAR(128) DEFAULT NULL,
    CB_Password             VARCHAR(255) DEFAULT NULL,  -- password_hash() output (60+ chars), never plain text
    CB_User_Role            TEXT DEFAULT NULL,          -- 'admin' or 'user'
    CB_Password_Reset_ID    VARCHAR(128) DEFAULT NULL,
    CB_Password_Reset_Timer DATETIME DEFAULT NULL,
    PRIMARY KEY (CB_User_ID),
    UNIQUE KEY uq_cb_username (CB_Username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
