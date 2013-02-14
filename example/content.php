<?php

setcookie('cookieName', 1+(int)$_COOKIE['cookieName'], time()+3600, '/');
setcookie('cookieName2', 1+(int)$_COOKIE['cookieName'], 0, '/');
setcookie('cookieName3', 1+(int)$_COOKIE['cookieName'], 0, '/');

echo "<pre>--content--\n";

var_dump($_REQUEST);
echo "---cookies ---\n";
var_dump($_COOKIE);
echo "----raw cookies---\n";
var_dump($_SERVER['HTTP_COOKIE']);
echo "----header list sented ----\n";
var_dump(headers_list());
echo "-------request header------\n";

$headers = array();
    foreach($_SERVER as $key => $value) {
        if (substr($key, 0, 5) <> 'HTTP_') {
            continue;
        }
        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
        $headers[$header] = $value;
    }
    var_dump( $headers );

echo "\n\\-----------------------\n";