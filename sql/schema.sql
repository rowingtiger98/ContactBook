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

-- One row per contact book.
CREATE TABLE IF NOT EXISTS CB_Created_Books (
    CB_Book_ID            INT(4) NOT NULL AUTO_INCREMENT,
    CB_Book_UUID          VARCHAR(64) DEFAULT NULL,   -- e.g. 3f2504e0-4f89-11d3-9a0c-0305e82c3301
    CB_Book_Name_About    VARCHAR(64) DEFAULT NULL,   -- name of the person the book is for
    CB_Base_Directory     VARCHAR(64) DEFAULT NULL,   -- contactbook/books/<uuid>, relative to public_html
    CB_Book_Cover_Picture VARCHAR(128) DEFAULT NULL,  -- cover.jpg / cover.png in the base directory, NULL if none
    CB_Book_Description   MEDIUMTEXT DEFAULT NULL,    -- optional, up to 1000 characters
    PRIMARY KEY (CB_Book_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per entry someone adds to a book (add_entry.php).
CREATE TABLE IF NOT EXISTS CB_Book_Entry (
    ENTRY_ID               INT(7) NOT NULL AUTO_INCREMENT,
    ENTRY_UUID             VARCHAR(64) DEFAULT NULL,   -- the entry's own UUID
    ENTRY_Book_Parent_UUID VARCHAR(64) DEFAULT NULL,   -- CB_Created_Books.CB_Book_UUID of the book
    ENTRY_Name             VARCHAR(64) DEFAULT NULL,
    ENTRY_Emails           VARCHAR(512) DEFAULT NULL,  -- up to 5 addresses, separated by ", "
    ENTRY_Street_Address   VARCHAR(128) DEFAULT NULL,
    ENTRY_City             VARCHAR(64) DEFAULT NULL,
    ENTRY_State            VARCHAR(64) DEFAULT NULL,
    ENTRY_Zip              VARCHAR(64) DEFAULT NULL,
    ENTRY_Phone            VARCHAR(32) DEFAULT NULL,
    ENTRY_Notes            MEDIUMTEXT DEFAULT NULL,
    PRIMARY KEY (ENTRY_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
