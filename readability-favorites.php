<?php
/*
Plugin Name: Readability Favorites
Plugin URI: http://kanedo.net/projekte/readability-favorites
Description: Publish a post of your readability favorites
Version: 1.4
Author: Gabriel Bretschner
Author URI: http://blog.kanedo.net
License: GPL2
*/

define('OPTIONSNAME', 'k_readability_options');
define('LOCALE_DOMAIN', 'readability_favorites');
define('PLUGIN_PATH', plugin_dir_url(NULL)."readability-favorites/");
define("READABILITY_DEBUG", false); // Show all errors

if(!class_exists("WP_Http")){
	include_once( ABSPATH . WPINC. '/class-http.php' );
}

include('readability-options.php');
include('readability-settings.php');
include_once 'OAuth.php';
include_once 'o-auth-config.php';

function k_readability_init() {
  load_plugin_textdomain( LOCALE_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) ); 
}
add_action('init', 'k_readability_init');

// Add settings link on plugin page
function k_readability_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=k_readability">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'k_readability_settings_link' );

function k_readability_admin_notice() {
	$options = get_option('readability_options');
	if(!isset($options['oauth_token']) || $options['oauth_token'] == false){
    ?>
    <div class="updated">
        <p><?php _e( 'You need to <a href="options-general.php?page=k_readability">authorize Readability Plugin</a> to work!', 'my-text-domain' ); ?></p>
    </div>
    <?php
	}
}
add_action( 'admin_notices', 'k_readability_admin_notice' );

/*
Function to generate the post
hook: my own hook for schedule
*/
function k_generate_readability_post(){
	$defaults = array(
				'schedule' => "weekly",
				'category' => "1",
				'status' => "publish",
				'hint' => 'hint',
				'oauth_token' => false,
				'oauth_token_secret' => false,
				'featured_image' => false,
				'title' => __('Links from ', LOCALE_DOMAIN)."###start###".__(" to ",LOCALE_DOMAIN)."###end###",
	);
	$options = get_option('readability_options', $defaults);
	$last = get_option('readability_last_executed', false);
	$start = $last;
	if($last == false){
		$start = mktime(0,0,0,0,0,0);
	}
	
	$end = time();
	$results = k_get_posts_from_readability($start, $end);
	$content = k_readability_generate_post($results);
	k_readability_generate_title($start, $end);
	if($content != false){
		$my_post = array(
					'post_title' => k_readability_generate_title($start, $end),
					'post_content' => $content,
					'post_status' => $options['status'],
					'post_author' => 1,
					'post_category' => array($options['category']),
					'post_thumbnail' => $options['featured_image'],
					);
		$the_id = wp_insert_post($my_post, 1);
		if($options['featured_image'] != false && !empty($options['featured_image'])){
			$upload_dir = wp_upload_dir();
			$path = $upload_dir['basedir']."/".str_replace($upload_dir['baseurl'], '', $options['featured_image']);		
			$attachment = array(
			    'guid' => $path, 
			    'post_mime_type' => mime_content_type($path),
			    'post_title' => preg_replace('/\.[^.]+$/', '', basename($options['featured_image'])),
			    'post_content' => '',
			    'post_status' => 'inherit'
			 );
			$attach_id = wp_insert_attachment( $attachment, $path, $the_id );
			add_post_meta($the_id, '_thumbnail_id', $attach_id, true);		
		}
		update_option("readability_last_executed", time());
	}	
}
add_action('k_readability_favorite_hook', 'k_generate_readability_post');

/**
* generate the title
**/
function k_readability_generate_title($start, $end){
	$defaults = array(
				'schedule' => "weekly",
				'category' => "1",
				'status' => "publish",
				'hint' => 'hint',
				'oauth_token' => false,
				'oauth_token_secret' => false,
				'featured_image' => false,
				'title' => __('Links from ', LOCALE_DOMAIN)."###start###".__(" to ",LOCALE_DOMAIN)."###end###",
	);
	
	$options = get_option('readability_options', $defaults);
	if(READABILITY_DEBUG){
		var_dump($defaults);
		var_dump($options);
	}
	$return = str_replace("###start###", date_i18n(get_option('date_format'), $start), $options['title']);
	$return = str_replace("###end###", date_i18n(get_option('date_format'), $end), $return);
	if(READABILITY_DEBUG){
		var_dump($return);
	}
	return $return;
}

/*
get all post from readability
@return array
*/
function k_get_posts_from_readability($start, $end){
	$fstart = date("Y-m-d", $start);
	$fend= date("Y-m-d", $end);
	$defaults = array(
				'schedule' => "weekly",
				'category' => "1",
				'status' => "publish",
				'hint' => 'hint',
				'oauth_token' => false,
				'oauth_token_secret' => false,
	);
	$options = get_option('readability_options', $defaults);
	if(!isset($options['oauth_token']) || $options['oauth_token'] == false){
		return NULL;
	}
	$consumer = new OAuthConsumer(consumer_key, consumer_secret, NULL);
	$token = new OAuthToken($options['oauth_token'], $options['oauth_token_secret']);
	$endpoint = "https://www.readability.com/api/rest/v1/bookmarks/?favorite=1&favorited_since={$fstart}&favorited_until={$fend}";
	$req = OAuthRequest::from_consumer_and_token($consumer, $token, "GET",$endpoint, NULL);
	$req->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, $token);
	$request = new WP_Http();
	$result = $request->request($req->to_url());
	if(READABILITY_DEBUG){
		echo "<pre>";
		var_dump($result);
		echo "</pre>";
	}
	$errors = array(401,404,500,400,409,403);
	if(in_array($result['response']['code'], $errors)){
		throw new exception('an error occured', $result['response']['code']);
	}
	$json = json_decode($result['body']);
	if(READABILITY_DEBUG){
		echo "<pre>";
		var_dump($json);
		echo "</pre>";
	}
	return $json->bookmarks;
}

function k_readability_get_user(){
	$options = get_option('readability_options', $defaults);
	if(!isset($options['oauth_token']) || $options['oauth_token'] == false){
		return NULL;
	}
	$consumer = new OAuthConsumer(consumer_key, consumer_secret, NULL);
	$token = new OAuthToken($options['oauth_token'], $options['oauth_token_secret']);
	$endpoint = "https://www.readability.com/api/rest/v1/users/_current";
	$req = OAuthRequest::from_consumer_and_token($consumer, $token, "GET",$endpoint, NULL);
	$req->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, $token);
	$request = new WP_Http();
	$result = $request->request($req->to_url());
	if(READABILITY_DEBUG){
		echo "<pre>";
		var_dump($result);
		echo "</pre>";
	}
	$errors = array(401,404,500,400,409,403);
	if(in_array($result['response']['code'], $errors)){
		throw new exception('an error occured', $result['response']['code']);
	}
	$json = json_decode($result['body']);
	if(READABILITY_DEBUG){
		echo "<pre>";
		var_dump($json);
		echo "</pre>";
	}
	return $json;
}

/*
generate post content
*/
function k_readability_generate_post($content) {
	if(count($content) == 0){
		if(READABILITY_DEBUG){
			echo "no posts to publish";
		}
		return false;
	}
	$options = get_option("readability_options");
	
	$return = "";
	if(array_key_exists("pre", $options)){
		$return = "<p>";
		$return .= $options['pre'];
		$return .= "</p>";
	}elseif (READABILITY_DEBUG) {
		echo br_trigger_error("array key pre does not exist",0);
	}
	$return .= "<ul>";
	$target = "";
	if(array_key_exists("blank", $options) && $options['blank'] == '_blank'){
		$target = 'target="_blank"';
	}
	foreach ($content as $item) {
		$return .= "<li>";
		$return .= "<a href='{$item->article->url}' {$target}>{$item->article->title}</a>";
		$return .= "<p>{$item->article->excerpt}</p>";
		$return .= "</li>";
	}
	$return .= "</ul>";
	if($options['hint'] == 'hint'){
		$return .= "<p><small>".__("This post was generated with <a href='http://kanedo.net/projekte/readability-favorites/?pk_campaign=Plugin&pk_kwd=Readability%20Favorites'>Readability Favorites</a>", LOCALE_DOMAIN)."</small></p>";
	}
	return $return;
}


/*
on activation register a few settings
*/
function k_readability_register_settings() {
	$defaults = array(
				'schedule' => "weekly",
				'category' => "1",
				'status' => "publish",
				'pre' => "My favorite Readability Links",
				'hint' => 'hint',
				'oauth_token' => false,
				'oauth_token_secret' => false,
				'show_preview' => 'show_preview',
				'featured_image' => false,
				'title' => __('Links from ', LOCALE_DOMAIN)."###start###".__(" to ",LOCALE_DOMAIN)."###end###",
	);
	
	add_option('readability_options',$defaults);
	register_setting(OPTIONSNAME, 'readability_options');
	
	/**
	 * The main section
	 **/
	add_settings_section('k_readability_main', __('Main Settings',LOCALE_DOMAIN), 'k_readability_main_settings', OPTIONSNAME."_main");
	
	
	add_settings_field('k_readability_schedule', __('Schedule',LOCALE_DOMAIN), 'k_readability_schedule', OPTIONSNAME."_main", 'k_readability_main');
	
	add_settings_field('k_readability_category', __('Category',LOCALE_DOMAIN), 'k_readability_category', OPTIONSNAME."_main", 'k_readability_main');
	
	add_settings_field('k_readability_status', __('default Status',LOCALE_DOMAIN), 'k_readability_status', OPTIONSNAME."_main", 'k_readability_main');
		
	add_settings_field('k_readability_connect', __('Connect with Readability',LOCALE_DOMAIN), 'k_readability_connect', OPTIONSNAME."_main", 'k_readability_main');
	/**
	 * The Post section
	 **/
	add_settings_section('k_readability_post', __('Post Settings',LOCALE_DOMAIN), 'k_readability_post_settings', OPTIONSNAME."_post");
	
	add_settings_field('k_readability_title', __('Post title',LOCALE_DOMAIN), 'k_readability_title', OPTIONSNAME."_post", 'k_readability_post');
	
	add_settings_field('k_readability_pre', __('Introduction',LOCALE_DOMAIN), 'k_readability_pre', OPTIONSNAME."_post", 'k_readability_post');
	
	add_settings_field('k_readability_featured_image', __('featured Image',LOCALE_DOMAIN), 'k_readability_featured_image', OPTIONSNAME."_post", 'k_readability_post');
	
	add_settings_field('k_readability_hint', __('Show the plugins name',LOCALE_DOMAIN), 'k_readability_hint', OPTIONSNAME."_post", 'k_readability_post');
	
	add_settings_field('k_readability_blank', __('Open links in a new window',LOCALE_DOMAIN), 'k_readability_blank', OPTIONSNAME."_post", 'k_readability_post');
	
	add_settings_section('k_readability_preview', "", 'k_readability_preview', OPTIONSNAME."_preview");
	
}
add_action('admin_init', 'k_readability_register_settings' );

function k_readability_activation(){
	if(!function_exists("curl_init")){
		br_trigger_error(__("Readability Favorites requires the <a href='http://php.net/curl'>cUrl PHP extension</a> to work",LOCALE_DOMAIN), E_USER_ERROR);
	}
}
register_activation_hook(__FILE__, 'k_readability_activation');


/*
on deactivation: delete the schedule
*/
function k_readability_deactivation(){
	wp_clear_scheduled_hook('k_readability_favorite_hook');
	delete_option("readability_options");
	delete_option("readability_last_executed");
}
register_deactivation_hook(__FILE__, 'k_readability_deactivation');

/**
* deauthorize with readability
*deletes option
**/
function k_readability_deauthorize(){
	$options = get_option('readability_options');
	$options['oauth_token'] = false;
	$options['oauth_token_secret'] = false;
	update_option("readability_options", $options);
	update_option("readability_user", NULL);
	update_option("readability_last_executed", 0);
}

/*
Custom error trigger function
*/
function br_trigger_error($message, $errno) {
 
    if(isset($_GET['action']) && $_GET['action'] == 'error_scrape') {
 
        echo '<strong>' . $message . '</strong>';
 
        exit;
 
    } else {
 
        trigger_error($message);
 
    }
 
}


/*
add weekly as time intervall
@todo change to weekly 604800
*/
function filter_cron_schedules( $param ) {
	return array( 'weekly' => array(
								'interval' => 604800, // seconds
								'display'  => __( 'Once a week' )
							) );
}
add_filter( 'cron_schedules', 'filter_cron_schedules');

/*
schedule the hook
*/
	$options = get_option('readability_options');
	if(is_array($options)){
		if(!array_key_exists("schedule", $options)){
			$options['schedule'] = 'weekly';
		}
		$schedule = wp_get_schedule('k_readability_favorite_hook');
		if($options['schedule'] == 'weekly' && $schedule != 'weekly'){
			wp_clear_scheduled_hook('k_readability_favorite_hook');
			wp_schedule_event( time(), 'weekly', 'k_readability_favorite_hook' );
		}
		
		if($options['schedule'] == 'daily' && $schedule != 'daily'){
			wp_clear_scheduled_hook('k_readability_favorite_hook');
			wp_schedule_event( time(), 'daily', 'k_readability_favorite_hook' );
		}
		if($options['schedule'] == 'manually'){
			wp_clear_scheduled_hook('k_readability_favorite_hook');
		}
	}


?>