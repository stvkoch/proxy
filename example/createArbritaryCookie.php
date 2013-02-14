<?php

setcookie('cookieNoSend', 'thisToProxy', 0, '/');


echo "<pre>";
var_dump(headers_list());

var_dump($_COOKIE);