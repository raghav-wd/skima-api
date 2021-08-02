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
    // curl_setopt($login, CURLOPT_COOKIEJAR, "cookie.txt");
    // curl_setopt($login, CURLOPT_COOKIEFILE, "cookie.txt");
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
// $cookieKey = "_iamadt_client_10002227248=c8081d266b5fa43863a292e4042802e2cfe2541d0b33f07414c555b315071a6618aba6463d69edea894f5f56b8407caeee81b9e5891741d67bc39a872d0ce04b;zccpn=bts;_iambdt_client_10002227248=d53c19d60e26edd17771c913c78bdd65ba3099f73b923a827462fbe3fafd08c59c19f6b42bfce33c629d03f0f33c45a85dcfbd1aa83b69d43c4f9ec5b6377e9a";

if(true){
    $loginURL = "https://academia.srmist.edu.in/liveViewHeader.do";
    $data = "sharedBy=srm_university&appLinkName=academia-academic-services&zccpn=bts&viewLinkName=My_Attendance&&urlParams=%7B%7D&isPageLoad=true";
    $headers = array("Cookie: ".$cookieKey);

    $html = login($loginURL, $data, $headers);
    $html = preg_replace_callback('/\\\\x([0-9A-F]{1,2})/i', function ($m) {
        return chr(hexdec($m[1]));
    }, $html);
    $html = substr($html, strpos($html, '<div class="mainDiv">'));
    $html = str_get_html($html);

    //Scraping the Academic Status Table from #My_Attendance page
    $json = [];
    if(!isset($html->find('tbody')[0]))
    {
        echo '{"error":"cookie expired"}';
        exit(0);
    }
    $academic_status_html = $html->find('tbody')[0];
    foreach($academic_status_html->find('tr') as $ele)
    {
        //Check if the string contains more than 1 \:
        if(preg_match_all('/:/i', $ele) > 1)
            {
                foreach(preg_split('/(\w+:\d+)/', $ele->plaintext, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $child)
                    {
                        $arr = explode(':', $child);
                        $obj = new stdClass();
                        $obj->{$arr[0]} = $arr[1];
                        array_push($json, $obj);
                    }
            }
        else
        {
            $arr = explode(':', $ele->plaintext);
            $obj = new stdClass();
            $obj->{$arr[0]} = $arr[1];
            array_push($json, $obj);
        }
    }
    //Saving the data scraped in response and then parsing it in json
    $response = [];
    $obj = new stdClass();
    $obj->{"Academic Status"} = $json;
    array_push($response, $obj);

    //Scraping the Attendance Table from #My_Attendance page
    $attendance = $html->find("table[bgcolor]")[0];
    $counter = 0;
    $json = [];
    $table_params = [];
    foreach($attendance->find("tr") as $tr)
    {
        $obj = new stdClass();
        $i = 0;
        foreach($tr->find("td") as $td){
            if($counter == 0){
                array_push($table_params, strip_tags($td->plaintext));
            }
            else{
                $obj->{$table_params[$i++]} = strip_tags($td->plaintext);
            }
        }
        if($counter == 1)
            array_push($json, $obj);
        $counter = 1;
    }
    $obj = new stdClass();
    $obj->Attendance = $json;
    array_push($response, $obj);

    echo json_encode($response);
} else{
    echo '{"error":"Account doesn\'t exit"}';
} 
