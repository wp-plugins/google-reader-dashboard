<?php
/**
  * This is a core PHP class for reading data from and parsing information to
  * the 'unofficial' Google Reader API.
  *
  * Copyright 2010  Eric Mann  (email : eric@eamann.com)
  *
  * This program is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License, version 2, as 
  * published by the Free Software Foundation.
  *
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  */

class JDMReader {
	private $_username;
	private $_password;
	private $_sid;

	private $_token;
	private $_cookie;
	
	public $loaded;

	public function __construct($username, $password) {
		if($this->_validateUser($username, $password)) {
			$this->_username = $username;
			$this->_password = $password;
			$this->loaded = true;
			$this->_connect();
		} else {
			$this->loaded = false;
		}
	}

	private function _connect() {
		$this->_getToken();
		return $this->_token != null;
	}
    
	private function _getToken() {
		$this->_getSID();
		$this->_cookie = "SID=" . $this->_sid . "; domain=.google.com; path=/";

		$url = "http://www.google.com/reader/api/0/token";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_COOKIE, $this->_cookie);
		curl_setopt($ch, CURLOPT_URL, $url);

		ob_start();

		curl_exec($ch);
		curl_close($ch);

		$this->_token = ob_get_contents();
		ob_end_clean();
	}

	private function _getSID() {
		$requestUrl = "https://www.google.com/accounts/ClientLogin?service=reader&Email=" . urlencode($this->_username) . '&Passwd=' . urlencode($this->_password);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $requestUrl);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		ob_start();

		curl_exec($ch);
		curl_close($ch);
		$data = ob_get_contents();
		ob_end_clean();

		$sidIndex = strpos($data, "SID=")+4;
		$lsidIndex = strpos($data, "LSID=")-5;

		$this->_sid = substr($data, $sidIndex, $lsidIndex);
	}
	
	private function _validateUser($user, $pass) {
		$requestUrl = "https://www.google.com/accounts/ClientLogin?service=reader&Email=" . urlencode($user) . '&Passwd=' . urlencode($pass);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $requestUrl);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		ob_start();

		curl_exec($ch);
		curl_close($ch);
		$data = ob_get_contents();
		ob_end_clean();
		if(trim($data) == 'Error=BadAuthentication') {
			return false;
		} else {
			return true;
		}
	}
	
	private function _httpGet($requestUrl, $getArgs) {
		$url = sprintf('%1$s?%2$s', $requestUrl, $getArgs);
		$https = strpos($requestUrl, "https://");
        
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if($https === true) curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt($ch, CURLOPT_COOKIE, $this->_cookie);

		ob_start();
        
		try {
			curl_exec($ch);
			curl_close($ch);
			$data = ob_get_contents();
			ob_end_clean();
		} catch(Exception $err) {
			$data = null;
		}
		return $data;       
	}
	
	private function _httpPost($requestUrl, $fields) {
		$https = strpos($requestUrl, "https://");
		
		foreach($fields as $key=>$value) { 
			$fields_string .= $key.'='.$value.'&'; 
		}
		rtrim($fields_string,'&');
		
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$requestUrl);
		curl_setopt($ch,CURLOPT_POST,count($fields));
		curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
		if($https === true) curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt($ch, CURLOPT_COOKIE, $this->_cookie);
		
		try {
			$result = curl_exec($ch);
			curl_close($ch);
			return $result;
		} catch(Exception $err) {
			return null;
		}
	}

	
	/* Public Methods */
	
	// List all subscriptions
	public function listAll() {
		$gUrl = "http://www.google.com/reader/api/0/stream/contents/user/-/state/com.google/reading-list";
		$args = sprintf('ck=%1$s', time());

		return $this->_httpGet($gUrl, $args);
	}
	
	// List a particular number of unread posts from the user's reading list
	public function listUnread($limit) {
		$out = '<ul>';
		$gUrl = 'http://www.google.com/reader/api/0/stream/contents/user/-/state/com.google/reading-list';
		$args = sprintf('ot=%1$s&r=n&xt=user/-/state/com.google/read&n=%2$s&ck=%3$s&client=GoogleReaderDashboard', time() - (7*24*3600), $limit, time());
		
		$data = $this->_httpGet($gUrl, $args);
		
		$decoded_data = json_decode($data, true);
		$feed_items = $decoded_data['items'];
		foreach($feed_items as $article) {
			$out .= "<li>";
			$out .= '<a class="rsswidget grdLink" href="' . $article['alternate'][0]['href'] . '" target="_blank">';
			$out .= '<span class="grd_title">' . $article['title'] . '</span>';
			$out .= '</a>';
			$out .= '<span class="rss-date">' . date('M j, Y', $article['published']) . '</span>';
			$out .= '<div class="rss-summary">';
			if(isset($article['summary']['content']))
				$out .= '<span class="grd_summary">' . $article['summary']['content'] . '</span>';
			if(isset($article['content']['content'])) {
				$splitdata = split('</p>', $article['content']['content']);
				$out .= '<span class="grd_content">' . $splitdata[0] . '[&#x2026;]</p></span>';			
			}
			$out .= "</div>";
			$out .= "</li>";
		}
		$out .= "</ul>";
		return $out;
	}
	
	// Add new subscription
	public function addFeed($feedUrl) {
		$data = sprintf('quickadd=%1$s&T=%2$s', $feedUrl, $this->_token);
		$url = 'http://www.google.com/reader/api/0/subscription/quickadd?client=scroll';
		
		$response = $this->_httpPost($url, $data);
		if($response == null) return false;
		return true;
	}
	
	public function addLabelToFeed($label, $feedUrl) {
		$data = sprintf('a=user/-/label/%1$s&s=feed/%2$s&ac=edit&T=%3$s', $label, $feedUrl, $this->_token);
		$url = 'http://www.google.com/reader/api/0/subscription/edit?client=scroll';
		
		$response = $this->_httpPost($url, $data);
		if($response == null) return false;
		return true;
	}
}
?>