# CoastalBookshop

A bookshop website built with PHP and MySQL. Customers can browse books by category, and admins can manage the book list.

## Features

- Browse books by category (fiction, kids, etc.)
- User login and logout
- Admin page for managing books
- Book table stored in a MySQL database

## Built with

- PHP
- MySQL (via XAMPP)
- HTML, CSS, JavaScript

## How to run it

1. Install XAMPP and start **Apache** and **MySQL**.
2. Clone the repo into `C:\xampp\htdocs`:
   git clone https://github.com/KaveeshaGB/CoastalBookshop.git
3. Open phpMyAdmin (http://localhost/phpmyadmin) and create a database named `coastal_bookshop`.
4. Import the file `database/coastal_bookshop.sql` into that database.
5. Check `includes/config.php` and make sure the database name, username and password match your computer.
6. Open http://localhost/CoastalBookshop in your browser.

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