<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

const ALLOWED_STATUSES = ['Active', 'Inactive', 'Graduated', 'Suspended'];
const DEFAULT_PROGRAMS = [
    'Computer Science',
    'Business Information Technology',
    'Electrical Engineering',
    'Nursing',
];

handle_request();

function handle_request(): void
{
    $route = trim((string) ($_GET['route'] ?? ''), '/');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    try {
        bootstrap_database();

        if ($method === 'GET' && $route === 'health') {
            send_json(200, [
                'status' => 'ok',
                'databaseUrl' => get_database_url(),
            ]);
        }

        if ($method === 'GET' && $route === 'students') {
            send_json(200, fetch_students());
        }

        if ($method === 'GET' && $route === 'stats') {
            send_json(200, fetch_stats());
        }

        if ($method === 'POST' && $route === 'students') {
            $payload = read_json_input();
            $student = normalize_student_payload($payload);
            send_json(201, create_student($student));
        }

        if (preg_match('/^students\/(\d+)$/', $route, $matches) === 1) {
            $student_id = (int) $matches[1];

            if ($method === 'PUT') {
                $payload = read_json_input();
                $student = normalize_student_payload($payload);
                send_json(200, update_student($student_id, $student));
            }

            if ($method === 'DELETE') {
                delete_student($student_id);
                send_json(200, [
                    'deleted' => true,
                    'id' => $student_id,
                ]);
            }
        }

        send_json(404, ['error' => 'Route not found.']);
    } catch (RuntimeException $error) {
        $status = $error->getCode();
        if ($status < 400 || $status > 599) {
            $status = 500;
        }
        send_json($status, ['error' => $error->getMessage()]);
    } catch (Throwable $error) {
        send_json(500, ['error' => 'Unexpected server error.']);
    }
}

function bootstrap_database(): void
{
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }

    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Could not read database schema.', 500);
    }

    $pdo = get_pdo();
    $pdo->exec($schema);

    $count = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
    if ($count === 0) {
        seed_students($pdo);
    }

    $bootstrapped = true;
}

function seed_students(PDO $pdo): void
{
    $students = [
        ['STU-2026-001', 'Amina', 'Otieno', 'amina.otieno@example.com', '+254700111222', 'Computer Science', 2, 'Active'],
        ['STU-2026-002', 'Brian', 'Mwangi', 'brian.mwangi@example.com', '+254711222333', 'Business Information Technology', 1, 'Active'],
        ['STU-2026-003', 'Cynthia', 'Njeri', 'cynthia.njeri@example.com', '+254722333444', 'Electrical Engineering', 4, 'Graduated'],
        ['STU-2026-004', 'David', 'Kiplagat', 'david.kiplagat@example.com', '+254733444555', 'Nursing', 3, 'Active'],
    ];

    $statement = $pdo->prepare(
        'INSERT INTO students (
            admission_number,
            first_name,
            last_name,
            email,
            phone,
            program,
            year_level,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($students as $student) {
        $statement->execute($student);
    }
}

function fetch_students(): array
{
    $statement = get_pdo()->query(
        'SELECT
            id,
            admission_number,
            first_name,
            last_name,
            email,
            phone,
            program,
            year_level,
            status,
            created_at,
            updated_at
        FROM students
        ORDER BY last_name, first_name, admission_number'
    );

    return array_map('map_student', $statement->fetchAll());
}

function fetch_stats(): array
{
    $pdo = get_pdo();

    return [
        'totalStudents' => (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
        'activeStudents' => (int) $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn(),
        'graduatedStudents' => (int) $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Graduated'")->fetchColumn(),
        'programCount' => (int) $pdo->query('SELECT COUNT(DISTINCT program) FROM students')->fetchColumn(),
        'programOptions' => DEFAULT_PROGRAMS,
        'statusOptions' => ALLOWED_STATUSES,
    ];
}

function create_student(array $student): array
{
    $pdo = get_pdo();
    $statement = $pdo->prepare(
        'INSERT INTO students (
            admission_number,
            first_name,
            last_name,
            email,
            phone,
            program,
            year_level,
            status
        ) VALUES (
            :admission_number,
            :first_name,
            :last_name,
            :email,
            :phone,
            :program,
            :year_level,
            :status
        )'
    );

    try {
        $statement->execute($student);
    } catch (PDOException $error) {
        throw_unique_error($error);
    }

    return fetch_student_by_id((int) $pdo->lastInsertId());
}

function update_student(int $student_id, array $student): array
{
    $pdo = get_pdo();
    $statement = $pdo->prepare(
        'UPDATE students
        SET
            admission_number = :admission_number,
            first_name = :first_name,
            last_name = :last_name,
            email = :email,
            phone = :phone,
            program = :program,
            year_level = :year_level,
            status = :status,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id'
    );

    $student['id'] = $student_id;

    try {
        $statement->execute($student);
    } catch (PDOException $error) {
        throw_unique_error($error);
    }

    if ($statement->rowCount() === 0) {
        if (fetch_student_exists($student_id)) {
            return fetch_student_by_id($student_id);
        }
        throw new RuntimeException('Student not found.', 404);
    }

    return fetch_student_by_id($student_id);
}

function delete_student(int $student_id): void
{
    $statement = get_pdo()->prepare('DELETE FROM students WHERE id = ?');
    $statement->execute([$student_id]);

    if ($statement->rowCount() === 0) {
        throw new RuntimeException('Student not found.', 404);
    }
}

function fetch_student_by_id(int $student_id): array
{
    $statement = get_pdo()->prepare(
        'SELECT
            id,
            admission_number,
            first_name,
            last_name,
            email,
            phone,
            program,
            year_level,
            status,
            created_at,
            updated_at
        FROM students
        WHERE id = ?'
    );
    $statement->execute([$student_id]);
    $student = $statement->fetch();

    if ($student === false) {
        throw new RuntimeException('Student not found.', 404);
    }

    return map_student($student);
}

function fetch_student_exists(int $student_id): bool
{
    $statement = get_pdo()->prepare('SELECT id FROM students WHERE id = ?');
    $statement->execute([$student_id]);
    return $statement->fetchColumn() !== false;
}

function map_student(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'admissionNumber' => $row['admission_number'],
        'firstName' => $row['first_name'],
        'lastName' => $row['last_name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'program' => $row['program'],
        'yearLevel' => (int) $row['year_level'],
        'status' => $row['status'],
        'createdAt' => $row['created_at'],
        'updatedAt' => $row['updated_at'],
    ];
}

function read_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        throw new RuntimeException('Request body is required.', 400);
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        throw new RuntimeException('Request body must be valid JSON.', 400);
    }

    return $payload;
}

function normalize_student_payload(array $payload): array
{
    $status = validate_text($payload['status'] ?? null, 'Status', 20);
    if (!in_array($status, ALLOWED_STATUSES, true)) {
        throw new RuntimeException(
            'Status must be one of: ' . implode(', ', ALLOWED_STATUSES) . '.',
            400
        );
    }

    return [
        'admission_number' => validate_text($payload['admissionNumber'] ?? null, 'Admission number', 40),
        'first_name' => validate_text($payload['firstName'] ?? null, 'First name', 60),
        'last_name' => validate_text($payload['lastName'] ?? null, 'Last name', 60),
        'email' => validate_text($payload['email'] ?? null, 'Email', 120),
        'phone' => validate_text($payload['phone'] ?? null, 'Phone', 30),
        'program' => validate_text($payload['program'] ?? null, 'Program', 120),
        'year_level' => validate_year_level($payload['yearLevel'] ?? null),
        'status' => $status,
    ];
}

function validate_text(mixed $value, string $field_name, int $max_length): string
{
    if (!is_string($value)) {
        throw new RuntimeException($field_name . ' must be a string.', 400);
    }

    $cleaned = trim($value);
    if ($cleaned === '') {
        throw new RuntimeException($field_name . ' is required.', 400);
    }

    if (mb_strlen($cleaned) > $max_length) {
        throw new RuntimeException($field_name . ' must be ' . $max_length . ' characters or fewer.', 400);
    }

    return $cleaned;
}

function validate_year_level(mixed $value): int
{
    if (!is_numeric($value)) {
        throw new RuntimeException('Year level must be a valid number.', 400);
    }

    $year_level = (int) $value;
    if ($year_level < 1 || $year_level > 8) {
        throw new RuntimeException('Year level must be between 1 and 8.', 400);
    }

    return $year_level;
}

function throw_unique_error(PDOException $error): never
{
    if ($error->getCode() === '23000') {
        throw new RuntimeException('Admission number and email must both be unique.', 409);
    }

    throw new RuntimeException('Database operation failed.', 500);
}

function send_json(int $status, array $payload): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}
