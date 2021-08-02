<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'simple_html_dom.php';

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

$cookieKey = $creds->Cookie;
$scheduleKey = "Special_Time_Table_".$creds->ScheduleKey;

$loginURL = "https://academia.srmist.edu.in/liveViewHeader.do";
$data = "sharedBy=srm_university&appLinkName=academia-academic-services&zccpn=bts&viewLinkName=".$scheduleKey."&&urlParams=%7B%7D&isPageLoad=true";
$headers = array("Cookie: ".$cookieKey);
$html = login($loginURL, $data, $headers);
 $html = preg_replace_callback('/\\\\x([0-9A-F]{1,2})/i', function ($m) {
        return chr(hexdec($m[1]));
    }, $html);
$html = substr($html, strpos($html, '<div class="mainDiv">'));
$html = str_get_html($html);
// Saving the data scraped in response and then parsing it in json
//Scraping the Attendance Table from #My_Attendance page
$obj = new stdClass();
foreach($html->find('table')[0]->find('tr') as $tr){
    $i = 0;
    foreach($tr->find('td') as $td){
        if($i==0){
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
