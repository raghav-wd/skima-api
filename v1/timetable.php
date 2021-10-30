<?php
error_reporting(E_ERROR);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

//Receiving content/keys in json format
$creds = json_decode(file_get_contents("php://input")) ?: (object) array();

$cookieKey = $creds->Cookie;
$timetableKey = $creds->TimeTableKey;

include 'simple_html_dom.php';
 
//Upload a blank cookie.txt to the same directory as this file with a CHMOD/Permission to 777
function login($url,$data, $header){
    $fp = fopen("cookie.txt", "w");
    fclose($fp);
    $headers = $header;
    $login = curl_init();
    curl_setopt($login, CURLOPT_COOKIEJAR, "cookie.txt");
    curl_setopt($login, CURLOPT_COOKIEFILE, "cookie.txt");
    curl_setopt($login, CURLOPT_HEADER, TRUE);
    curl_setopt($login, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($login, CURLOPT_TIMEOUT, 40000);
    curl_setopt($login, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($login, CURLOPT_URL, $url);
    curl_setopt($login, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($login, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($login, CURLOPT_POST, TRUE);
    curl_setopt($login, CURLOPT_POSTFIELDS, $data);
    ob_start();
    return curl_exec ($login);
    ob_end_clean();
    curl_close ($login);
    unset($login);    
}

//1
$loginURL = "https://academia.srmist.edu.in/liveViewHeader.do";
$data = "sharedBy=srm_university&appLinkName=academia-academic-services&zccpn=bts&viewLinkName=".$timetableKey."&&urlParams=%7B%7D&isPageLoad=true";
$headers = array("Cookie: ".$cookieKey);

$html = login($loginURL, $data, $headers);
$html = preg_replace_callback('/\\\\x([0-9A-F]{1,2})/i', function ($m) {
    return chr(hexdec($m[1]));
}, $html);
$html = substr($html, strpos($html, '<div class="mainDiv">'));
$html = str_get_html($html);
echo $html;
// echo $html;
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