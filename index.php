<?php
require_once __DIR__ . '/config/db.php';

try {
    $stmt = $pdo->query(
        "SELECT id, name FROM students ORDER BY id"
    );

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    exit("Could not fetch students. Check your database connection and students table.");
}

$requestedId = filter_input(
    INPUT_GET,
    'student_id',
    FILTER_VALIDATE_INT
);

$validIds = array_map(
    'intval',
    array_column($students, 'id')
);

if (
    $requestedId !== false &&
    $requestedId !== null &&
    in_array($requestedId, $validIds, true)
) {
    $currentStudentId = $requestedId;
} else {
    $currentStudentId = !empty($students)
        ? (int) $students[0]['id']
        : null;
}

$bookStmt = $pdo->query(
    "SELECT
        b.id,
        b.title,
        s.name AS owner_name,
        b.status
     FROM books b
     JOIN students s
        ON b.owner_id = s.id
     ORDER BY b.id DESC"
);

$books = $bookStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Book Portal</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <main class="container">

        <div class="card">

            <h2>Logged in as:</h2>

            <?php if (empty($students)): ?>

                <p>No students found. Add students to your database first.</p>

            <?php else: ?>

                <form method="GET" action="index.php">

                    <select
                        name="student_id"
                        onchange="this.form.submit()"
                    >

                        <?php foreach ($students as $s): ?>

							<option
								value="<?=(int) $s['id']?>"
								<?= (int) $s['id'] === $currentStudentId
									? 'selected' 
									: '' ?>
							>

								<?= htmlspecialchars(
									$s['name'],
									ENT_QUOTES,
									'UTF-8'
								)?>
								(Id:<?= (int) $s['id']?>)
							</option>

						<?php endforeach; ?>	

                    </select>

                </form>

            <?php endif; ?>

        </div>

		<div class="card">

		<h2>List a book for lending</h2>
		
		<form method="post" action="actions.php">

			<input
				type="hidden"
				name="action"
				value="add_book"
			>

			<input
				type="hidden"
				name="student_id"
				value="<?= (int) $currentStudentId ?>"
			>

			<label for="title">Title :</label>
			
			<input
            	type="text"
            	id="title"
            	name="title"
            	maxlength="100"
            	placeholder="Enter book title..."
            	required
            >

			<button type="submit">Add Book</button>

		</form>

		</div>

		<div class="card">
			<h2>All Listed Books</h2>
			<?php if (empty($books)): ?>
				<p>No books listed yet</p>
				
			<?php else: ?>

				<div class="table-wrapper">
					<table class="books-table">

						<thead>
							<tr>
								<th scope="col">Title</th>
								<th scope="col">Owner</th>
								<th scope="col">Status</th>
							</tr>
						</thead>

						<tbody>
							<?php foreach($books as $book): ?>
							<tr>
								<td>
									<?= htmlspecialchars(
										$book['title'],
										ENT_QUOTES,
										'UTF-8'
									) ?>
								</td>

								<td>
									<?= htmlspecialchars(
										$book['owner_name'],
										ENT_QUOTES,
										'UTF-8'
									) ?>
								</td>
								
								<td>
									<?= htmlspecialchars(
										$book['status'],
										ENT_QUOTES,
										'UTF-8'
									) ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>				
			<?php endif; ?>
		


		</div>

    </main>

</body>
</html>