<?php
define('WP_USE_THEMES', false);
require('../../../wp-blog-header.php');
$options = get_option('readability_options');
if((!isset($options['schedule']) || $options['schedule'] != "manually") && $options['schedule_manually'] != 'schedule_manually'){
	die("no manual execution allowed.");
}

do_action('k_readability_favorite_hook');
?>