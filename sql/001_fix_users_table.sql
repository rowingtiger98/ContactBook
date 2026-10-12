-- Fixes for the CB_User_Accounts table as originally created in phpMyAdmin.
-- Run once against kirbypar_contactbook.

-- password_hash() produces 60-character bcrypt hashes (and PHP recommends room
-- for 255 as algorithms change). At VARCHAR(36) every hash is cut off and no
-- login can ever succeed.
ALTER TABLE CB_User_Accounts MODIFY CB_Password VARCHAR(255) DEFAULT NULL;

-- One row per username: login looks users up by name, and
-- tools/create_user.php relies on this to update an existing user instead of
-- adding a duplicate.
ALTER TABLE CB_User_Accounts ADD UNIQUE KEY uq_cb_username (CB_Username);
