<?php
define('WP_USE_THEMES', false);
require('../../../wp-blog-header.php');
if(!is_user_logged_in()){
	die("unauthorized access. dare you!");
}
if(isset($_GET['k_action']) && $_GET['k_action'] == "deauthorize"){
	k_readability_deauthorize();
	wp_redirect(admin_url('options-general.php?page=k_readability'));
}
?>