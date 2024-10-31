<?php
include_once('OAuth.php');
include_once('o-auth-config.php');
add_action('admin_menu', 'k_readability_menu');

function k_readability_menu() {
	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_enqueue_style('thickbox.css', '/'.WPINC.'/js/thickbox/thickbox.css', null, '1.0');
	add_options_page(__('Readability Plugin Options',LOCALE_DOMAIN), 'Readability Plugin', 'manage_options', 'k_readability', 'k_readability_options');	
}

function k_readability_options(){
	if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	$link = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?page={$_GET['page']}";
	
	if(isset($_GET['manual']) && $_GET['manual'] == "manually"){
		do_action('k_readability_favorite_hook');
	}
	$options = get_option('readability_options');
	?>
	
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br/></div>
		<h2><?php _e("Readability Favorites",LOCALE_DOMAIN); ?></h2>
		<p>Readability Favorites is a plugin to generate a post with a list of all your favorites at readability</p>
		<p><?php _e("Here you can manage all options of this Plugin", LOCALE_DOMAIN); ?></p>
		<p>You need an account at readability. If you don't have one, <a href="https://www.readability.com/register/">get a free one</a></p>
		
		<h2 class="nav-tab-wrapper">
			<a href="?page=k_readability&k_tab=main" class="nav-tab <?php echo ($_GET['k_tab'] == 'main' || empty($_GET['k_tab']))? "nav-tab-active":""; ?>">Main</a>
			<a href="?page=k_readability&k_tab=post" class="nav-tab <?php echo ($_GET['k_tab'] == 'post')? "nav-tab-active":""; ?>">Post</a>
			<a href="?page=k_readability&k_tab=preview" class="nav-tab <?php echo ($_GET['k_tab'] == 'preview')? "nav-tab-active":""; ?>">Preview</a>
			<a href="?page=k_readability&k_tab=about" class="nav-tab <?php echo ($_GET['k_tab'] == 'about')? "nav-tab-active":""; ?>">About</a>
		</h2>
		<form method="post" action="options.php">
		<?php settings_fields( OPTIONSNAME );  ?>
		<?php 
		$tab = "main";
		switch($_GET['k_tab']){
			case 'main':
			$tab = "main";
			break;
			case 'post':
			$tab = "post";
			break;
			case 'preview':
			$tab = "preview";
			break;
			case 'about':
			$tab = "about";
			break;
		}
		if($tab != "about"){
		do_settings_sections(OPTIONSNAME."_".$tab);
		?>
		<table class="form-table">
			<tr>
				<td><input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>"></td>
				<td> &nbsp;</td>
			</tr>
		</table>
		</form>
		<?php }else{ ?>
			<p><?php _e('If you like, please visit my <a href="http://blog.kanedo.net/?pk_campaign=Plugin&pk_kwd=Readability%20Favorites"><strong>blog</strong></a></p>',LOCALE_DOMAIN); ?>
			<script type="text/javascript">
			/* <![CDATA[ */
			    (function() {
			        var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
			        s.type = 'text/javascript';
			        s.async = true;
			        s.src = 'http://api.flattr.com/js/0.6/load.js?mode=auto';
			        t.parentNode.insertBefore(s, t);
			    })();
			/* ]]> */</script>
			<p><?php _e("and you can send me a flattr",LOCALE_DOMAIN); ?></p>
			<a class="FlattrButton" style="display:none;" href="http://kanedo.net/projekte/readability-favorites/"></a>
			<noscript><a href="http://flattr.com/thing/550258/Readability-Favorites-Wordpress-Plugin" target="_blank">
			<img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a></noscript>
				
	<?php } ?>
	</div>
	<?php 
}
?>