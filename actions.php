<?php
    require_once __DIR__ . '/config/db.php';

    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }

    $action = $_POST['action'] ?? '';

    if($action !== 'add_book'){
        http_response_code(400);
        exit('Unknown action');
    }

    $rawTitle = $_POST['title'] ?? '';

    $studentId = filter_input(
        INPUT_POST,
        'student_id',
        FILTER_VALIDATE_INT
    );

    if(!is_string($rawTitle)){
        http_response_code(400);
        exit('Invalid Book Title');
    }

    $title = trim($rawTitle);

    if(
        $title === '' ||
        strlen($title) > 100 ||
        $studentId === NULL ||
        $studentId === FALSE ||
        $studentId < 1
    ){
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

