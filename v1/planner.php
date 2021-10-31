<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include "./parser/simple_html_dom.php";

//Receiving content/keys in json format
$creds = json_decode(file_get_contents("php://input")) ?: (object) [];

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

$AcademicPlannerKey = $creds->AcademicPlannerKey;
$cookieKey = $creds->Cookie;

$loginURL = "https://academia.srmist.edu.in/liveViewHeader.do";
$data =
    "sharedBy=srm_university&appLinkName=academia-academic-services&zccpn=bts&viewLinkName=" .
    $AcademicPlannerKey .
    "&&urlParams=%7B%7D&isPageLoad=true";
$headers = ["Cookie: " . $cookieKey];
$html = login($loginURL, $data, $headers);
$html = preg_replace_callback(
    "/\\\\x([0-9A-F]{1,2})/i",
    function ($m) {
        return chr(hexdec($m[1]));
    },
    $html
);
$html = substr($html, strpos($html, '<div class="mainDiv"'));
$html = str_get_html($html);
// echo $html->find("table")[0];
$obj = new stdClass();

$params = [];
$html = $html->find("table")[0];
foreach ($html->find("th") as $th) {
    array_push(
        $params,
        html_entity_decode(strip_tags($th->plaintext), ENT_QUOTES | ENT_HTML5)
    );
}

$response = [];
$arrMnth = [];
for ($i = 0; $i < count($params); $i++) {
    $obj = new stdClass();
    $array = [];
    if ($params[$i] != "") {
        foreach ($html->find("tr") as $tr) {
            $td = $tr->find("td")[$i];
            array_push($array, $td->plaintext);
        }
        if (preg_match('/\w{3}\s\'\d{2}/', $params[$i])) {
            $obj->Events = $array;
        } else {
            $obj->{$params[$i]} = $array;
        }
        array_push($arrMnth, $obj);
    } else {
        $mnthobj = new stdClass();
        $mnthobj->{$params[$i - 2]} = $arrMnth;
        array_push($response, $mnthobj);
        $arrMnth = [];
    }
}

echo json_encode($response);
