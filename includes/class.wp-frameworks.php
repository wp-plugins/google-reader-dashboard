<?php
 if ( ! class_exists('WP_Frameworks') ) :
 
function wp_register_framework( $handle, $src, $deps = array(), $ver = false ) {
	global $wp_frameworks;
	if( ! is_a($wp_frameworks, 'WP_Frameworks') )
		$wp_frameworks = new WP_Frameworks();

	$wp_frameworks->add( $handle, $src, $deps, $ver );
}

function wp_deregister_framework( $handle ) {
	global $wp_frameworks;
	if ( !is_a($wp_frameworks, 'WP_Frameworks') )
		$wp_frameworks = new WP_Frameworks();

	$wp_frameworks->remove( $handle );
}

function wp_load_framework( $handle, $src = false, $deps = array(), $ver = false ) {
	global $wp_frameworks;
	if ( !is_a($wp_frameworks, 'WP_Frameworks') )
		$wp_frameworks = new WP_Frameworks();

	if ( $src ) {
		$wp_frameworks->add( $handle, $src, $deps, $ver );
	}
	$wp_frameworks->load( $handle );
}
	
/**
 * Framework dependency loader
 * 
 * @uses WP_Dependencies
 */ 
class WP_Frameworks extends WP_Dependencies {

	function __construct() {
		do_action_ref_array( 'wp_default_frameworks', array(&$this) );
	}
	
	function all_deps( $handles, $recursion = false, $group = false ) {
		$r = parent::all_deps( $handles, $recursion );
		if ( ! $recursion )
			$this->to_do = apply_filters( 'print_frameworks_array', $this->to_do );
		return $r;
	}
	
	function load( $handle ) {
		if( empty( $this->registered[$handle] ) )
			return false;
			
		if( in_array( $handle, $this->done ) )
			return false;

		$lib_url = $this->registered[$handle]->src;
		
		$this->done[] = $handle;

		include_once($lib_url);
	}
	
	/**
	 * Adds item
	 *
	 * Adds the framework only if no higher version of the same framework already exists
	 *
	 * @param string $handle Framework name
	 * @param string $src Framework url
	 * @param array $deps (optional) Array of framework names on which this framework depends
	 * @param string $ver (optional) Framework version
	 */
	function add( $handle, $src, $deps = array(), $ver = false ) {
		if ( isset($this->registered[$handle]) ) {
			$reg = $this->registered[$handle];
			if( $ver && version_compare($reg->ver, $ver, '>=' ) )
				return false;
		}
		$this->registered[$handle] = new _WP_Dependency( $handle, $src, $deps, $ver, null );
		return true;
	}
}
endif;