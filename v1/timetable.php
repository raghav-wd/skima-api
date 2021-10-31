<?php
error_reporting(E_ERROR);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include './parser/simple_html_dom.php';
include './utils/Scraper.php';

//Receiving content/keys in json format
$creds = json_decode(file_get_contents("php://input")) ?: (object) array();

$cookieKey = $creds->Cookie;
$timetableKey = $creds->TimeTableKey;

 
//1
$loginURL = "https://academia.srmist.edu.in/liveViewHeader.do";
$data = "sharedBy=srm_university&appLinkName=academia-academic-services&zccpn=bts&viewLinkName=".$timetableKey."&&urlParams=%7B%7D&isPageLoad=true";
$headers = array("Cookie: ".$cookieKey);

$scraper = new Scraper($loginURL, $data, $headers)
$html = $scraper->login();

$html = preg_replace_callback('/\\\\x([0-9A-F]{1,2})/i', function ($m) {
    return chr(hexdec($m[1]));
}, $html);
$html = substr($html, strpos($html, '<div class="mainDiv">'));
$html = str_get_html($html);

$response = [];
$Student_Details = [];
$c = 0;
foreach($html->find('table')[0]->find('tr') as $tr){
    $i = 0;
    $key = "";
    foreach($tr->find('td') as $td){
        if($i==0){
            $key = $td->plaintext;
            $i++;
        } else {
            $obj = new stdClass();
            $obj->{$key} = $td->plaintext;
            $i--;
            array_push($Student_Details, $obj);
            // echo json_encode($obj)."<br/>";
        }
    }
}
$obj = new stdClass();
$obj->Student_Details = $Student_Details;
array_push($response, $obj);
$c = 0;
$json = [];
$jsonParams = [];
if($html->find('table') == null){
    echo '{"error":"timetable table not found"}';
    exit(0);
}
// echo $html->find('table')[1];
$html = $html->find('table')[1];
$html = repair($html);
$html = str_get_html($html);

// echo $html;
foreach($html->find('tr') as $tr){
    $i = 0;
    $obj = new stdClass();
    foreach($tr->find('td') as $td){
        if($c==0){
            array_push($jsonParams, $td->plaintext);
        } else {
            // echo $td."<br/>";
            $obj->{$jsonParams[$i++]} = strip_tags($td->plaintext);
        }
    }
    if($c==1)array_push($json, $obj);
    $c=1;
}
$obj = new stdClass();
$obj->TimeTable = $json;
array_push($response, $obj);
$response = json_encode($response);
echo $response;

function repair($content)
 {
     
    $content = preg_replace('/<\/tr>/i', '</tr><tr>', $content);
    return $content;
 }