# Peer-to-Peer Student Book Exchange Portal

A campus book-lending portal that allows students to list books, request books listed by other students, and manage requests for books they own.

The application runs locally using **PHP on Apache (XAMPP)** and stores its data in **PostgreSQL**. Database access is handled through **PDO with the PostgreSQL PDO driver (`pdo_pgsql`)**.

## Features in the current codebase

- **Active student selector:** Loads students from PostgreSQL. Changing the selection submits a GET request with `student_id`.
- **List a book:** Adds a book for the currently selected student. New books use the database default status `AVAILABLE`.
- **All listed books:** Displays each book's title, owner's name, and status.
- **Request a book:** Shows a Request Book form for an available book owned by another student. The backend checks that the book exists, that the borrower is not its owner, and that the book is available before inserting a request.
- **Incoming requests:** Displays pending requests for books owned by the selected student.
- **Accept a request:** Accepts the selected request, rejects the other pending requests for that book, and changes the book status to `LENT` within one database transaction.
- **Reject a request:** Changes the selected pending request to `REJECTED` after checking that the selected student owns the book.
- **Received Books:** Displays books for which the selected student has an `ACCEPTED` request.

The active student selector is a development convenience, not an authentication system. The application does not implement user accounts or login authentication.

## Technology used

- HTML
- CSS
- PHP
- Apache through XAMPP
- PostgreSQL
- PDO (`pdo_pgsql`)
- pgAdmin 4 for database administration

`assets/js/app.js` exists in the repository but is currently empty. The current page uses standard HTML form submissions and does not implement jQuery or AJAX interactions.

## Project structure

```text
Book_Portal/
├── index.php                 # Main page and database reads
├── actions.php               # POST handlers for book and request actions
├── README.md
├── .gitignore
├── config/
│   └── db.example.php        # Example PDO connection configuration
└── assets/
    ├── css/
    │   └── style.css         # Page and table styling
    └── js/
        └── app.js            # Currently empty
```

The local `config/db.php` file is created during setup and is ignored by Git because it contains database credentials.

---

## Local setup (Windows)

These instructions assume:

- XAMPP is installed at `C:\xampp`.
- PostgreSQL is installed separately and listens on port `5432`.
- The PostgreSQL database is named `book_portal_db`.

If your installation uses a different path, port, database name, or username, use your actual values in the relevant steps.

### 1. Install the required software

Install:

- [XAMPP](https://www.apachefriends.org/) — provides Apache and PHP.
- [PostgreSQL](https://www.postgresql.org/download/windows/) — database server.
- [pgAdmin 4](https://www.pgadmin.org/download/) — graphical tool for managing PostgreSQL databases.
- [Git for Windows](https://git-scm.com/download/win) — to clone the repository.
- [Visual Studio Code](https://code.visualstudio.com/) — optional, for editing the project.

PostgreSQL is separate from XAMPP. The MySQL service bundled with XAMPP is not used by this application.

### 2. Put the project inside XAMPP's `htdocs` directory

Open PowerShell and run:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/ZapRaptor/Book_Portal.git
cd Book_Portal
```

The project directory should be:

```text
C:\xampp\htdocs\Book_Portal\
```

If you have already cloned the repository, open the existing `Book_Portal` folder instead of cloning it again.

### 3. Start Apache and PostgreSQL

1. Open **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Make sure the PostgreSQL Windows service is running. Starting Apache does not start PostgreSQL.
4. Open pgAdmin 4 and connect to your PostgreSQL server.

### 4. Create the PostgreSQL database

In pgAdmin 4:

1. Connect to your PostgreSQL server.
2. Right-click **Databases** and choose **Create → Database**.
3. Set the database name to:

   ```text
   book_portal_db
   ```

4. Select the new database in the Object Explorer.
5. Open **Tools → Query Tool**.

### 5. Create the tables

Run the following SQL in the Query Tool connected to `book_portal_db`. This script is for a new, empty database; do not run it again over tables that already exist.

```sql
-- Students who can list and borrow books.
CREATE TABLE students (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- Books listed by students.
CREATE TABLE books (
    id SERIAL PRIMARY KEY,
    owner_id INT NOT NULL
        REFERENCES students(id) ON DELETE CASCADE,
    title VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'AVAILABLE',
    CONSTRAINT books_status_check
        CHECK (status IN ('AVAILABLE', 'LENT'))
);

-- Borrow requests made by students.
CREATE TABLE requests (
    id SERIAL PRIMARY KEY,
    book_id INT NOT NULL
        REFERENCES books(id) ON DELETE CASCADE,
    borrower_id INT NOT NULL
        REFERENCES students(id) ON DELETE CASCADE,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    CONSTRAINT requests_status_check
        CHECK (status IN ('PENDING', 'ACCEPTED', 'REJECTED')),
    CONSTRAINT unique_borrower_book
        UNIQUE (borrower_id, book_id)
);
```

The tables are related as follows:

- `books.owner_id` references `students.id` and identifies the student who listed a book.
- `requests.book_id` references `books.id` and identifies the requested book.
- `requests.borrower_id` references `students.id` and identifies the student who made the request.
- The foreign keys use `ON DELETE CASCADE`, so deleting a referenced student or book also deletes dependent records.
- A book status is either `AVAILABLE` or `LENT`.
- A request status is `PENDING`, `ACCEPTED`, or `REJECTED`.
- `UNIQUE (borrower_id, book_id)` prevents duplicate request rows for the same student-book pair. Under this schema, a student cannot create another request row for the same book after an earlier request has been rejected.

### 6. Add student records

The student selector needs rows in the `students` table. For a new database, add sample students using pgAdmin's Query Tool:

```sql
INSERT INTO students (name)
VALUES
    ('Ayush'),
    ('Anshika'),
    ('Manikanta');
```

Run this insert once. If student records already exist, do not run it again unless you intend to add those names again.

Verify the records:

```sql
SELECT * FROM students ORDER BY id;
```

Books can then be added through the portal's **List a book for lending** form.

### 7. Enable PHP's PostgreSQL PDO driver

The PHP installation used by Apache must have `pdo_pgsql` enabled.

1. Open XAMPP's PHP configuration file:

   ```text
   C:\xampp\php\php.ini
   ```

2. Find the following line:

   ```ini
   ;extension=pdo_pgsql
   ```

3. If it has a leading semicolon, remove it so it reads:

   ```ini
   extension=pdo_pgsql
   ```

4. Save `php.ini`.
5. In XAMPP Control Panel, stop Apache and start it again so PHP reloads the configuration.

If PHP reports `could not find driver`, check that the `pdo_pgsql` extension is available in the XAMPP PHP installation and that Apache is using the PHP configuration file you edited.

### 8. Create the local database connection file

The repository contains `config/db.example.php` with placeholder credentials. The actual `config/db.php` file is ignored by Git and must be created on each local installation.

From PowerShell in the project root, copy the example file:

```powershell
Copy-Item config\db.example.php config\db.php
```

Open `config/db.php` and replace `YOUR_USERNAME` and `YOUR_PASSWORD` with the credentials for your PostgreSQL server. The example connection uses:

- Host: `localhost`
- Port: `5432`
- Database: `book_portal_db`

The connection file should have this structure:

```php
<?php

$pdo = new PDO(
    "pgsql:host=localhost;port=5432;dbname=book_portal_db",
    "YOUR_USERNAME",
    "YOUR_PASSWORD",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
```

Keep your real `config/db.php` out of Git. The repository's `.gitignore` excludes `config/db.php` and `.env`. Do not put real database passwords in `db.example.php` or commit them to the repository.

### 9. Run the portal

1. Confirm Apache is running in XAMPP.
2. Confirm PostgreSQL is running.
3. Confirm `config/db.php` exists and contains the correct local credentials.
4. Open the following URL in your browser:

   ```text
   http://localhost/Book_Portal/
   ```

Apache serves `index.php` from the project directory. You do not need to start PHP's built-in development server when using XAMPP's Apache server.

---

## Using the portal

### Select an active student

Use the **Logged in as** dropdown to select a student. Changing the selection submits a GET request and adds the selected student's ID to the URL as `student_id`.

The selector is used to simulate which student is using the portal. It is not a login or authentication mechanism.

### List a book

1. Select the student who owns the book.
2. Enter the book title in **List a book for lending**.
3. Click **Add Book**.

The form submits to `actions.php` using POST. The book is inserted with the selected student's ID as `owner_id`; PostgreSQL supplies the book ID and the default `AVAILABLE` status.

### Request a book

1. Select the student who wants to borrow a book.
2. In **All Listed Books**, find a book owned by another student with status `AVAILABLE`.
3. Click **Request Book**.

The request is submitted to `actions.php`. New requests use the default status `PENDING`.

### Review incoming requests

Select the student who owns the book. The **Incoming Requests for Your Books** section displays that student's pending requests. The owner can select **Accept** or **Reject** for a request.

- **Reject:** Changes the selected pending request to `REJECTED`.
- **Accept:** Changes the selected request to `ACCEPTED`, changes other pending requests for the same book to `REJECTED`, and changes the book status to `LENT`. These updates are performed within a PostgreSQL transaction. The handler locks the book row with `FOR UPDATE` while processing the acceptance.

### View received books

The **Received Books** section displays books for which the selected student has an `ACCEPTED` request, along with the owner's name and request status.

---

## Verify database records

Run these queries in pgAdmin's Query Tool while connected to `book_portal_db`.

List books and their owners:

```sql
SELECT
    b.id,
    b.title,
    s.name AS owner_name,
    b.status
FROM books AS b
JOIN students AS s
    ON b.owner_id = s.id
ORDER BY b.id DESC;
```

List borrow requests:

```sql
SELECT
    r.id,
    b.title,
    borrower.name AS borrower_name,
    r.status
FROM requests AS r
JOIN books AS b
    ON r.book_id = b.id
JOIN students AS borrower
    ON r.borrower_id = borrower.id
ORDER BY r.id DESC;
```

View accepted requests and their book statuses:

```sql
SELECT
    r.id AS request_id,
    b.title,
    owner.name AS owner_name,
    borrower.name AS borrower_name,
    r.status AS request_status,
    b.status AS book_status
FROM requests AS r
JOIN books AS b
    ON r.book_id = b.id
JOIN students AS owner
    ON b.owner_id = owner.id
JOIN students AS borrower
    ON r.borrower_id = borrower.id
WHERE r.status = 'ACCEPTED'
ORDER BY r.id DESC;
```

## Current scope

The current codebase includes the student selector, book submission and listing, request submission, incoming-request management (accept/reject), and the Received Books view.

The current codebase does **not** include:

- User registration, login, or authenticated user sessions.
- A book-return operation or a return-history workflow.
- jQuery or AJAX interactions. Forms currently use standard browser GET/POST submissions.
- A separate lending-ledger or loan-history table.

The `assets/js/app.js` file is currently empty and is not loaded by `index.php`.

## Troubleshooting

| Problem | Checks |
|---|---|
| `403 Forbidden` | Confirm the project is inside `C:\xampp\htdocs\Book_Portal` and open the project URL shown above. |
| `404 Not Found` | Check the folder name and confirm `index.php` is in the `Book_Portal` root. |
| `could not find driver` | Enable `extension=pdo_pgsql` in `C:\xampp\php\php.ini`, then restart Apache. |
| PostgreSQL connection refused | Confirm the PostgreSQL service is running and the host and port in `config/db.php` are correct. |
| Database does not exist | Confirm pgAdmin contains `book_portal_db` and that the name matches `dbname` in `config/db.php`. |
| Relation/table does not exist | Run the table-creation SQL against `book_portal_db`. |
| Book status check-constraint error | Use only the uppercase values `AVAILABLE` and `LENT`. |
| Request status check-constraint error | Use only `PENDING`, `ACCEPTED`, or `REJECTED`. |
| CSS changes do not appear | Save `assets/css/style.css` and hard-refresh the browser with `Ctrl + Shift + R`. |

## Git workflow

Run Git commands from the project root:

```powershell
cd C:\xampp\htdocs\Book_Portal
git status
git add .
git commit -m "docs: update project documentation"
git push
```

Before committing, check `git status` and confirm that `config/db.php` and any other files containing credentials are not staged.
