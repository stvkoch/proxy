<?php
/**
* @filename: proxy.php
*
* Proxy transparent cross-domain, what your receive you send back to server by proxy.
*
* !Important insert distinct prefix Of Cookie on whitelist
*
* 	@example
*		http://mydomain.com/proxy.php?http://helloworld.com?foo=bar&bar=bar&myusername=proxyuser
*
* @autor Steven Koch<stvkoch@gmail.com>
*/


/*
* Permission url (whitelist)
*    Only urls that match with list are permitted!
*    - try short prefixOfCookie, how:
* @example: $whiteListURLs = array('_iPxFb'=>'http://facebook.com','_iPxGm'=>'http://mygame.com',etc..);
*/
//If has more one url for proxy
$whiteListURLs = array(
	'prefixOfCookieThisDomain'=>'localhost',
	'_i9'=>'inov.es',
);



//----NO MORE EDITABLE----
//check permission, allow only hosts that match
foreach($whiteListURLs as $prefix=>$checkUrl)
{
	if(strpos(urldecode($_SERVER['QUERY_STRING']), $checkUrl)!==false)
	{
		define( '_PREFIX_COOKIE', $prefix );
		define( '_URL', urldecode($_SERVER['QUERY_STRING']) );
		break;
	}
}
//check if white list passed!
if(!defined('_URL')){
	echo "erro";
	exit(1);
}

//make resource
$ch =  curl_init();
curl_setopt($ch, CURLOPT_URL, _URL);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, getHeadersFromServer(true) );//get cookies received by proxy and sent back to server!
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

//get contents
$response = curl_exec( $ch );
list($response_headers, $response_body) = explode("\r\n\r\n", $response, 2);

//sent your header
$headers = explode("\n", $response_headers);
//for each cookie received save on browser
foreach($headers as $hdr){
	if( preg_match('@^Set-Cookie:\s+([^;]+)=([^;]*)(;\s*expires=([^;]*))?@i', $hdr, $matches) ){
		setcookie(_PREFIX_COOKIE.$matches[1], trim($matches[2]), isset($matches[4])? strtotime(trim($matches[4])): 0, '/');//no have path restritions
	}else{
		//@disabled: not receive any other type http header
		//header($hdr); 
	}
}
//sent to request body
echo $response_body;
exit(0);

//---
function getHeadersFromServer($onlyCookie=false)
{
    $headers = array();
	if($onlyCookie){
		$cookies = array();
		foreach ($_COOKIE as $key => $value) {
			if(strpos($key, _PREFIX_COOKIE)===0){
				$cookies[] = str_replace(_PREFIX_COOKIE, '', $key).'='.$value.';';
			}
		}
		$headers[] = 'Cookie: '. implode(' ', $cookies);
	}else{
	    foreach ($_SERVER as $k => $v)
	    {
			if (substr($k, 0, 5) == "HTTP_")
		    {
		        $k = str_replace('_', ' ', substr($k, 5));
		        $k = str_replace(' ', '-', ucwords(strtolower($k)));
				if($k=='Cookie')
        			$headers[] = 'Cookie: '.preg_replace('@'._PREFIX_COOKIE.'(.+=)?@', '${1}', $v);
				else
		        	$headers[] = $k.': '.$v;
		    }
		}
    }
    return $headers;
}