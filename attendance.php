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
// $cookieKey = "_iamadt_client_10002227248=c8081d266b5fa43863a292e4042802e279fe49921ff7a8cebd98643c0ad6edb4cc630f0b497945204fe57640b4aa7579ec0b1b45035d480adddffcb899670748;zccpn=bts;_iambdt_client_10002227248=da21117aae2d654a27e822cbf3724faac831334b968b990e41ae905c253cac36fb38f23bf26b0d6deb43a8634cdf0c6a7eee5a126142b99eb2f66733d36bb04d";

if(true){
    $loginURL = "https://academia.srmist.edu.in/liveViewHeader.do";
    $data = "sharedBy=srm_university&appLinkName=academia-academic-services&zccpn=bts&viewLinkName=My_Attendance&&urlParams=%7B%7D&isPageLoad=true";
    $headers = array("Cookie: ".$cookieKey);

    $html = login($loginURL, $data, $headers);
    $html = substr($html, strpos($html, '<div class="mainDiv">'));
    $html = str_get_html($html);

    //Scraping the Academic Status Table from #My_Attendance page
    $json = [];
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
