<?php
/** 
  * This class is the core of the Google Reader Dashboard plug-in.
  * Instantiating this class loads the plug-in, reports success to
  * the plug-in author via Elliot RPC v1, and adds a widget to the
  * WordPress admin dashboard.
  *
  * To log in with your Google account, merely enter your account
  * information in the wdiget's configuration setting.
  *
  * Login information will be stored in the WordPress options table
  * in a serialized array.  Anyone with access to your WordPress
  * database will potentially be able to see your plaintext Google
  * login name and password if you check the "remember me" box!
  *
  */

require_once(ABSPATH . WPINC . '/pluggable.php');

if(!class_exists('GRDplugin')) :
class GRDplugin {	
	private $reader;
	private $_user;
	private $_gUser;
	private $_gPass;
	private $_readerLoaded;
	private $_loggedIn;

	// Constructor - creates plugin framework, loads data, launches
	// additional required modules.
	public function __construct() {
		global $current_user;
		global $user_ID;
		get_currentuserinfo();
		if(!'' == $user_ID) {
			$this->user = $current_user->user_login;
			$this->_loggedIn = $this->_get_google_login($this->user);
		}
	}
	
	/* Private Methods */
	
	private function _get_google_login($username) {
		if(isset($_COOKIE['grd_cookie'])) {
			$data = stripslashes($_COOKIE['grd_cookie']);
			$data = json_decode($data, true);
			if($data['uLogin'] == $username) {
				$this->_gUser = $data['user'];
				$this->_gPass = $data['pass'];
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	private function _load_reader($user, $password) {
		wp_load_framework( 'greader' );

		$this->reader = new JDMReader($user, $password);
		$this->_readerLoaded = $this->reader->loaded;
	}
	
	private function _googleLoginForm() {
		$form = '<div class="inside">';
		$form .=  '<p class="center">Log in with your Google credentials:</p>';
		$form .= '<form id="grd_login">';
		$form .= '<h4><label for="gUser">Username</label></h4><div class="input-text-wrap"><input type="text" id="gUser" name="gUser" autocomplete="off" /></div>';
		$form .= '<h4><label for="gPassword">Password</label></h4><div class="input-text-wrap"><input type="password" id="gPassword" name="gPassword" autocomplete="off" /></div>';
		$form .= '<p class="submit"><input type="submit" value="Log in" class="button-primary" id="login" /><br class="clear"></p>';
		$form .= '</form>';
		$form .= '</div>';
		return $form;
	}
	
	/* Public Methods */
	
	// Hook the widget into WordPress - first function to fire!!!
	public function load() {	
		if(!$this->_loggedIn) {
			add_action('admin_head', array(&$this,'login_javascript'));
			add_action('wp_ajax_grd_login', array(&$this,'grd_login_callback'));
		}
		add_action('admin_head', array(&$this, 'admin_style'));
		add_action('wp_dashboard_setup', array(&$this, 'call_widget'));
	}

	// Add the actual widget to the dashboard
	public function call_widget() {	
		wp_add_dashboard_widget('google_reader', 'Google Reader', array(&$this, 'widget'));
	}	
	
	// Render the widget
	public function widget() {
		global $current_user;
		get_currentuserinfo();
		if($this->_loggedIn) {
			$this->_load_reader($this->_gUser, $this->_gPass);
			echo '<div class="rss-widget">';
			echo $this->reader->listUnread('5');
			echo '</div>';
		} else {
			echo '<div id="grd_widget" class="rss-widget">';
			echo $this->_googleLoginForm();
			echo '</div>';
		}
	}	
	
	/* Google Reader Login Functions */
	public function login_javascript() {
		?>
		<script type="text/javascript" >
		var data;
		reStart();
		function reStart() {
			jQuery(document).ready(function($) {			
				$('#grd_login').submit(function() {
					data = {
						action: 'grd_login',
						gUser: $('#gUser').val(),
						gPass: $('#gPassword').val()
					};
					$('#grd_widget').html('Loading...');
					jQuery.post(ajaxurl, data, function(response) {
						$('#grd_widget').html(response);
						reStart();
					});
					return false;
				});	
			});
		}
		</script>
		<?php
	}
	
	// Log the user in, create a cookie to store user data, and return the reading list.
	public function grd_login_callback() {
		global $current_user;
		get_currentuserinfo();
		
		$this->_gUser = $_POST['gUser'];
		$this->_gPass = $_POST['gPass'];
		
		$this->_load_reader($this->_gUser, $this->_gPass);
		if($this->_readerLoaded) {
			$cookieData = array( 'uLogin' => $current_user->user_login,
								 'user' => $this->_gUser,
								 'pass' => $this->_gPass);
			setcookie('grd_cookie', json_encode($cookieData), time() + 24*7*3600, '/');

			$data = '<div class="rss-widget">';
			$data .= $this->reader->listUnread('5');
			$data .= '</div>';
		} else {
			$data = '<p class="error">Bad Login</p>';
			$data .= $this->_googleLoginForm();
		}
		echo $data;

		die();
	}
	
	// Add styling for the Google login form
	public function admin_style() {
	?>
	<style type="text/css">
	#google_reader h4 {
		clear: both;
		float: left;
		font-family: 'Lucida Grande', Verdana, Arial, 'Bitstream Vera Sans', sans-serif;
		font-size: 12px;
		font-weight: normal;
		padding-top: 5px;
		text-align: right;
		width: 5.5em;
	}
	#google_reader h4 label {
		margin-right:10px;
	}
	#google_reader .input-text-wrap {
		margin:0px 0px 1em 5em;
	}
	#google_reader form .input-text-wrap input {
		border:0px none;
		color:#333;
		margin:0px;
		outline:none;
		padding:0px;
		width:99%;
	}
	#google_reader form p.submit #login {
		float:right;
	}
	#google_reader p.center {
		text-align:center;
	}
	</style>
	<?php
	}
}
endif;
?>