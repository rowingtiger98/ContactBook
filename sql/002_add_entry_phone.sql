-- Adds the phone number column used by add_entry.php.
-- Run once against kirbypar_contactbook.

ALTER TABLE CB_Book_Entry
    ADD COLUMN ENTRY_Phone VARCHAR(32) DEFAULT NULL AFTER ENTRY_Zip;
