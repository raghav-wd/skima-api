<?php
define("DOC_ROOT","/path/to/html");

include './parser/simple_html_dom.php';
error_reporting(E_ERROR);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

//Receiving content/keys in json format
$creds = json_decode(file_get_contents("php://input")) ?: (object) array();

$headers = array('Host: academia.srmist.edu.in',
    'Connection: keep-alive',
    'Accept: /',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36',
    'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
    'Origin: https://academia.srmist.edu.in',
    'Sec-Fetch-Site: same-origin',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Dest: empty',
    'Referer: https://academia.srmist.edu.in/accounts/signin?_sh=false&hideidp=true&portal=10002227248&client_portal=true&servicename=ZohoCreator&service_language=en&serviceurl=https%3A%2F%2Facademia.srmist.edu.in%2F',
    'Accept-Encoding: gzip, deflate, br',
    'Accept-Language: en-GB,en-US;q=0.9,en;q=0.8',
    'Cookie: IAM_TEST_COOKIE=IAM_TEST_COOKIE; _ga=GA1.3.2030294163.1577521520; zohocares-_zldp=YfEOFpfOAG%2BTO31vEsSMwERDg2On8kq551Oz1gFS0T0Ej%2BNVmsYM2M97Q24h6vKJK%2FcxiH9c3kQ%3D; zohocares-_siqid=YfEOFpfOAG8vp0ZG9ycmFbCrqS2H59yvRE8cJfRR0I%252BJr7Yz9MxaWFNXmKhvIMXGCkw4Shd4MhyG%250AEoyIscLXgIg8ak6Oi3KUnhAcCMBWGySQS22dhhC44Q%253D%253D; _fbp=fb.2.1590823346845.1366151061; isiframeenabled=true; BetaFeature=1; zohocares-_zldt=78ca59c5-6699-46a2-a21a-8e941e91e619; zccpn=bts; ZCNEWUIPUBLICPORTAL=false; iamcsr=557fccc6-2994-42b1-95ff-f6e3bf7c5d1c; _zcsr_tmp=557fccc6-2994-42b1-95ff-f6e3bf7c5d1c; 74c3a1eecc=9549f720e73a1868e8570541f0fe20cb; bdb5e23bb2=934ab92d71aeb620a38a6323c7e70227; e188bc05fe=8db261d30d9c85a68e92e4f91ec8079a; JSESSIONID=F0C47195AA53A83AD5504B036FB8D7E8'
);
$username = $creds->username;
$password = $creds->password;
// $username = "Rishiraj_ra@srmuniv.edu.in";
// $password = "helloacademia";
// $username = "devanshgupta_ra@srmuniv.edu.in";
// $password = "R@ghavgpt123";
$data = 'username='.$username.'&password='.$password.'&client_portal=true&serviceurl=https%3A%2F%2Facademia.srmist.edu.in%2F&servicename=ZohoCreator&portal=10002227248&service_language=en&is_ajax=true&grant_type=password';

$loginURL = "https://academia.srmist.edu.in/accounts/signin.ac";

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
 
function grab_page($site){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
    curl_setopt($ch, CURLOPT_URL, $site);
    ob_start();
    return curl_exec ($ch);
    ob_end_clean();
    curl_close ($ch);
}
 
function post_data($site,$data, $header){
    $datapost = curl_init();
    $headers = $header;  
    curl_setopt($datapost, CURLOPT_URL, $site);
    curl_setopt($datapost, CURLOPT_TIMEOUT, 40000);
    curl_setopt($datapost, CURLOPT_HEADER, TRUE);
    curl_setopt($datapost, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($datapost, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($datapost, CURLOPT_POST, TRUE);
    curl_setopt($datapost, CURLOPT_POSTFIELDS, $data);
    curl_setopt($datapost, CURLOPT_COOKIEFILE, "cookie.txt");
    ob_start();
    return curl_exec ($datapost);
    ob_end_clean();
    curl_close ($datapost);
    unset($datapost);    
}

$str = login($loginURL, $data, $headers);
preg_match_all("/_\w+=\w+/", $str, $matches);

if(preg_match('/"error":{"password":"Invalid password"}/', $str) == 1){
    echo '{"error":"Incorrect Password"}';
} else {
    if(isset($matches[0][0])){
        $cookieKey = $matches[0][0].';zccpn=bts;'.$matches[0][1];
        $obj = new stdClass();
        $obj->cookie = $cookieKey;
        echo json_encode($obj);
    }
    else {
        echo "{\"error\":\"Account doesn't exit\"}";
    }
}