-- Run this ONCE in phpMyAdmin (database: coastal_bookshop) if you already imported
-- the database and do not want to re-import it.
-- It replaces the old decoration choices (Ribbon / Gift wrap) with Option 1-7 and None.

UPDATE Gift SET DecorationType = 'None';

ALTER TABLE Gift
  MODIFY DecorationType ENUM('Option 1','Option 2','Option 3','Option 4','Option 5','Option 6','Option 7','None')
  NOT NULL DEFAULT 'None';
