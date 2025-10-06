<?php

include "connection.php";

$successMsg = '';
$errorMsg = '';
$eventsFromDB = [];


//Handle add Appointments
// ?? null coalescing operator --> only for null or undefined

if($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === "add"){
    // server["request_method] -> this tells you how the form was submitted 
    $course = trim($_POST["course_name"] ?? '');
    $instructor  = trim($_POST["instructor_name"] ?? '');
    $start       = $_POST["start_date"] ?? ''; //only uses "" if $instructor is null or undefined 
    $end         = $_POST["end_date"] ?? '';
    $startTime   = $_POST["start_time"] ?? '';
    $endTime     = $_POST["end_time"] ?? '';

    if ($course && $instructor && $start && $end && $startTime && $endTime){

        $stmt = $conn->prepare(
            "INSERT INTO appointments (course_name, instructor_name, start_date, end_date, start_time, end_time) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );  //creates a prepared statement -> ? are placehiolders for the actual values you'll insert later 
        $stmt->bind_param("ssssss",$course, $instructor, $start, $end, $startTime, $endTime); //the ssss--> means all are string values for the 6 parameters
        $stmt->execute(); //execute the query
        $stmt->close(); //frees up resources (memory) used by the prepared statement

        header("Location: " . $_SERVER["PHP_SELF"] . "?success=1");/*
        header("Location: ...") tells the browser to redirect to another page (here it redirects back to the same page).
        $_SERVER["PHP_SELF"] is the current file’s name (so it refreshes the same page).
        ?success=1 adds a query parameter to the URL, meaning “success”.
        */
        exit;
    }else{
        header("Location: " . $_SERVER["PHP_SELF"] . "?error=1");
        exit;
    }
}

//Handle Edit Appointment
if($_SERVER["REQUEST_METHOD"]=== "POST" && ($_POST['action'] ?? '') === "edit"){
    $id          = $_POST["event_id"] ?? null;
    $course      = trim($_POST["course_name"] ?? '');
    $instructor  = trim($_POST["instructor_name"] ?? '');
    $start       = $_POST["start_date"] ?? '';
    $end         = $_POST["end_date"] ?? '';
    $startTime   = $_POST["start_time"] ?? '';
    $endTime     = $_POST["end_time"] ?? '';

     if ($id && $course && $instructor && $start && $end && $startTime && $endTime){
        $stms = $conn->prepare(
             "UPDATE appointments SET course_name = ?, instructor_name = ?, start_date = ?, end_date = ?, start_time = ?, end_time = ? 
             WHERE id = ?"
        );
        $stmt->bind_param("ssssssi", $course, $instructor, $start, $end, $startTime, $endTime, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: " . $_SERVER["PHP_SELF"] . "?success=2");
        exit;
     }else{
        header("Location: " . $_SERVER["PHP_SELF"] . "?error=2");
        exit;
     }
}


//Handle Delete Appointment 
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === "delete"){
    $id = $_POST["event_id"] ?? null;

    if($id){
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt ->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: " . $_SERVER["PHP_SELF"] . "?success=3");
        exit;
    }
}


//Success and Error Messages

if(isset($_GET["success"])){
    //isset() check whether that variable exists (i.e., the URL includes success=).
    $successMsg = match ($_GET["success"]){
        '1' => "✅ Appointment added successfully",
        '2' => "✅ Appointment updated successfully",
        '3' => "🗑️ Appointment deleted successfully",
        default => ''
    };
}

if (isset($_GET["error"])){
    $errorMsg = '❗ Error occurred. Please check your input.';
}

// match is like a smart version of swtich introduced in PHP 8

//Fetch Appointments from DB and spread by date

$result = $conn->query("SELECT * FROM appointments");

if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        //fetch_assoc() is a MySQLi function that fetches the next row from the result set as an associatev e array
        /*
        If your table has columns:
        id | course_name | instructor_name | start_date | end_date

        then $row will look like
        [
  "id" => 1,
  "course_name" => "Web Dev 101",
  "instructor_name" => "Mr. Tan",
  "start_date" => "2025-10-06",
  "end_date" => "2025-10-08"
]



        */
        $start = new DateTime($row["start_date"]);
        $end = new DateTime($row["end_date"]);

        while ($start <= $end){
            $eventsFromDB[] = [
                "id"          => $row["id"],
                "title"       => "{$row['course_name']} - {$row['instructor_name']}",
                "date"        => $start->format('Y-m-d'),
                "start"       => $row["start_date"],
                "end"         => $row["end_date"],
                "start_time"  => $row["start_time"],
                "end_time"    => $row["end_time"],
            ];

            //$eventsFRomDB is a PHP indexed array of associative arrays
            //$eventsFromDB is a multidimensional array (an array of associative arrays)
            $start->modify('+1 day');
            //This moves $start forward by one day, so that the loop can continue until it reaches $end.
        }
    }
}
$conn->close();
?>