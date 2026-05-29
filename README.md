# Booklet Platform - Database Programming 2 Project

## Project Overview

**Booklet Platform** is a database-driven book review web application developed for the **Database Programming 2** course. The system allows visitors to browse books and reviews, creators to add and manage books, and administrators to manage users, books, reviews, comments, and analytical reports.

---

## Application URL

http://20.74.143.233/~u202301820/Booklet/Home.php

---

## Technologies Used

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Chart.js "https://cdn.jsdelivr.net/npm/chart.js"
- PHPMailer (Installed from: "https://sourceforge.net/projects/phpmailer/")

---

## User Roles
The system supports three main user roles:

### 1- Visitor
Visitors can browse the platform without logging in.
- View the Home page
- Browse all books
- Search and filter books
- View book details
- View reviews and comments
- Access About Us, Login, Register, and Forgot Password pages
- Visitors cannot add reviews or comments unless they log in.

### 2- Creator
Creators can manage their own book content after logging in.
- Add new books
- View and manage uploaded books
- Edit their own books
- Add one review per book
- Edit their own reviews
- Add comments on reviews
- Search, filter, and sort their books

### 3- Admin
Admins can manage the full platform.
- View the admin dashboard
- Manage registered users
- Search for users
- Delete creator accounts
- View all books
- Edit or delete any book
- Delete inappropriate reviews and comments
- Generate book and creator reports
- Export reports as PDF

---

## Main Features

### 1. User Roles and Authentication
The system supports Visitor, Creator, and Admin roles with login, registration, session management, password hashing, validation, and role-based access.

### 2. Home Page
The Home page displays the newest books first and allows users to quickly search for books and open book details.

### 3. Search and Filtering
Users can search and filter books by title, date range, creator/author, rating, and popularity.

### 4. Comments and Rating System
Logged-in users can add reviews, star ratings, and comments. Visitors can view reviews and comments only.

### 5. Creator Panel
Creators can add books, upload cover images, edit their own books, publish content, and manage their reviews and comments.

### 6. Admin Panel
Admins can manage users, books, reviews, and comments, including removing inappropriate content.

### 7. Reporting System
Admins can generate reports such as most popular books and content created by specific users. Reports include charts and can be printed or saved as PDF.

---

## Advanced Features

### 1. Prepared Statements
The system uses **prepared statements** to protect the database from SQL Injection attacks. Instead of inserting user input directly into SQL queries, the system uses placeholders and safely binds values using `mysqli_stmt_bind_param()`.

### 2. Forgot Password Feature
The Forgot Password feature allows users to reset their password securely using a reset token and email link.

### 3. Advanced UI
The platform includes a modern and user-friendly interface with:
- Structured navigation bars
- Book cards
- Responsive pages
- Pop-up forms
- Confirmation alerts
- Validation alerts
- Dynamic star rating display

---

## Testing Accounts

| Role | Email | Password |
|---|---|---|
| Admin | hoor.yousif05@gmail.com | H123456@ |
| Creator | sara@test.com | S123456@ |
| Creator | dalal@test.com | D123456@ |
| Creator | zahraa@test.com | Z123456@ |
| Creator | walaa@test.com | W123456@ |

---

## Course Information
**Course:** Database Programming 2  
**Project Topic:** Book Review Platform  
**Project Name:** Booklet Platform   
**Academic Year:** 2025/2026  

---

## License

This project was created for educational purposes only.
