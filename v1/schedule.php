<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include "./parser/simple_html_dom.php";
include "./utils/Scraper.php";

//Receiving content/keys in json format
$creds = json_decode(file_get_contents("php://input")) ?: (object) [];

$cookieKey = $creds->Cookie;
$scheduleKey = $creds->ScheduleKey . `Batch_` . $creds->Batch;
$scheduleKey = str_replace("BATCH", "Batch", $scheduleKey);

$loginURL = "https://academia.srmist.edu.in/liveViewHeader.do";
$data =
    "sharedBy=srm_university&appLinkName=academia-academic-services&zccpn=bts&viewLinkName=" .
    $scheduleKey .
    "&&urlParams=%7B%7D&isPageLoad=true";
$headers = ["Cookie: " . $cookieKey];
$scraper = new Scraper($loginURL, $data, $headers);
$html = $scraper->login();
// $html = login($loginURL, $data, $headers);

$html = preg_replace_callback(
    "/\\\\x([0-9A-F]{1,2})/i",
    function ($m) {
        return chr(hexdec($m[1]));
    },
    $html
);
$html = substr($html, strpos($html, '<div class="mainDiv">'));
$html = str_get_html($html);

$obj = new stdClass();
foreach ($html->find("table")[1]->find("tr") as $tr) {
    $i = 0;
    foreach ($tr->find("td") as $td) {
        if ($i == 0) {
            $key = $td->plaintext;
            $obj->{$key} = [];
            $i++;
        } else {
            array_push($obj->{$key}, $td->plaintext);
        }
    }
}
$o = new stdClass();
$o->Schedule = $obj;
echo json_encode($o);
