-- Run this AFTER coastal_bookshop.sql
USE coastal_bookshop;

-- Needed for registration / log-in, and for showing book pictures
ALTER TABLE Customer ADD COLUMN PasswordHash VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE Books    ADD COLUMN ImageFile VARCHAR(100);

-- The 9 books from the site (Category matches the three book type pages)
INSERT INTO Books (Title, Author, Price, Category, ImageFile) VALUES
('Children of Time',         'Adrian Tchaikovsky', 2800.00, 'Fiction',     'fiction1.jpg'),
('Little Red Riding Hood',   'Brothers Grimm',      800.00, 'Fiction',     'fiction2.jpg'),
('The Night Circus',         'Erin Morgenstern',   4000.00, 'Fiction',     'fiction3.jpg'),
('Gone Girl',                'Gillian Flynn',      3000.00, 'Novel',       'novel1.jpg'),
('The Silent Patient',       'Alex Michaelides',   4000.00, 'Novel',       'novel2.jpg'),
('Dune',                     'Frank Herbert',      4500.00, 'Novel',       'novel3.jpg'),
('The Pied Piper of Hamelin','Robert Browning',     800.00, 'Short Story', 'shortstory1.jpg'),
('The Gingerbread Man',      'Traditional',         500.00, 'Short Story', 'shortstory2.jpg'),
('The Runaway Kite',         'Unknown',             300.00, 'Short Story', 'shortstory3.jpg');
