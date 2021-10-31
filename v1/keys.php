<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include './parser/simple_html_dom.php';

//Receiving content/keys in json format
$creds = json_decode(file_get_contents("php://input")) ?: (object) array();

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
function getL($url, $headers){
    $login = curl_init();
    curl_setopt($login, CURLOPT_URL, $url);
    curl_setopt($login, CURLOPT_HEADER, TRUE);
    curl_setopt($login, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($login, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($login, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($login, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    return curl_exec ($login);
}
$cookieKey = $creds->Cookie;
$headers = array("Cookie: ".$cookieKey);

$loginURL = "https://academia.srmist.edu.in/liveViewHeader.do";
$data = "sharedBy=srm_university&appLinkName=academia-academic-services&zccpn=bts&viewLinkName=WELCOME&&urlParams=%7B%7D&isPageLoad=true";
$headers = array("Cookie: ".$cookieKey);

$html = login($loginURL, $data, $headers);
$html = preg_replace_callback('/\\\\x([0-9A-F]{1,2})/i', function ($m) {
        return chr(hexdec($m[1]));
    }, $html);
$html = substr($html, strpos($html, '</style>'));
$html = str_get_html($html);

$obj = new stdClass();
$obj->DayOrder = substr(strip_tags($html->find('font')[1]), 10, 1);

$html = getL('https://academia.srmist.edu.in/', $headers);

$html = str_get_html($html);

$obj->isGrades = false;
foreach($html->find('div[elname=zc-menudiv]')[0]->find('a') as $a){
    if(isset($a->complinkname)){
        $a = $a->complinkname;
        if(preg_match('/My_Time_Table/', $a))
        $obj->TimeTableKey = $a;
        else if(preg_match('/Special_Time_Table_/', $a))
        $obj->ScheduleKey = "Special_Time_Table_";
        // else if(preg_match('/Special_Time_Table_/', $a))
        // $obj->ScheduleKey = $a;
        else if(preg_match('/Unified_Timetable/', $a))
        $obj->ScheduleKey = "Unified_Timetable_BATCH_";
        else if(preg_match('/Academic_Plan/', $a))
        $obj->AcademicPlannerKey = $a;
        else if(preg_match('/My_Result/', $a))
        $obj->isGrades = true;
    }
}
$response = new stdClass();
$response->Keys = $obj;
echo json_encode($response);