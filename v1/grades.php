<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

//Receiving content/keys in json format
$creds = json_decode(file_get_contents("php://input")) ?: (object) [];

$cookieKey = $creds->Cookie;
// $cookieKey = "_iamadt_client_10002227248=c8081d266b5fa43863a292e4042802e279fe49921ff7a8cebd98643c0ad6edb4cc630f0b497945204fe57640b4aa7579ec0b1b45035d480adddffcb899670748;zccpn=bts;_iambdt_client_10002227248=da21117aae2d654a27e822cbf3724faac831334b968b990e41ae905c253cac36fb38f23bf26b0d6deb43a8634cdf0c6a7eee5a126142b99eb2f66733d36bb04d";

include "./parser/simple_html_dom.php";

//Upload a blank cookie.txt to the same directory as this file with a CHMOD/Permission to 777
function login($url, $data, $header)
{
    $fp = fopen("cookie.txt", "w");
    fclose($fp);
    $headers = $header;
    $login = curl_init();
    curl_setopt($login, CURLOPT_COOKIEJAR, "cookie.txt");
    curl_setopt($login, CURLOPT_COOKIEFILE, "cookie.txt");
    curl_setopt($login, CURLOPT_HEADER, true);
    curl_setopt($login, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($login, CURLOPT_TIMEOUT, 40000);
    curl_setopt($login, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($login, CURLOPT_URL, $url);
    curl_setopt($login, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
    curl_setopt($login, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($login, CURLOPT_POST, true);
    curl_setopt($login, CURLOPT_POSTFIELDS, $data);
    ob_start();
    return curl_exec($login);
    ob_end_clean();
    curl_close($login);
    unset($login);
}

//1
$loginURL = "https://academia.srmist.edu.in/liveViewHeader.do";
$data =
    "sharedBy=srm_university&appLinkName=academia-academic-services&zccpn=bts&viewLinkName=My_Result&&urlParams=%7B%7D&isPageLoad=true";
$headers = ["Cookie: " . $cookieKey];

$html = login($loginURL, $data, $headers);
$html = preg_replace_callback(
    "/\\\\x([0-9A-F]{1,2})/i",
    function ($m) {
        return chr(hexdec($m[1]));
    },
    $html
);
$html = substr($html, strpos($html, '<div class="mainDiv">'));
$html = str_get_html($html);

$c = 0;
$json = [];
$response = [];
$jsonParams = [];
if ($html->find("table") == null) {
    echo '{"error":"grade table not found"}';
    exit(0);
}
foreach ($html->find("table")[1]->find("tr") as $tr) {
    $i = 0;
    $obj = new stdClass();
    foreach ($tr->find("td") as $td) {
        if ($c == 0) {
            array_push($jsonParams, $td->plaintext);
        } else {
            $obj->{$jsonParams[$i++]} = strip_tags($td->plaintext);
            // echo $td->plaintext."<br/>";
        }
    }
    if ($c == 1) {
        array_push($json, $obj);
    }
    $c = 1;
}
$obj = new stdClass();
$obj->Grades = $json;
$response = json_encode($obj);
echo $response;
