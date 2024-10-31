<?php
if(!class_exists("WP_Http")){
	include_once( ABSPATH . WPINC. '/class-http.php' );
}

function k_readability_main_settings(){
	echo(__("The main settings",LOCALE_DOMAIN));
	$options = get_option('readability_options');
	foreach($options as $key => $val){
		echo "<input type='hidden' name='readability_options[{$key}]' value='{$val}' />";
	}
}

function k_readability_post_settings(){
	echo(__("The post settings",LOCALE_DOMAIN));
	$options = get_option('readability_options');
	foreach($options as $key => $val){
		echo "<input type='hidden' name='readability_options[{$key}]' value='{$val}' />";
	}
}

function k_readability_title(){
	$options = get_option('readability_options');
	if(!array_key_exists('title', $options) || empty($options['title'])){
		$options['title'] = __('Links from ', LOCALE_DOMAIN)."###start###".__(" to ",LOCALE_DOMAIN)."###end###";
	}
	?>
	<input style="width:100%;" id="k_readability_title" name="readability_options[title]" class="form-input-tip" value="<?php echo (isset($options['title']))?$options['title']: ""; ?>"/>
	<p>You can use the following placeholder to insert dynamic values.<br />
		<em><?php _e("start date:", LOCALE_DOMAIN) ?></em> ###start###<br />
		<em><?php _e("end date:", LOCALE_DOMAIN) ?></em> ###end###</p>
	<p>A date is rendered in following format: <strong><?php echo(date_i18n(get_option('date_format'), time())) ?></strong> <br />
		You can change this at <a href="options-general.php"><em>Settings &gt; General &gt; Date Format</em></a>
	</p>
	<?php
}

function k_readability_pre(){
	$options = get_option('readability_options');
	if(!array_key_exists("pre", $options)){
		$options['pre'] = '';
	}
	$mc_config = array();
	wp_editor($options['pre'], "readability_options[pre]", $mc_settings);
	
}

function k_readability_schedule(){
	$options = get_option('readability_options');
	if(!array_key_exists("schedule", $options)){
		$options['schedule'] = 'weekly';
	}
	?>
	<select id="k_readability_schedule" name="readability_options[schedule]">
		<option <?php if($options['schedule'] == 'daily') echo('selected'); ?> value="daily"><?php _e("daily",LOCALE_DOMAIN); ?></option>
		<option <?php if($options['schedule'] == 'weekly') echo('selected'); ?> value="weekly"><?php _e("weekly",LOCALE_DOMAIN); ?></option>
		<option <?php if($options['schedule'] == 'manually') echo('selected'); ?> value="manually"><?php _e("manually",LOCALE_DOMAIN); ?></option>
	</select>
	<?php 
	if(!array_key_exists("schedule_manually", $options)){
		$options['schedule_manually'] = 'schedule_manually';
	}
	if($options['schedule_manually'] == 'schedule_manually'){
		$checked = "checked";
	}else {
		$checked = "";
	}
	?>
	<p><input type="checkbox" name="readability_options[schedule_manually]" value="schedule_manually" <?php echo($checked); ?>/> Allow manual execution even with not manual schedule</p>
	<p><?php _e("To trigger the plugin manually use the following Link (e.g. external cronjob):",LOCALE_DOMAIN); ?></p>
	<p><?php echo "<a href='".plugins_url( 'readability_favorites.php' , __FILE__ )."'>".plugins_url( 'readability_favorites.php' , __FILE__ )."</a>"; ?></p>
	<?php
}

function k_readability_category() {
	$options = get_option('readability_options');
	$cats = get_categories();
	if(!array_key_exists("category", $options)){
		$options['category'] = 1; //Default: Category ID 1
	}
	?>
	<select id="k_readability_category" name="readability_options[category]">
	<?php
		foreach ($cats as $cat) {
			echo "<option value='{$cat->cat_ID}'";
			if($options['category'] == $cat->cat_ID){
				echo 'selected';
			}
			echo ">{$cat->name}</option>";
		}
	?>
	</select>
	<?php
}

function k_readability_status() {
	//'draft' | 'publish' | 'pending'
	$options = get_option('readability_options');
	if(!array_key_exists("status", $options)){
		$options['status'] = 'draft'; //default: Draft
	}
	?>
	<select id="k_readability_status" name="readability_options[status]">
		<option <?php if($options['status'] == 'draft') echo('selected'); ?>>draft</option>
		<option <?php if($options['status'] == 'publish') echo('selected'); ?>>publish</option>
		<option <?php if($options['status'] == 'pending') echo('selected'); ?>>pending</option>
	</select>
	<?php
}

function k_readability_hint() {
	$options = get_option('readability_options');
	if(!array_key_exists("hint", $options)){
		$options['hint'] = 'hint';
	}
	if($options['hint'] == 'hint'){
		$checked = "checked";
	}else {
		$checked = "";
	}
	?>
	<input type="checkbox" name="readability_options[hint]" value="hint" <?php echo($checked); ?>/>
	<?php 
}

function k_readability_blank() {
	$options = get_option('readability_options');
	if(!array_key_exists("blank", $options)){
		$options['blank'] = 'none'; //default: none
	}
	?>
	<input type="checkbox" name="readability_options[blank]" value="_blank" <?php if($options['blank'] == "_blank"){ echo "checked";} ?>/>
	<?php 
}

function k_readability_connect() {
	$options = get_option('readability_options');
	$consumer = new OAuthConsumer(consumer_key, consumer_secret, NULL);
	$endpoint = "https://www.readability.com/api/rest/v1/oauth/authorize/";
	$req = OAuthRequest::from_consumer_and_token($consumer, NULL, "GET",$endpoint, array("oauth_callback" => "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));
	$req->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL);
		
	error_log("oauth");
	if(isset($_GET['oauth_callback_confirmed']) && $_GET['oauth_callback_confirmed'] && (!isset($options['oauth_token']) || !$options['oauth_token'])){
		
		//Authorization was good
		$consumer = new OAuthConsumer(consumer_key, consumer_secret, NULL);
		$endpoint = "https://www.readability.com/api/rest/v1/oauth/request_token/";
		$req = OAuthRequest::from_consumer_and_token($consumer, NULL, "GET",$endpoint, $_REQUEST);
		$req->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL);
		$request = new WP_Http();
		$result = $request->request($req->to_url());
		$parsed_result = array();
		$tmp = explode("&",$result['body']);
		foreach($tmp as $part){
			$tmp2 = explode("=", $part);
			if(count($tmp2) == 2){
				$parsed_result[$tmp2[0]] = $tmp2[1];	
			}
		}
		$token2 = new OAuthToken($parsed_result['oauth_token'], $parsed_result['oauth_token_secret']);
		$endpoint2 = "https://www.readability.com/api/rest/v1/oauth/access_token/";
		$req = OAuthRequest::from_consumer_and_token($consumer, $token2, "GET",$endpoint2, array('oauth_verifier' => $parsed_result['oauth_verifier']));
		$req->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, $token2);
		$request = new WP_Http();
		$result = $request->request($req->to_url());
		$parsed_result = array();
		$tmp = explode("&",$result['body']);
		foreach($tmp as $part){
			$tmp2 = explode("=", $part);
			if(count($tmp2) == 2){
				$parsed_result[$tmp2[0]] = $tmp2[1];	
			}
		}
		$options['oauth_token'] = $parsed_result['oauth_token'];
		$options['oauth_token_secret'] = $parsed_result['oauth_token_secret'];
		update_option('readability_options', $options);
		//Get current user and cache it
		$user = k_readability_get_user();
		update_option('readability_user', $user);
	}
	
	if(!isset($options['oauth_token']) || $options['oauth_token'] == false){ 
	echo '<a href="'.$req.'" title="Authorize Plugin with readability">'.__("Authorize with Readability", LOCALE_DOMAIN).'</a>';
	}else{ 
	 echo __('Authorized as ',LOCALE_DOMAIN);
	 $user = get_option('readability_user');
	 
	 echo ($user)? $user->username." ": "";
	 echo "<a href='".plugins_url( 'readability_favorites_action.php' , __FILE__ )."?k_action=deauthorize'>Deauthorize</a>";
	 echo "<input type='hidden' value='{$options['oauth_token']}' name='readability_options[oauth_token]' />";
	 echo "<input type='hidden' value='{$options['oauth_token_secret']}' name='readability_options[oauth_token_secret]' />";
	}
	
}

function k_readability_featured_image() {
	$options = get_option('readability_options');
	if(!array_key_exists('featured_image', $options) || empty($options['featured_image'])){
		$style = "style='display:none;'";
	}
	?>
	<script type="text/javascript">
	jQuery(document).ready(function() {
	
	jQuery('#upload_image_button').click(function() {
	 formfield = jQuery('#upload_image').attr('name');
	 tb_show('', 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
	 return false;
	});
	
	window.send_to_editor = function(html) {
	console.log(html);
	 imgurl = jQuery('img',html).attr('src');
	 jQuery('#upload_image').val(imgurl);
	 jQuery('#k_readability_featured_image').attr('src', imgurl);
	 jQuery('#k_readability_featured_image').css('display', 'inline');
	 tb_remove();
	}
	
	});
	</script>
	<p><img <?php echo $style; ?> src="<?php echo (isset($options['featured_image']))?$options['featured_image']: ""; ?>" alt="featured_image" id="k_readability_featured_image"/></p>
	<input id="upload_image" type="text" size="36" name="readability_options[featured_image]" value="<?php echo (isset($options['featured_image']))?$options['featured_image']: ""; ?>" />
	<input id="upload_image_button" type="button" value="Upload Image" />
	<br /><?php _e("Enter an URL or upload an image for the banner.", LOCALE_DOMAIN); ?>
	
	<?php 
}

function k_readability_preview(){
	$options = get_option('readability_options');
	$last = get_option('readability_last_executed', false);
	$start = $last;
	if($last == false){
		$start = mktime(0,0,0,0,0,0);
	}
	
	$end = time();
	$error = false;
	try{
		$results = k_get_posts_from_readability($start, $end);
	}catch (exception $e) {
		$error = true;
		$message = "";
		switch($e->getCode()){
			case 401:
				$message = "Authentication failed or was not provided. Verify that you have sent valid credentials.";
				break;
			case 404:
				$message = "The resource that you requested does not exist.";
				break;
			case 500:
				$message = "An unknown error has occurred.";
				break;
			case 400:
				$message = "The server could not understand your request. Verify that request parameters (and content, if any) are valid.";
				break;
			case 409:
				$message = "The resource that you are trying to create already exists. This should also provide a Location header to the resource in question.";
				break;
			case 403:
				$message = "The server understood your request and verified your credentials, but you are not allowed to perform the requested action.";
				break;
		}
	}
	if($error){
		echo($message);
		return;
	}
	echo "<h2>".__("Preview", LOCALE_DOMAIN)."</h2>";
	if($options['schedule'] != 'manually'){
		echo "<p>"."This post will be published at"." <strong>".date_i18n(get_option('date_format')." ".get_option('time_forma'),wp_next_scheduled('k_readability_favorite_hook'))."</strong></p>";
	}else{
		echo "<p>"._("Post schedule is manually", LOCALE_DOMAIN)."</p>";
	}
	echo "<div id='k_readability_preview' >"; //style='margin:0 10px;border:1px solid #585858; padding:10px;'
	echo "<h3>".k_readability_generate_title($start, $end)."</h3>";
	if(isset($options['featured_image'])){
		echo "<img src='{$options['featured_image']}' />";
	}
	echo k_readability_generate_post($results);
	echo "</div>";
}
?>