<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$creds = json_decode(file_get_contents("php://input")) ?: (object) [];

$servername = "localhost";
$username = "id14584234_raghav57221";
$password = "R@ghavgpt123";
$database = "id14584234_academia";

$regno = $creds->regno;
$section = $creds->section;
$degree = $creds->degree;
$stream = $creds->stream;
$rating = $creds->rating;
$cookie = isset($creds->cookie) ? $creds->cookie : "";

// $regno = "RA1811003010419";
// $section = "A2";
// $degree = "BTech";
// $stream = "Computer Science and Technology";
// $rating = "1.241";
// $cookie = "dks";

$tbname = strtolower($degree . "_" . $stream . "_" . substr($regno, 0, 4));
$tbname = preg_replace("/\s+/", "_", $tbname);

$query = "CREATE TABLE IF NOT EXISTS $tbname (
    Id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Regno VARCHAR(15),
    Degree VARCHAR(100),
    Section VARCHAR(255),
    Rating FLOAT(3),
    Cookie TEXT(500)
);";

$mysqli = new mysqli($servername, $username, $password, $database);
$res = $mysqli->query($query);
$query = "SELECT * FROM $tbname WHERE Regno = '$regno'";
$res = $mysqli->query($query);

if ($res->fetch_assoc()) {
    $query = "UPDATE $tbname SET Section = '$section', Rating = '$rating' , Cookie = '$cookie' WHERE Regno = '$regno'";
    $res = $mysqli->query($query);
} else {
    $query = "INSERT INTO $tbname (Regno, Degree, Section, Rating, Cookie) VALUES ('$regno', '$degree', '$section', '$rating', '$cookie');";
    $res = $mysqli->query($query);
}

// #Analytics
$analysis = new stdClass();
//Stream
$query = "SELECT MIN(Rating), MAX(Rating), AVG(Rating), COUNT(Id) FROM $tbname";
$res = $mysqli->query($query);
$row = $res->fetch_assoc();
$stream = new stdClass();
//Piling
$stream->min = round($row["MIN(Rating)"], 3);
$stream->max = round($row["MAX(Rating)"], 3);
$stream->average = round($row["AVG(Rating)"], 3);
$stream->count = $row["COUNT(Id)"];

$query = "SELECT Regno, Rnk FROM ( SELECT Regno, Rating, RANK() OVER ( ORDER BY Rating DESC) Rnk FROM $tbname ) a WHERE Regno = '$regno'";
$res = $mysqli->query($query);
$row = $res->fetch_assoc();
//piling
$stream->rank = $row["Rnk"];

$analysis->stream = $stream;

//Section
$sectionObj = new stdClass();
$query = "SELECT MIN(Rating), MAX(Rating), AVG(Rating), COUNT(Id) FROM $tbname WHERE Section = '$section'";
$res = $mysqli->query($query);
$row = $res->fetch_assoc();
//Piling
$sectionObj->min = round($row["MIN(Rating)"], 3);
$sectionObj->max = round($row["MAX(Rating)"], 3);
$sectionObj->average = round($row["AVG(Rating)"], 3);
$sectionObj->count = $row["COUNT(Id)"];

$query = "SELECT Regno, Rnk FROM ( SELECT Regno, Rating, RANK() OVER ( ORDER BY Rating DESC) Rnk FROM $tbname WHERE Section = '$section' ) a WHERE Regno = '$regno'";
$res = $mysqli->query($query);
$row = $res->fetch_assoc();
//piling
$sectionObj->rank = $row["Rnk"];

$ratings = [];
$query = "SELECT Rating FROM $tbname WHERE Section = '$section'";
$res = $mysqli->query($query);
while ($row = $res->fetch_assoc()) {
    array_push($ratings, $row["Rating"]);
}
//piling
$sectionObj->ratings = $ratings;

$analysis->section = $sectionObj;

//Delivering data in JSON formatt
$json = new stdClass();
$json->analysis = $analysis;
echo json_encode($json);
