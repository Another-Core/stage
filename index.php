
<?php
require __DIR__ . '/db.php';
require __DIR__ . '/rules.php';

// Send data as JSON and stop the script
function sendJson(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Find a shift by its ID
function findShift(PDO $pdo, string $id): ?array
{
    $query = $pdo->prepare('SELECT * FROM shifts WHERE id = ?');
    $query->execute([$id]);
    return $query->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Get all shifts assigned to an employee
function assignedShifts(PDO $pdo, string $employeeId): array
{
    $query = $pdo->prepare('SELECT * FROM shifts WHERE assignedEmployeeId = ?');
    $query->execute([$employeeId]);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}


// Get the requested URL path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Check if the URL matches one of our API endpoints
if (!preg_match('~/shifts/([a-zA-Z0-9_-]+)/(assign|eligible-employees)/?$', $path, $parts)) {
    sendJson(['error' => 'Unknown endpoint'], 404);
}

// Get shift ID, action and request method
$id = $parts[1];
$action = $parts[2];
$method = $_SERVER['REQUEST_METHOD'];

// Check if the HTTP method is correct for the action
if (
    ($action === 'assign' && $method !== 'POST') 
    ||
    ($action === 'eligible-employees' && $method !== 'GET')
    ) {
        sendJson(['error' => 'Method not allowed'], 405);
}


// Find the requested shift
$shift = findShift($pdo, $id);

// Check if the shift exists
if (!$shift) {
    sendJson(['error' => 'Shift not found'], 404);
}

// Check if the shift already has an employee
if ($shift['assignedEmployeeId'] !== null) {
    sendJson(['error' => 'Shift is already assigned'], 409);
}


// Handle the POST request to assign an employee
if ($action === 'assign') {

    // Read JSON data from the request
    $body = json_decode(file_get_contents('php://input'), true);
    
    // Check if employeeId exists and is a string
    if (
        !is_array($body) 
        || 
        !isset($body['employeeId']) 
        || 
        !is_string($body['employeeId'])
        ) 
        {
        sendJson(['error' => 'employeeId is required'], 400);
    }

    // Find the employee in the database
    $query = $pdo->prepare('SELECT * FROM employees WHERE id = ?');
    $query->execute([$body['employeeId']]);
    $employee = $query->fetch(PDO::FETCH_ASSOC);
    
    // Check if the employee exists
    if (!$employee) {
        sendJson(['error' => 'Employee not found'], 404);
    }

    // Check all assignment rules
    $violations = checkRules($employee, $shift, assignedShifts($pdo, $employee['id']));
    
    // Return all errors if any rules are broken
    if ($violations) {
        sendJson(['violations' => $violations], 422);
    }

    // Assign the employee only if the shift is still free
    $query = $pdo->prepare(
        'UPDATE shifts SET assignedEmployeeId = ? WHERE id = ? AND assignedEmployeeId IS NULL'
        );
    $query->execute([$employee['id'], $id]);
    
    // Check if the assignment failed
    if ($query->rowCount() === 0) {
        sendJson(['error' => 'Shift is already assigned'], 409);
    }

    // Return the updated shift
    sendJson(findShift($pdo, $id));
}


// Handle GET /shifts/:id/eligible-employees

// Get all employees from the database
$employees = $pdo->query('SELECT * FROM employees')->fetchAll(PDO::FETCH_ASSOC);

// Create an empty list for eligible employees
$result = [];

// Check every employee
foreach ($employees as $employee) {

    // Get the employee's assigned shifts
    $previous = assignedShifts($pdo, $employee['id']);

    // Add the employee if all rules are passed
    if (checkRules($employee, $shift, $previous) === []) {
        $result[] = [
            'id' => $employee['id'],
            'name' => $employee['name'],
            // Calculate hours left after the new shift
            'remainingHours' => remainingHours($employee, $shift, $previous)
        ];
    }
}

// Sort employees by remaining hours (highest first)
usort($result, fn($a, $b) => $b['remainingHours'] <=> $a['remainingHours']);

// Return the list of eligible employees
sendJson(['employees' => $result]);