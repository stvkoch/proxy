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

try {

	$proxy = new Proxy(array( 'url'=>$_SERVER['QUERY_STRING'], 'whiteList'=>$whiteListURLs, 'sendCookies'=>true )  );

	if($proxy->getContentFromServer())
	{
		$proxy->saveLocalCookies( $proxy->getHeaders() );
		echo $proxy->getContent();
	}
	else
	{
		throw new Exception("Not content from server", 1);
	}

}
catch (Exception $e) 
{
	echo $e->getMessage();

	header('HTTP/1.0 502 Bad Gateway;');
	exit(1);
}


/**
* 
*/
class Proxy
{
	private $_whiteListURLs = array();
	private $_sendCookies = false;

	private $_url = '';
	private $_prefixCookie = '';

	private $_response_body = '';
	private $_response_headers = array();


	function __construct( array $opts )
	{
		if(isset($opts['whiteList'])) $this->_whiteListURLs = $opts['whiteList'];
		if(isset($opts['sendCookies'])) $this->_sendCookies = $opts['sendCookies'];
		$this->_checkUrl( $opts['url'] );
	}


	public function getContentFromServer()
	{
		//make resource
		$ch =  curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->_url );
		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		if($this->_sendCookies)
		{
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->_getHeadersFromServer(true) );//get cookies received by proxy and sent back to server!
		}
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLINFO_HEADER_OUT, 1 );

		$response = curl_exec( $ch );
		list( $response_headers, $this->_response_body ) = explode( "\r\n\r\n", $response, 2 );

		$this->_response_headers = explode("\n", $response_headers);
		//$this->_saveCookiesFromHeaders( $this->_response_headers );

		return !!($response);
	}

	public function getContent()
	{
		return $this->_response_body;
	}

	public function getHeaders()
	{
		return $this->_response_headers;
	}

	public function saveLocalCookies( array $headers )
	{
		//for each cookie received save on browser
		foreach($headers as $hdr){
			if( preg_match('@^Set-Cookie:\s+([^;]+)=([^;]*)(;\s*expires=([^;]*))?@i', $hdr, $matches) ){
				setcookie($this->_prefixCookie.$matches[1], trim($matches[2]), isset($matches[4])? strtotime(trim($matches[4])): 0, '/');//no have path restritions
			}else{
				//@disabled: not receive any other type http header
				//header($hdr); 
			}
		}
	}




	private function _checkUrl($url)
	{
		foreach($this->_whiteListURLs as $prefix => $urlAllowed)
		{
			if(strpos(urldecode($url), $urlAllowed)!==false)
			{
				$this->_prefixCookie = $prefix;
				$this->_url = $url;
				return true;
			}
		}
		throw new Exception("Not allow proxy for this url", 2);
	}


	private function _getHeadersFromServer($onlyCookie=false)
	{
	    $headers = array();
		if($onlyCookie){
			$cookies = array();
			foreach ($_COOKIE as $key => $value) {
				if(strpos($key, $this->_prefixCookie)===0){
					$cookies[] = str_replace($this->_prefixCookie, '', $key).'='.$value.';';
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
	        			$headers[] = 'Cookie: '.preg_replace('@'.$this->_prefixCookie.'(.+=)?@', '${1}', $v);
					else
			        	$headers[] = $k.': '.$v;
			    }
			}
	    }
	    return $headers;
	}
}