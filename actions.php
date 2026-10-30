<?php
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$action = $_POST['action'] ?? '';

if ($action === 'request_book') {

    $borrowerId = filter_input(
        INPUT_POST,
        'student_id',
        FILTER_VALIDATE_INT
    );

    $bookId = filter_input(
        INPUT_POST,
        'book_id',
        FILTER_VALIDATE_INT
    );

    if (
        $borrowerId === false ||
        $borrowerId === null ||
        $borrowerId < 1 ||
        $bookId === false ||
        $bookId === null ||
        $bookId < 1
    ) {
        http_response_code(400);
        exit('Invalid student ID or book ID');
    }

    try {

        $pdo->beginTransaction();

        $bookStmt = $pdo->prepare(
            "SELECT id, owner_id, status
             FROM books
             WHERE id = :book_id
             FOR UPDATE"
        );

        $bookStmt->execute([
            'book_id' => $bookId
        ]);

        $book = $bookStmt->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            $pdo->rollBack();
            http_response_code(404);
            exit('Book not found');
        }

        if (
            (int) $book['owner_id'] === $borrowerId
        ) {
            $pdo->rollBack();
            http_response_code(403);
            exit('You cannot request your own book');
        }

        if ($book['status'] !== 'AVAILABLE') {
            $pdo->rollBack();
            http_response_code(409);
            exit('This book is no longer available');
        }

        $requestStmt = $pdo->prepare(
            "INSERT INTO requests (book_id, borrower_id)
             VALUES (:book_id, :borrower_id)
             ON CONFLICT (borrower_id, book_id) DO NOTHING"
        );

        $requestStmt->execute([
            'book_id' => $bookId,
            'borrower_id' => $borrowerId
        ]);

        $pdo->commit();

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log($e->getMessage());

        http_response_code(500);
        exit('Could not submit the request');
    }

    header('Location: index.php?student_id=' . $borrowerId);
    exit;
}

if ($action === 'reject_request') {

    $ownerId = filter_input(
        INPUT_POST,
        'student_id',
        FILTER_VALIDATE_INT
    );

    $requestId = filter_input(
        INPUT_POST,
        'request_id',
        FILTER_VALIDATE_INT
    );

    if (
        $ownerId === false ||
        $ownerId === null ||
        $ownerId < 1 ||
        $requestId === false ||
        $requestId === null ||
        $requestId < 1
    ) {
        http_response_code(400);
        exit('Invalid owner ID or request ID');
    }

    try {

        $pdo->beginTransaction();

        $lookupStmt = $pdo->prepare(
            "SELECT book_id
             FROM requests
             WHERE id = :request_id"
        );

        $lookupStmt->execute([
            'request_id' => $requestId
        ]);

        $requestInfo = $lookupStmt->fetch(PDO::FETCH_ASSOC);

        if (!$requestInfo) {
            $pdo->rollBack();
            http_response_code(404);
            exit('Request not found');
        }

        $bookId = (int) $requestInfo['book_id'];

                $bookStmt = $pdo->prepare(
            "SELECT owner_id
             FROM books
             WHERE id = :book_id
             FOR UPDATE"
        );

        $bookStmt->execute([
            'book_id' => $bookId
        ]);

        $book = $bookStmt->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            $pdo->rollBack();
            http_response_code(404);
            exit('Book not found');
        }

        if ((int) $book['owner_id'] !== $ownerId) {
            $pdo->rollBack();
            http_response_code(403);
            exit('You do not own this book');
        }

                $requestStmt = $pdo->prepare(
            "SELECT status
             FROM requests
             WHERE id = :request_id
             AND book_id = :book_id
             FOR UPDATE"
        );

        $requestStmt->execute([
            'request_id' => $requestId,
            'book_id' => $bookId
        ]);

        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            $pdo->rollBack();
            http_response_code(404);
            exit('Request not found');
        }

        if ($request['status'] !== 'PENDING') {
            $pdo->rollBack();
            http_response_code(409);
            exit('This request has already been processed');
        }

                $rejectStmt = $pdo->prepare(
            "UPDATE requests
             SET status = 'REJECTED'
             WHERE id = :request_id
             AND status = 'PENDING'"
        );

        $rejectStmt->execute([
            'request_id' => $requestId
        ]);

        if ($rejectStmt->rowCount() !== 1) {
            $pdo->rollBack();
            http_response_code(409);
            exit('Request could not be rejected');
        }

        $pdo->commit();
    }
    
     catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log($e->getMessage());

        http_response_code(500);
        exit('Could not reject request');
    }

    header('Location: index.php?student_id=' . $ownerId);
    exit;

}


if ($action === 'accept_request') {


    $ownerId = filter_input(
        INPUT_POST,
        'student_id',
        FILTER_VALIDATE_INT
    );

    $requestId = filter_input(
        INPUT_POST,
        'request_id',
        FILTER_VALIDATE_INT
    );

    if (
        $ownerId === false ||
        $ownerId === null ||
        $ownerId < 1 ||
        $requestId === false ||
        $requestId === null ||
        $requestId < 1
    ) {
        http_response_code(400);
        exit('Invalid owner ID or request ID');
    }


    try {


        $pdo->beginTransaction();



        $lookupStmt = $pdo->prepare(
            "SELECT book_id
             FROM requests
             WHERE id = :request_id"
        );

        $lookupStmt->execute([
            'request_id' => $requestId
        ]);

        $requestInfo = $lookupStmt->fetch(PDO::FETCH_ASSOC);

        if (!$requestInfo) {
            $pdo->rollBack();
            http_response_code(404);
            exit('Request not found');
        }

        $bookId = (int) $requestInfo['book_id'];



        $bookStmt = $pdo->prepare(
            "SELECT id, owner_id, status
             FROM books
             WHERE id = :book_id
             FOR UPDATE"
        );

        $bookStmt->execute([
            'book_id' => $bookId
        ]);

        $book = $bookStmt->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            $pdo->rollBack();
            http_response_code(404);
            exit('Book not found');
        }

        if ((int) $book['owner_id'] !== $ownerId) {
            $pdo->rollBack();
            http_response_code(403);
            exit('You do not own this book');
        }

        if ($book['status'] !== 'AVAILABLE') {
            $pdo->rollBack();
            http_response_code(409);
            exit('This book is no longer available');
        }



        $requestStmt = $pdo->prepare(
            "SELECT id, book_id, borrower_id, status
             FROM requests
             WHERE id = :request_id
             AND book_id = :book_id
             FOR UPDATE"
        );

        $requestStmt->execute([
            'request_id' => $requestId,
            'book_id' => $bookId
        ]);

        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            $pdo->rollBack();
            http_response_code(404);
            exit('Request not found');
        }

        if ($request['status'] !== 'PENDING') {
            $pdo->rollBack();
            http_response_code(409);
            exit('This request has already been processed');
        }



        $acceptStmt = $pdo->prepare(
            "UPDATE requests
             SET status = 'ACCEPTED'
             WHERE id = :request_id
             AND status = 'PENDING'"
        );

        $acceptStmt->execute([
            'request_id' => $requestId
        ]);

        if ($acceptStmt->rowCount() !== 1) {
            $pdo->rollBack();
            http_response_code(409);
            exit('Could not accept this request');
        }



        $rejectOthersStmt = $pdo->prepare(
            "UPDATE requests
             SET status = 'REJECTED'
             WHERE book_id = :book_id
             AND id <> :request_id
             AND status = 'PENDING'"
        );

        $rejectOthersStmt->execute([
            'book_id' => $bookId,
            'request_id' => $requestId
        ]);



        $updateBookStmt = $pdo->prepare(
            "UPDATE books
             SET status = 'LENT'
             WHERE id = :book_id
             AND owner_id = :owner_id
             AND status = 'AVAILABLE'"
        );

        $updateBookStmt->execute([
            'book_id' => $bookId,
            'owner_id' => $ownerId
        ]);

        if ($updateBookStmt->rowCount() !== 1) {
            $pdo->rollBack();
            http_response_code(409);
            exit('Could not update book status');
        }



        $pdo->commit();


    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log($e->getMessage());

        http_response_code(500);
        exit('Could not accept the request');
    }



    header('Location: index.php?student_id=' . $ownerId);
    exit;
}

if ($action !== 'add_book') {
    http_response_code(400);
    exit('Unknown action');
}

$rawTitle = $_POST['title'] ?? '';

$studentId = filter_input(
    INPUT_POST,
    'student_id',
    FILTER_VALIDATE_INT
);

if (!is_string($rawTitle)) {
    http_response_code(400);
    exit('Invalid Book Title');
}

$title = trim($rawTitle);

if (
    $title === '' ||
    strlen($title) > 100 ||
    $studentId === NULL ||
    $studentId === FALSE ||
    $studentId < 1
) {
    http_response_code(400);
    exit('Invalid Title or Student ID');
}

$stmt = $pdo->prepare(
    "INSERT INTO books (owner_id, title)
        VALUES (:owner_id, :title)"

);

$stmt->execute([
    'owner_id' => $studentId,
    'title' => $title
]);

header("Location: index.php?student_id=" . $studentId);
exit;

