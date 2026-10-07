<?php

header("Content-Type: application/json");


require_once "db.php";



if (!$conn) {

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit;
}




if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $action = $_GET["action"] ?? "";


    if ($action === "list") {

        $sql = "SELECT id, picture, name, price FROM items ORDER BY id DESC";

        $result = mysqli_query($conn, $sql);

        if (!$result) {

            echo json_encode([
                "success" => false,
                "message" => "Unable to load items."
            ]);

            exit;
        }


        $items = [];


        while ($row = mysqli_fetch_assoc($result)) {

            $items[] = [

                "id" => $row["id"],

                "picture" => base64_encode($row["picture"]),

                "name" => $row["name"],

                "price" => $row["price"]

            ];
        }


        echo json_encode([
            "success" => true,
            "items" => $items
        ]);

        exit;
    }
}



if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "add";


    if ($action === "remove") {

        $id = intval($_POST["id"] ?? 0);


        if ($id <= 0) {

            echo json_encode([
                "success" => false,
                "message" => "Invalid item ID."
            ]);

            exit;
        }


        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM items WHERE id = ?"
        );


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $id
        );


        if (mysqli_stmt_execute($stmt)) {

            echo json_encode([
                "success" => true,
                "message" => "Item removed."
            ]);

        } else {

            echo json_encode([
                "success" => false,
                "message" => "Failed to remove item."
            ]);
        }


        mysqli_stmt_close($stmt);

        exit;
    }



    $name = trim($_POST["name"] ?? "");
    $price = trim($_POST["price"] ?? "");


    if ($name === "") {

        echo json_encode([
            "success" => false,
            "message" => "Please enter an item name."
        ]);

        exit;
    }


    if ($price === "") {

        echo json_encode([
            "success" => false,
            "message" => "Please enter a price."
        ]);

        exit;
    }



    if (!isset($_FILES["picture"])) {

        echo json_encode([
            "success" => false,
            "message" => "Please select an item picture."
        ]);

        exit;
    }


    if ($_FILES["picture"]["error"] !== UPLOAD_ERR_OK) {

        echo json_encode([
            "success" => false,
            "message" => "There was an error uploading the picture."
        ]);

        exit;
    }


    $picture = file_get_contents(
        $_FILES["picture"]["tmp_name"]
    );



    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO items (picture, name, price)
         VALUES (?, ?, ?)"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "bss",
        $picture,
        $name,
        $price
    );



    mysqli_stmt_send_long_data(
        $stmt,
        0,
        $picture
    );


    if (mysqli_stmt_execute($stmt)) {

        echo json_encode([
            "success" => true,
            "message" => "Item added successfully.",
            "id" => mysqli_insert_id($conn)
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Failed to add item: " . mysqli_error($conn)
        ]);
    }


    mysqli_stmt_close($stmt);

    exit;
}



echo json_encode([
    "success" => false,
    "message" => "Invalid request."
]);

?>