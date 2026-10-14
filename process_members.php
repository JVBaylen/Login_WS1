
<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . "/db.php";

mysqli_set_charset($conn, "utf8mb4");

function respond($success, $message, $extra = [])
{
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));
    exit;
}

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    respond(false, "Database connection failed.");
}

$user = $_SESSION["user"] ?? null;

if (
    !is_array($user) ||
    !isset($user["id"], $user["role"]) ||
    strtolower(trim((string) $user["role"])) !== "staff"
) {
    http_response_code(403);
    respond(false, "Staff session missing or invalid. Please log in again using your staff account.");
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $sql = "SELECT id, username, role, is_blocked
            FROM member
            WHERE role = 'member'
            ORDER BY id DESC";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        http_response_code(500);
        respond(false, "Unable to load members. Check the member table and is_blocked column.");
    }

    $members = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = [
            "id" => (int) $row["id"],
            "username" => $row["username"],
            "role" => $row["role"],
            "is_blocked" => (int) $row["is_blocked"]
        ];
    }

    mysqli_free_result($result);

    respond(true, "Members loaded successfully.", [
        "members" => $members
    ]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    respond(false, "Method not allowed.");
}

$action = $_POST["action"] ?? "";
$rawId = $_POST["id"] ?? "";

if (!in_array($action, ["block", "unblock"], true)) {
    http_response_code(400);
    respond(false, "Invalid action.");
}

if (
    !is_string($rawId) ||
    !ctype_digit($rawId) ||
    (int) $rawId < 1
) {
    http_response_code(400);
    respond(false, "Invalid member ID.");
}

$id = (int) $rawId;
$blocked = ($action === "block") ? 1 : 0;

$stmt = mysqli_prepare(
    $conn,
    "UPDATE member
     SET is_blocked = ?
     WHERE id = ? AND role = 'member'"
);

if (!$stmt) {
    http_response_code(500);
    respond(false, "Unable to prepare account update.");
}

mysqli_stmt_bind_param($stmt, "ii", $blocked, $id);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    respond(false, "Unable to update member account.");
}

mysqli_stmt_close($stmt);

$check = mysqli_prepare(
    $conn,
    "SELECT id
     FROM member
     WHERE id = ? AND role = 'member' AND is_blocked = ?"
);

if (!$check) {
    http_response_code(500);
    respond(false, "Unable to verify account status.");
}

mysqli_stmt_bind_param($check, "ii", $id, $blocked);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);

$found = mysqli_stmt_num_rows($check) > 0;

mysqli_stmt_close($check);

if (!$found) {
    respond(false, "Member not found.");
}

respond(
    true,
    $blocked
        ? "Member blocked successfully."
        : "Member unblocked successfully.",
    [
        "id" => $id,
        "is_blocked" => $blocked
    ]
);

?>
