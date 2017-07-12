<?php
/*
Plugin Name: dPost Uploads
Version: 1.1
Description: Stores uploads in the month folder of the post. Eg. Edit a post from Dec 2007, Uploads will be stored in /2007/12/ if selected. <strong>Note:</strong> Radio options only available on posts published in the past/future
Author: Dion Hulse

Author URI: http://dd32.id.au/
Plugin URI: http://dd32.id.au/wordpress-plugins/post-uploads/
*/

add_action('init', 'pu_init');
function pu_init(){
	if( ! get_option('uploads_use_yearmonth_folders') )
		return;
	if( strpos($_SERVER['REQUEST_URI'], 'media-upload.php') || strpos($_SERVER['REQUEST_URI'], 'async-upload.php') ){
		add_action('flash_uploader', 'pu_flash_checkbox'); //Yup, lets hook a filter as an action.. 
		add_filter('upload_dir', 'pu_upload_filter');
	}
}

function pu_upload_filter($uploads){
	if( ! isset($_REQUEST['pu_upload']) )
		return $uploads;
	else
		$folder = $_REQUEST['pu_upload'];
	
	if( $uploads['subdir'] == $_REQUEST['pu_upload'])
		return $uploads;

	$uploads['path'] = str_replace($uploads['subdir'], $folder, $uploads['path']);
	$uploads['url'] = str_replace($uploads['subdir'], $folder, $uploads['url']);
	$uploads['subdir'] = $folder;
	
	// Make sure we have an uploads dir
	if ( ! wp_mkdir_p( $uploads['path'] ) ) {
		return array( 'error' => sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $dir ) );
	}

	return $uploads;
}

function pu_flash_checkbox($use_flash){
	pu_checkbox();
	if( $use_flash )
		add_action('attribute_escape', 'pu_flash_js_hook'); //Eww, Yes i know, There just happens to be no other useable hooks after this. We'll only be hooked a few calls anyway.
	return $use_flash;
}

function pu_flash_js_hook($s){
	if( $s != __('Insert into Post') )
		return $s; //Not the hook we want.. Hopefully trasnalting it will allow us to work with non-en languages.
	remove_action('attribute_escape', 'pu_flash_js_hook'); //We found it, lets get off this hook..
	?>
		<script type="text/javascript">
		<!--
		uploadStart = function(obj){
			var pu_upload = false;
			if( jQuery("#pu_upload-post").attr('checked') )
				pu_upload = jQuery("#pu_upload-post").val();

			else if( jQuery("#pu_upload-current").attr('checked') )
				pu_upload = jQuery("#pu_upload-current").val();
			
			if( pu_upload )
				swfu.addFileParam(obj.id, 'pu_upload', pu_upload );
			return true;
		}
		-->
		</script>
		<?php
	return $s; //Remember, this is a filter.. Even if we hook it as an action it needs to return a value.
}

function pu_checkbox($echo=true){ //Echo can be utilised by other plugins of mine which have ajaxified content returns.
	$id = (int)$_GET['post_id'];
	if( ! $id || $id < 0 )
		return;
	
	$post = get_post($id);
	if( ! $post )
		return;

	$post_date = strtotime($post->post_date);
	if( ! $post_date )
		$post_date = strtotime($post->post_modified);

	$post_date_folder = date('/Y/m', $post_date);
	$current_date_folder = date('/Y/m', time());
	
	if( $post_date_folder == $current_date_folder)
		return;

	if( isset($_REQUEST['pu_upload']) )
		$selected = $_REQUEST['pu_upload'];
	else
		$selected = $current_date_folder;

	if( ! in_array($selected, array($post_date_folder, $current_date_folder) ) )
		$selected = $current_date_folder;

	$return = '<div>';
	$return .= __('Store uploads in');
	$return .= '<input type="radio" name="pu_upload" id="pu_upload-post" value="'. $post_date_folder .'"' . (($post_date_folder == $selected) ? ' checked="checked"' : '') . 
				'/> <strong>' . $post_date_folder . '/</strong>';

	$return .= '<input type="radio" name="pu_upload" id="pu_upload-current" value="' . $current_date_folder . '"' . (($current_date_folder == $selected) ? ' checked="checked"' : '') . 
				' /> <strong>' . $current_date_folder . '/</strong>';
	$return .= '</div>';
	
	if( $echo )
		echo $return;
	else
		return $return;
}

?>