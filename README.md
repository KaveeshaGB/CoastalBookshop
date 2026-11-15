# CoastalBookshop

A bookshop website built with PHP and MySQL. Customers can browse and order books, and members can join book club sessions and buy ebooks. Admins manage the books and ebooks.

## Features

- Browse books by category (Fiction, Novel, Short Story, Kids Story)
- Register, log in and log out
- Like books, add books to a cart, and place orders (parcel or gift, with delivery and packaging options)
- Special offers and stock levels on books
- Profile page with details, joined sessions and order history
- Book Club: members can start and join sessions and chat in them
- Ebooks: members can buy ebooks and read them on their bookshelf
- Admin pages for managing books and ebooks

## Built with

- PHP
- MySQL (via XAMPP)
- HTML, CSS, JavaScript

## How to run it

1. Install XAMPP and start **Apache** and **MySQL**.
2. Clone the repo into `C:\xampp\htdocs`:
   `git clone https://github.com/KaveeshaGB/CoastalBookshop.git`
3. Open phpMyAdmin (http://localhost/phpmyadmin) and create a database named `coastal_bookshop`.
4. Import these three files from the `database` folder into `coastal_bookshop`, in this order:
   1. `coastal_bookshop.sql` (tables)
   2. `session_setup.sql` (book club sessions and chat)
   3. `setup_additions.sql` (books)
5. Check `includes/config.php` and make sure the database name, username and password match your computer.
6. Open http://localhost/CoastalBookshop in your browser.
7. To use the admin pages, register an account on the site, then run this in phpMyAdmin (SQL tab), using the email you registered with:
   `UPDATE Customer SET IsAdmin = 1 WHERE Email = 'your@email';`
   Log out and log in again, and **Manage Books** will appear in the profile menu.

## Ebooks

Ebook files are not stored in the repo. Add them from **Manage Books**, then **+ Add New Ebook**. Uploaded files are saved in `ebooks/copy`.

## Team

- KaveeshaGB
- MalmiUmayangana
- RameshaUJayasekara
- NirmaniPiyumika
- Chayakaggodaarachchi

## Git workflow

- Never push straight to `main`.
- Create a branch for each task, then open a pull request.
- Run `git pull` on `main` before starting new work.