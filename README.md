# Peer-to-Peer Student Book Exchange Portal

A small campus book-lending portal built with **HTML, CSS, JavaScript/jQuery, PHP, and PostgreSQL**. It is designed to run locally through **Apache in XAMPP**. The database is managed with PostgreSQL and pgAdmin 4.

> **Project status:** The active-student selector, book-submission flow, and all-listed-books table are implemented. The Request Book and incoming-request accept/reject workflows are still being built. The schema below includes the `requests` table for those upcoming features.

## 1. Requirements

Install these tools on Windows:

- **Git for Windows** — to clone the repository: <https://git-scm.com/download/win>
- **XAMPP** — provides Apache and PHP: <https://www.apachefriends.org/>
- **PostgreSQL** — the database server: <https://www.postgresql.org/download/windows/>
- **pgAdmin 4** — usually installed with the PostgreSQL Windows installer; used to create the database and run SQL queries.
- **Visual Studio Code** (recommended) — to edit the project: <https://code.visualstudio.com/>

This setup assumes XAMPP is installed at `C:\xampp` and PostgreSQL listens on port `5432`. Use your actual paths, port, username, and password if they differ.

## 2. Download the project into XAMPP's `htdocs`

### Option A: Clone with Git (recommended)

Open PowerShell and run:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/ZapRaptor/Book_Portal.git
cd Book_Portal
```

The project folder should be:

```text
C:\xampp\htdocs\Book_Portal\
├── index.php
├── actions.php
├── config\
│   └── db.example.php
└── assets\
    ├── css\
    │   └── style.css
    └── js\
        └── app.js
```

If you already have the project folder, **do not clone it over the existing folder**. Open that folder instead. Later, to download teammates' or GitHub changes, run `git pull` from the project directory.

### Option B: Download as a ZIP

1. Open the repository on GitHub: <https://github.com/ZapRaptor/Book_Portal>.
2. Choose **Code → Download ZIP**.
3. Extract the contents so the `index.php` file is directly inside `C:\xampp\htdocs\Book_Portal\`.
4. Avoid accidentally creating a nested path such as `C:\xampp\htdocs\Book_Portal\Book_Portal\index.php`.

## 3. Start Apache in XAMPP

1. Open **XAMPP Control Panel**.
2. Click **Start** beside **Apache**.
3. Confirm Apache starts without an error.

You do **not** need to run `php -S ...` when using XAMPP's Apache server. Keep in mind that XAMPP's MySQL service is not needed for this project; the database is PostgreSQL.

## 4. Start PostgreSQL and create the database

1. Install PostgreSQL using the official Windows installer if it is not installed already.
2. Ensure the PostgreSQL Windows service is running. PostgreSQL is separate from XAMPP; starting Apache does not start PostgreSQL.
3. Open **pgAdmin 4** and connect to your PostgreSQL server (often named `PostgreSQL 18`).
4. Right-click **Databases → Create → Database...**.
5. Set the database name to:

   ```text
   book_portal_db
   ```

6. Open the new `book_portal_db` database, then choose **Tools → Query Tool**.
7. Run the schema SQL below.

## 5. Create the database tables

Run this once in the **new, empty** `book_portal_db` database. If you already created these tables, do not re-run the script blindly; inspect your existing schema first.

```sql
CREATE TABLE students (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

CREATE TABLE books (
    id SERIAL PRIMARY KEY,
    owner_id INT NOT NULL
        REFERENCES students(id) ON DELETE CASCADE,
    title VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'AVAILABLE',
    CONSTRAINT books_status_check
        CHECK (status IN ('AVAILABLE', 'LENT'))
);

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

### Optional: Insert sample students

The student dropdown needs records in `students`. For an empty database, run:

```sql
INSERT INTO students (name)
VALUES ('Ayush'), ('Anshika'), ('Manikanta');
```

If you already have students, skip this insert to avoid adding duplicates. Verify the students with:

```sql
SELECT * FROM students ORDER BY id;
```

Check the books and requests tables with:

```sql
SELECT * FROM books;
SELECT * FROM requests;
```

### Schema notes

- `SERIAL PRIMARY KEY` gives each row an automatically generated ID.
- `books.owner_id` references the student who listed the book.
- A book's status must be `AVAILABLE` or `LENT` (uppercase).
- A request starts as `PENDING`; its allowed statuses are `PENDING`, `ACCEPTED`, and `REJECTED`.
- `UNIQUE (borrower_id, book_id)` prevents the same student from creating multiple request rows for the same book. With this schema, that student cannot submit another row for the same book after a rejected request unless the database design is changed.

## 6. Configure PHP to connect to PostgreSQL

PHP needs the PDO PostgreSQL driver (`pdo_pgsql`).

1. Open XAMPP's PHP configuration file:

   ```text
   C:\xampp\php\php.ini
   ```

2. Find the line below. If it is commented out with a leading semicolon, remove the semicolon:

   ```ini
   extension=pdo_pgsql
   ```

3. The `pgsql` extension can also be enabled if needed, but **PDO-based database connections require `pdo_pgsql`**.
4. Save `php.ini`.
5. In XAMPP Control Panel, click **Stop** for Apache, then **Start** again so PHP reloads its configuration.

If the extension is not available or PHP reports `could not find driver`, confirm the extension DLL exists in XAMPP's PHP `ext` directory and check Apache's PHP error log.

## 7. Create your local database connection file

The real connection file, `config/db.php`, is intentionally excluded from Git because it contains local credentials. The repository provides `config/db.example.php` as a template.

From PowerShell, inside `C:\xampp\htdocs\Book_Portal`, copy the example file:

```powershell
Copy-Item config\db.example.php config\db.php
```

If you already have a working `config/db.php`, **keep it** instead of overwriting it. Otherwise, open `config/db.php` and set the correct database name, PostgreSQL username, and password. A PDO connection should follow this pattern:

```php
<?php

$pdo = new PDO(
    'pgsql:host=localhost;port=5432;dbname=book_portal_db',
    'YOUR_POSTGRES_USERNAME',
    'YOUR_POSTGRES_PASSWORD',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
```

Replace the placeholder credentials with your own local PostgreSQL credentials. Never commit real passwords, production credentials, or private connection details.

Check that `.gitignore` includes at least:

```gitignore
config/db.php
.env
```

Keep `config/db.example.php` in Git, with placeholders only. If real credentials were ever committed to a public repository, remove them from the code and rotate/change those credentials; deleting the file in a later commit does not erase its history.

## 8. Run the portal

1. Confirm Apache is running in XAMPP.
2. Confirm the PostgreSQL service is running.
3. Confirm `config/db.php` exists and contains the correct connection settings.
4. Open this URL in a browser:

   <http://localhost/Book_Portal/>

   Or go directly to <http://localhost/Book_Portal/index.php>.

The site is available locally on your computer; this does not publish it to the public internet.

## 9. Test the current features

1. **Student switcher:** Select a student. The URL should include a query parameter such as `?student_id=2`.
2. **Add a book:** Select a student, enter a title, and click **Add Book**. The new row should appear in PostgreSQL's `books` table with that student's ID as `owner_id` and status `AVAILABLE`.
3. **All listed books:** The table should show each book's title, owner's name, and status.
4. Verify data from pgAdmin, for example:

   ```sql
   SELECT b.id, b.title, s.name AS owner_name, b.status
   FROM books AS b
   JOIN students AS s ON s.id = b.owner_id
   ORDER BY b.id DESC;
   ```

Request submission and the owner-side accept/reject workflow are still under development. The completed workflow must validate requests server-side and use a single PostgreSQL transaction when accepting one request, rejecting competing pending requests for the same book, and marking that book as `LENT`.

## 10. Common problems

| Problem | What to check |
|---|---|
| `403 Forbidden` | Confirm the project is inside `C:\xampp\htdocs\Book_Portal` and open `index.php` directly. |
| `404 Not Found` | Check the folder name and that `index.php` is in the project root. |
| `could not find driver` | Enable `extension=pdo_pgsql` in `C:\xampp\php\php.ini`, then restart Apache. |
| Connection refused | Confirm PostgreSQL is running and the host/port are correct. |
| Database does not exist | Ensure the database name in pgAdmin matches `dbname` in `config/db.php`. |
| Relation/table does not exist | Run the schema SQL against `book_portal_db`, not another database. |
| Invalid book status / check constraint | Use the exact uppercase status values `AVAILABLE` and `LENT`. |
| CSS changes are not visible | Save `assets/css/style.css` and hard-refresh the page with `Ctrl + Shift + R`. |

## 11. Development workflow with Git

Run Git commands from the project root:

```powershell
cd C:\xampp\htdocs\Book_Portal
git status
git add .
git commit -m "Describe the change"
git push
```

Before committing, inspect `git status` and ensure `config/db.php` or other secret files are not staged.

## Technology summary

- **Frontend:** HTML5, CSS3, JavaScript/jQuery
- **Backend:** PHP (running through Apache in XAMPP)
- **Database:** PostgreSQL
- **Database access:** PDO with the `pdo_pgsql` driver
- **Database administration:** pgAdmin 4
