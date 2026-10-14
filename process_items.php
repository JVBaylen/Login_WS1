
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

mysqli_set_charset($conn, "utf8mb4");

function sendResponse($success, $message, $extra = [])
{
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));
    exit;
}

if (!$conn || mysqli_connect_errno()) {
    sendResponse(false, "Database connection failed.");
}

/* GET: LOAD ITEMS */
if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $action = $_GET["action"] ?? "";

    if ($action !== "list") {
        sendResponse(false, "Invalid GET action.");
    }

    $sql = "SELECT id, picture, name, price
            FROM items
            ORDER BY id DESC";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        sendResponse(false, "Unable to load items.");
    }

    $items = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            "id" => (int) $row["id"],
            "picture" => base64_encode($row["picture"] ?? ""),
            "name" => $row["name"],
            "price" => $row["price"]
        ];
    }

    mysqli_free_result($result);

    echo json_encode([
        "success" => true,
        "message" => "Items loaded successfully.",
        "items" => $items
    ]);

    exit;
}

/* POST: ADD OR REMOVE ITEMS */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "add";

    /* REMOVE ITEM */
    if ($action === "remove") {

        $rawId = $_POST["id"] ?? "";

        if (
            !is_scalar($rawId) ||
            !ctype_digit((string) $rawId) ||
            (int) $rawId <= 0
        ) {
            sendResponse(
                false,
                "Invalid item ID. Check the ID returned by the database."
            );
        }

        $id = (int) $rawId;

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM items WHERE id = ?"
        );

        if (!$stmt) {
            sendResponse(false, "Unable to prepare item deletion.");
        }

        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            sendResponse(false, "Failed to remove item.");
        }

        $affectedRows = mysqli_stmt_affected_rows($stmt);

        mysqli_stmt_close($stmt);

        if ($affectedRows > 0) {
            sendResponse(true, "Item removed successfully.");
        }

        sendResponse(
            false,
            "No item found with ID " . $id . ". Check your items table."
        );
    }

    /* ADD ITEM */
    if ($action !== "add") {
        sendResponse(false, "Invalid POST action.");
    }

    $name = trim($_POST["name"] ?? "");
    $price = trim($_POST["price"] ?? "");

    if ($name === "") {
        sendResponse(false, "Please enter an item name.");
    }

    if ($price === "") {
        sendResponse(false, "Please enter a price.");
    }

    if (!isset($_FILES["picture"])) {
        sendResponse(false, "Please select an item picture.");
    }

    if ($_FILES["picture"]["error"] !== UPLOAD_ERR_OK) {
        sendResponse(false, "There was an error uploading the picture.");
    }

    if (!is_uploaded_file($_FILES["picture"]["tmp_name"])) {
        sendResponse(false, "Invalid picture upload.");
    }

    if ($_FILES["picture"]["size"] > 5 * 1024 * 1024) {
        sendResponse(false, "Picture must be 5 MB or smaller.");
    }

    $imageInfo = getimagesize($_FILES["picture"]["tmp_name"]);

    if ($imageInfo === false) {
        sendResponse(false, "The uploaded file is not a valid image.");
    }

    $picture = file_get_contents($_FILES["picture"]["tmp_name"]);

    if ($picture === false) {
        sendResponse(false, "Unable to read the uploaded picture.");
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO items (picture, name, price)
         VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        sendResponse(false, "Unable to prepare item insertion.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "bss",
        $picture,
        $name,
        $price
    );

    mysqli_stmt_send_long_data($stmt, 0, $picture);

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        sendResponse(false, "Failed to add item.");
    }

    $newItemId = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    if ($newItemId <= 0) {
        sendResponse(
            false,
            "Item was added, but the database did not return a valid ID."
        );
    }

    sendResponse(
        true,
        "Item added successfully.",
        ["id" => $newItemId]
    );
}

sendResponse(false, "Invalid request method.");

?>
