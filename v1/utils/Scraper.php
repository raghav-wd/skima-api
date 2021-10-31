<?php
class Scraper
{
    public $url;
    public $data;
    public $header;

    function __construct($url, $data, $header)
    {
        $this->url = $url;
        $this->data = $data;
        $this->header = $header;
    }

    // Upload a blank cookie.txt to the same directory as this file with a CHMOD/Permission to 777
    function login()
    {
        $fp = fopen("cookie.txt", "w");
        fclose($fp);
        $headers = $this->header;
        $login = curl_init();
        curl_setopt($login, CURLOPT_COOKIEJAR, "cookie.txt");
        curl_setopt($login, CURLOPT_COOKIEFILE, "cookie.txt");
        curl_setopt($login, CURLOPT_HEADER, true);
        curl_setopt($login, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($login, CURLOPT_TIMEOUT, 40000);
        curl_setopt($login, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($login, CURLOPT_URL, $this->url);
        curl_setopt($login, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
        curl_setopt($login, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($login, CURLOPT_POST, true);
        curl_setopt($login, CURLOPT_POSTFIELDS, $this->data);
        ob_start();
        return curl_exec($login);
        ob_end_clean();
        curl_close($login);
        unset($login);
    }
}
