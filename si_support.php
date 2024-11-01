<?php
/*
Plugin Name: Simple Support
Description: Provides a simple support ticket system for your intranet or organization.
Plugin URI: http://www.simpleintranet.org
Version: 1.1
Author: Simple Intranet
Author URI: http://www.simpleintranet.org
*/

function support_enable_comments (){
// enable comments
global $wpdb;
$scomment_query ="UPDATE ".$wpdb->prefix."posts SET comment_status = 'open' WHERE post_type = 'support'";
$wpdb->query($scomment_query);
}
register_activation_hook( __FILE__, 'support_enable_comments' );

function si_support_posts_for_current_author($query) {

if( !current_user_can( 'administrator','editor' ) ){
	if($query->is_admin) {

		global $user_ID;
		$query->set('author',  $user_ID);
	}
	return $query;
}
}
add_filter('pre_get_posts', 'si_support_posts_for_current_author');

function si_custom_post_support() {
global $wpdb;
$label_all = array(
		'name'               => _x( 'Support Requests', 'post type general name' ),
		'singular_name'      => _x( 'Support Request', 'post type singular name' ),
		'add_new'            => _x( 'New Request', 'request' ),
		'add_new_item'       => __( 'Support Request Form' ),
		'edit_item'          => __( 'Approve or Edit Request' ),
		'new_item'           => __( 'New Request' ),		
		'view_item'          => __( 'View Request' ),
		'search_items'       => __( 'Search Requests' ),
		'not_found'          => __( 'No requests found' ),
		'not_found_in_trash' => __( 'No request found in the Trash' ), 
		'parent_item_colon'  => '',
		'menu_name'          => 'Support'
	);
	if( current_user_can( 'administrator' ) ){
	$label_subscriber= array( 'all_items' => __( 'All Requests' ),);
	}
	else {
	$label_subscriber = array();	
	}
	$labels = array_merge((array)$label_all, (array)$label_subscriber);
	
	$args = array(
		'labels'        => $labels,
		'supports' => 'revisions',
		'description'   => 'Displays support requests and status.',
		'public'        => true,
		'menu_position' => 5,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
		'has_archive'   => false,	
		'show_ui'=> true,
		'exclude_from_search'=> true,
		'capability_type' => 'support',
		'capabilities' => array(
				'publish_posts' => 'si_support_publish',
				'edit_post' => 'si_support_edit_post',
				'edit_posts' => 'si_support_edit_posts',
				'edit_others_posts' => 'si_support_edit_others',
				'delete_posts' => 'si_support_delete_posts',
				'delete_post' => 'si_support_delete_post',
				'delete_others_posts' => 'si_support_delete_others_posts',
				'read_private_posts' => 'si_support_read_private_posts',				
				'read_post' => 'si_support_read_post',
				'read_posts' => 'si_support_read_posts',
				'read' => 'si_support_read',
			),		
		'rewrite' => array("slug" => "support"), 
		'register_meta_box_cb' => 'si_support_metaboxes',
	);
	register_post_type( 'support', $args );	
	flush_rewrite_rules();	
		
		
// ADD Capabilities
  $caps = array(
    'read',
	'read_post', 
    'read_posts', 
	'read_private_posts',
	'edit_post', 
    'edit_posts', 
	'edit_others_posts',
    'publish_posts',
	'delete_post', 	
    'delete_posts',  
	'delete_others_posts',
	'si_support_read',
	'si_support_read_post',
	'si_support_read_posts',
	'si_support_publish',
	'si_support_edit_post',
	'si_support_edit_posts',
	'si_support_edit_others',
	'si_support_delete_post',
	'si_support_delete_posts',
	'si_support_delete_others_posts',
	'si_support_read_private_posts',
  );
  $roles = array(
    get_role( 'administrator' ),
    get_role( 'editor' ),
  );
  foreach ($roles as $role) {
    foreach ($caps as $cap) {
		if ( is_object($role)) {
      $role->add_cap( $cap );
		}
    }
  }
  // ADD Capabilities for subscribers
  $caps2 = array(  	
 	'read',
	'read_post', 
    'read_posts', 
	'edit_post', 
    'edit_posts', 
	'publish_posts',
	'delete_post', 	
    'delete_posts',  
	'si_support_read',
	'si_support_read_post',
	'si_support_read_posts',
	'si_support_publish',
	'si_support_edit_post',
	'si_support_edit_posts',
  );
  $roles2 = array(
    get_role( 'subscriber' ),
     );
  foreach ($roles2 as $role2) {
    foreach ($caps2 as $cap2) {
		if ( is_object($role2)) {
      $role2->add_cap( $cap2 );
    }
  }  
}
}

add_action( 'init', 'si_custom_post_support' );

add_action('admin_menu' , 'si_support_admin_support_settings'); 
function si_support_admin_support_settings() {
    add_submenu_page('edit.php?post_type=support', 'Your Support History', 'Your Support History', 'edit_posts', 'your-support-settings', 'si_support_user_settings');
	
	add_submenu_page('edit.php?post_type=support', 'Settings', 'Settings', 'edit_others_support', 'support-settings', 'si_support_admin_settings');	
   }

function si_support_user_settings(){
global $user_id,$post;
$user_id = get_current_user_id();	
// count posts outstanding for current user
        query_posts(array( 
            'post_type' => 'support',
            'author' => $user_id,
        ) ); 
            $rcount = 0;
			$pcount = 0;
            while (have_posts()) : the_post(); 
			$si_support_approved = get_post_meta(get_the_ID(), 'si_support_approved', true);
			if($si_support_approved=="Resolved"){
                $rcount++; 
			}
			if($si_support_approved=="Pending"){
                $pcount++; 
			}
            endwhile;
            wp_reset_query();


?>
<div class="wrap">

        <h2>Your Support Settings</h2>
  <table width="500px" border="0" cellspacing="0" cellpadding="3">                      
           
           <tr>
            <td><span>Your support requests outstanding: </span></td>
            <td><?php echo $pcount; ?> request<?php if ($pcount!=1) {
echo 's';
} ?></td>
          </tr>
          <tr>
            <td><span>Your support requests resolved: </span></td>
            <td><?php echo $rcount; ?> request<?php if ($rcount!=1) {
echo 's';
} ?></td>
          </tr>
                
         </table>
</div>
<?php  
}

	
function si_support_admin_settings(){
global $post,$user_id;
$user_id=get_option('current_author');
$count_support_posts = wp_count_posts('posts');
$total_support_requests= $count_support_posts->publish; 
update_user_meta($user_id, 'support_requests_outstanding', $total_support_requests);

if($_POST['author']){
$user_id = $_POST['author'];
update_option('current_author', $_POST['author'] );	
}
if($_POST['submit_support_settings']){
update_option('default_approver_email',$_POST['default_approver_email']);	
}
$user_id=get_option('current_author');
// count posts outstanding for current user
        query_posts(array( 
            'post_type' => 'support',
            'author' => $user_id,
        ) ); 
            $rcount = 0;
			$pcount = 0;
            while (have_posts()) : the_post(); 
			$si_support_approved = get_post_meta(get_the_ID(), 'si_support_approved', true);
			if($si_support_approved=="Resolved"){
                $rcount++; 
			}
			if($si_support_approved=="Pending"){
                $pcount++; 
			}
            endwhile;
            wp_reset_query();
?>
<div class="wrap">

        <h2>Support Settings (Admins only)</h2>
        <form method="post" action="">
  <table width="500px" border="0" cellspacing="0" cellpadding="3">
  
    <tr>
            <td><span>Select an employee to edit: </span></td>
            <td> <?php wp_dropdown_users(array('name' => 'author', 'selected' => $user_id)); ?><input type="submit" name="view_support_settings" value="View" /></form></td>
          </tr>                 
           
          <form method="post" action="">
          <tr>
            <td><span>Support requests outstanding: </span></td>
            <td><?php echo $pcount; ?> request<?php if ($pcount!=1) {
echo 's';
} ?></td>
          </tr>
          <tr>
            <td><span>Support requests resolved: </span></td>
            <td><?php echo $rcount; ?> request<?php if ($rcount!=1) {
echo 's';
} ?></td>
          </tr>
            <tr>  
            
            </tr>    
               <tr>
          <td><span>Set default support email;</span></td>
            <td>
<input type="text"  name="default_approver_email" id="default_approver_email" <?php 
		$default_email= get_option('default_approver_email');
		
		 echo 'value="'. $default_email.'"';
			 ?>/>
             </td>
            </tr>  
            <tr>   
            <td><a href="<?php echo plugins_url('simple-support/download.php');?>">Download support requests to an Excel file</a>
            </td>
            </tr>
         </table>
          <input type="submit" name="submit_support_settings" value="Save/Update" /></form> 
</div>
    <?php 	}


// Add Custom Columns

add_filter( 'manage_edit-support_columns', 'si_support_my_edit_support_columns' ) ;

function si_support_my_edit_support_columns( $columns ) {

	$columns = array(
		'cb' => '<input type="checkbox" />',
		'ticket' =>__( 'Ticket #' ),
		'title' => __( 'Description' ),
		'employee' => __( 'Author' ),				
		'approval' => __( 'Approver' ),
		'approved' => __( 'Status' ),
		'date' => __( 'Request Date' ),	
	);

	return $columns;
}

add_action( 'manage_support_posts_custom_column', 'my_manage_support_columns', 10, 2 );


function my_manage_support_columns( $column, $post_id ) {
	global $post,$parent,$edate,$sdate,$updated_days_used;
	
	switch( $column ) {
		
		case 'ticket' :
		/* Get the post meta. */
		echo $post_id;
		break;
		
		case 'employee' :
		/* Get the post meta. */
		the_author();
		break;		
		
		
		case 'approval' :
		global $post,$approver_name;
		$approver_name= get_post_meta($post_id, 'si_support_approver', true);	
		if($approver_name==''){
		$approver_name = 'admin';
		}
		echo $approver_name;			 
		break;	
		
		case 'approved' :
		$support_status2= get_post_meta($post_id, 'si_support_approved', true);
		if($support_status2==''){
		update_post_meta($post_id, 'si_support_approved', 'Pending');
		}
		echo get_post_meta($post_id, 'si_support_approved', true);					
		break;		
		
	}
}

//Add Meta Boxes

function si_support_metaboxes( $meta_boxes ) {
	
	if ( current_user_can('administrator','editor') ) {
add_meta_box('si_support_approver_name', 'Support Contact Name', 'si_support_approver_name', 'support', 'normal', 'high');
	}	
	return $meta_boxes;	
}

function si_support_approver_name () {

global $post;
	// Noncename needed to verify where the data originated
	echo '<input type="hidden" name="si_support_noncename" id="si_support_noncename" value="' .
	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	// Get the location data if its already been entered
	$si_support_approver = get_post_meta($post->ID, 'si_support_approver', true);
	$si_support_approved = get_post_meta($post->ID, 'si_support_approved', true);
	if($si_support_approved==''){
	$si_support_approved =="Pending";
	$select = 'selected="selected"';
	$select2 = '';
	}
	if ($si_support_approved == "Resolved"){
	$select = '';
	$select2 = 'selected="selected"';
	}
	
	// Echo out the field
?>	Enter the name of your support contact:

<SELECT NAME="si_support_approver" id="si_support_approver"  class="regular-text" VALUE="<?php if(!empty($si_support_approver)) {echo $si_support_approver; } else {
				 echo "Select";
			 }?>">
             <OPTION VALUE="<?php echo $si_support_approver; ?>" selected="selected"><?php if($si_support_approver) { 
			 echo $si_support_approver; 
			 } 
			 elseif ($si_support_approver==''){
			 echo "Please select";	 
			 }
			 else {
			 echo "Please select a user/employee."; 
			 }
// Create the WP_User_Query object for showing all users who could be parents reported to
global $wpdb,$si_support_approver,$display2;
$args  = array(
	'orderby' => 'first_name',  // Order by display name	
);
$wp_user_query = new WP_User_Query($args);
// Get the results
$authors = $wp_user_query->get_results();
// Check for results
foreach ($authors as $author ) {
$first2=get_the_author_meta('first_name', $author->ID);
$last2=get_the_author_meta('last_name', $author->ID);
$display2=get_the_author_meta('user_login', $author->ID);
echo '<OPTION VALUE="'.$display2.'">'.$first2.' '.$last2.' (username:'.$display2.')';
} 
?></SELECT><br />
		
       <?php	
	
	echo 'Enter the support status: ';
	echo '<select name="si_support_approved" id="si_support_approved">
      <option value="Pending"'.$select.'>Pending</option>
      <option value="Resolved"'.$select2.'>Resolved</option>
    </select>';	
}

add_filter( 'add_meta_boxes', 'si_support_metaboxes' );

// Save metabox data
function si_support_save_my_metadata(){

	if(!isset($post) || $post->post_type != 'support')
        return;
$p=$post->ID;		
if($_POST['si_support_approved']==''){
	$_POST['si_support_approved']="Pending";
}
    update_post_meta($post_id, 'si_support_approver', $_POST['si_support_approver']);
	update_post_meta($post_id, 'si_support_approver_email', $_POST['si_support_approver_email']);
	if ( current_user_can('administrator','editor') ) {
 update_post_meta($post_id, 'si_support_approved', $_POST['si_support_approved']);
	}
	if ( !current_user_can('administrator','editor') ) {
		update_post_meta($post_id, 'si_support_approved', 'Pending');
	}
  $author_id=$post->post_author;
  update_post_meta($post_id, 'si_support_author', get_the_author_meta( 'display_name', $author_id )); 

}

add_action('save_post', 'si_support_save_my_metadata');


//Notify Authors when support Request Submitted
function si_support_email($post) {

$post = get_post($post_id);
if ( ! empty( $_POST['post_type'] ) && 'support' == $_POST['post_type'] ) {
$author_id=$post->post_author; 
    
$admin_email = get_option('admin_email'); 
$website_name =get_option('blogname');
$post_author = esc_attr( get_the_author_meta( 'display_name', $author_id ) );
$post_author_email =esc_attr( get_the_author_meta( 'user_email', $author_id ) );

$headers = 'From: '.$post_author.'<'.$post_author_email.'>' . "\r\n";
$headers2 = 'From: '.$website_name.'<'.$admin_email.'>' . "\r\n";

$post_title = html_entity_decode(get_the_title($post),ENT_QUOTES,'UTF-8');
$p=$post_id;
if ($p==''){
$p=$post->ID;
}
  if( $_POST ) {
        update_post_meta( $post->ID, 'si_support_approver', $_POST['si_support_approver'] );
		update_post_meta( $post->ID, 'si_support_approver_email', $_POST['si_support_approver_email'] );
        update_post_meta( $post->ID, 'si_support_approved', $_POST['si_support_approved'] );
	    }
	
$meta_approval_status = get_post_meta($p, 'si_support_approved', true);
$app_name = get_post_meta($p, 'si_support_approver', true);
$app_email = get_post_meta($p, 'si_support_approver_email', true);

$support_url = admin_url().'post.php?post='.$p.'&action=edit';
$support_front = get_permalink($p);

$message = "Hi ".$post_author.",
Your support request ticket #".$p." ".$post_title." has been sent.
";

if ($app_name!=''){
$message .= $app_name." has been sent an e-mail to approve your request.
" ;
}
if ($meta_approval_status!=''){
$message .= "Current approval status: ".$meta_approval_status." 
";
}
if ($app_name!=''){
$message .= "Person approving: ".$app_name;
}

$message2 = "Please respond by commenting on this support post: ".$support_front." 

You can mark this post Resolved or Pending here: ".$support_url."

Submitted by: ".$post_author." 
";
if ($meta_approval_status!=''){
$message2 .= "Current approval status: ".$meta_approval_status." 
";
}
if ($app_name!=''){
$message2 .= "Person approving: ".$app_name;
}
if ($app_email==''){
$app_email=	get_option('admin_email');
} 

wp_mail($post_author_email, "Your support request has been updated", $message);
wp_mail($app_email, "Please review this support request", $message2, $headers2);

} // Support Post Types Only

}
add_action('transition_post_status','si_support_email'); //publish_support

function support_title_change($post) {
global $wpdb,$post;
$p=$post->ID;
$post = get_post($post_id);
$si_support_approved = get_post_meta($p, 'si_support_approved', true);
if ($si_support_approved=="Resolved"){
$status_query ="UPDATE ".$wpdb->prefix."posts SET post_title = replace(post_title,' : Pending',' : Resolved') WHERE ID = '$p'";
$wpdb->query($status_query);
$scomment_query2 ="UPDATE ".$wpdb->prefix."posts SET comment_status = 'closed' WHERE ID = '$p'";
}
$wpdb->query($scomment_query2);
if ($si_support_approved=="Pending"){
$status_query4 ="UPDATE ".$wpdb->prefix."posts SET post_title = replace(post_title,' : Resolved',' : Pending') WHERE ID = '$p'";
$wpdb->query($status_query4);
$scomment_query3 ="UPDATE ".$wpdb->prefix."posts SET comment_status = 'open' WHERE ID = '$p'";
$wpdb->query($scomment_query3);
}
}

add_action('save_post','support_title_change');


// Change Title text box default value
function si_support_change_default_title( $title ){
     $screen = get_current_screen();
 
     if  ( 'support' == $screen->post_type ) {
          $title = 'Title for your support request goes here.';
     } 
     return $title;
}
add_filter( 'enter_title_here', 'si_support_change_default_title' );

add_filter( 'gettext', 'si_support_change_publish_button', 10, 2 );

// Rename publish button for support post type only
function si_support_change_publish_button( $translation, $text ) {
if ( $text == 'Publish' && !empty($_GET['post_type']) && $_GET['post_type']=='support' )
    return 'Submit Request';

return $translation;
}
add_action('init', 'kses_init');
// Add shortcode to front end for support input

add_shortcode( 'support', 'si_support_shortcode' );

/**
 * Enqueue the date picker
 */

function si_support_shortcode() {
	
wp_enqueue_style('jquery-ui-datepicker', plugins_url( '/css/datepicker.css', __FILE__ ) ); 
if(!empty($_POST['support2']) && $_POST['support2']=="submit" && !empty( $_POST['action2'] )) {
echo '<font color="red"><strong>Thanks for submitting your support request!<br><strong></font>';
}
?>
<form method="post" name="support_form" action="" id="support_form" enctype="multipart/form-data">
Title of support request:<br /> <input type="text" name="title2" id="title2" value="" /><br />
Details of issue:<br /> <textarea name="si_support_support_type" id="si_support_support_type" rows="5" cols="70"></textarea><br />
<input type="hidden" name="si_support_approved" id="si_support_approved" value="Pending" /><br />
Upload a screenshot or file here:<br />
<input type="file" name="myfile" id="myfile"><br /><br />
<input type="hidden" name="support2" id="support2" value="submit" />
<input type="hidden" name="action2" value="new_support" />
<input type="submit" value="Submit Support Request">
<?php wp_nonce_field( 'new_support' ); ?>
</form>
<?php 
}
function simple_support_add_post(){	
global $post;
if(!empty($_POST['support2']) && $_POST['support2']=="submit" && !empty( $_POST['action2'] )) {
	
$uploadedfile = $_FILES['myfile'];
$upload_overrides = array( 'test_form' => false );
$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );	
if ( $movefile ) {

    $wp_filetype = $movefile['type'];
    $filename = $movefile['file'];
    $wp_upload_dir = wp_upload_dir();
    $attachment = array(
        'guid' => $wp_upload_dir['url'] . '/' . basename( $filename ),
        'post_mime_type' => $wp_filetype,
        'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
        'post_content' => 'File',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment( $attachment, $filename);
	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	$attach_data = wp_generate_attachment_metadata($attach_id, $filename);
	wp_update_attachment_metadata($attach_id,  $attach_data);
    $file_attach_url = wp_get_attachment_url( $attach_id );
	if($file_attach_url!=''){
	$support_image = '<img src="'.$file_attach_url.'">';
	}
}
	
$title2     = $_POST['title2'];
$support_type = $_POST['si_support_support_type'];  
$support_approved = $_POST['si_support_approved'];
$current_user = wp_get_current_user();
$author_id = $current_user->ID;
$support_author = esc_attr( get_the_author_meta( 'display_name', $author_id ) );
$support_author_email = esc_attr( get_the_author_meta( 'user_email', $author_id ) );

//the array of arguments to be inserted with wp_insert_post
$scontent = $support_type.'<br>'.$support_image;
$title2=$title2.' : Pending';
$new_post = array(
'post_title'    => $title2,
'post_type'     =>'support',
'post_status'   => 'publish',
'post_content'  => $scontent
);

//insert the the post into database by passing $new_post to wp_insert_post
$pid = wp_insert_post($new_post);

//we now use $pid (post id) to help add our post meta data
add_post_meta($pid, 'si_support_ticket', $pid, true);
add_post_meta($pid, 'si_support_type', $support_type, true);
add_post_meta($pid, 'si_support_approver', $support_approver, true);
add_post_meta($pid, 'si_support_approver_email', $support_approver_email, true);
add_post_meta($pid, 'si_support_approved', $support_approved, true);
add_post_meta($pid, 'si_support_author', $support_author, true);
add_post_meta($pid, 'si_support_author_email', $support_author_email, true);
add_post_meta($pid, 'si_support_file', $filename, true);

// notify user submitted and person approving
$post = get_post($post_id);
if ( ! empty( $_POST['action2'] ) && 'new_support' == $_POST['action2'] ) {
$author_id=$post->post_author; 

$admin_email = get_option('admin_email'); 
$website_name =get_option('blogname');

$p=$post_id;
if ($p==''){
$p=$post->ID;
}
$meta_approval_status = get_post_meta($p, 'si_support_approved', true);
$app_name = get_post_meta($p, 'si_support_approver', true);
$app_email = get_post_meta($p, 'si_support_approver_email', true);
if($app_email==''){
$app_email = get_option('default_approver_email');	
}
if($app_email==''){
$app_email= get_option('admin_email');	
}

$support_url = admin_url().'post.php?post='.$pid.'&action=edit';
$support_front =  get_permalink( $pid );

$message = "Hi ".$support_author.",
Your support request ticket #".$pid." ".$title2." has been sent.
";
if ($app_name!=''){
$message .= $app_name." has been sent an e-mail to approve your request.
" ;
}
if ($meta_approval_status!=''){
$message .= "Current approval status: ".$meta_approval_status." 
";
}
if ($app_name!=''){
$message .= "Person approving: ".$app_name;
}

$message2 = "Please respond by commenting on this support post: ".$support_url."

You can mark this post Resolved or Pending here: ".$support_front."
";
if ($attach_id!=''){
$message2 .= "File attachment: ".$file_attach_url."
";
}
if ($support_author!=''){
$message2 .= "Submitted by: ".$support_author." 
";
}
if ($meta_approval_status!=''){
$message2 .= "Current approval status: Pending. ";
}
if ($app_name!=''){
$message2 .= "Person approving: ".$app_name;
}

if ($app_email==''){
$app_email=	get_option('admin_email');
} 
$ws_title = get_bloginfo('name');
$ws_email = get_bloginfo('admin_email');
$headers = 'From: '.$ws_title.' <'.$ws_email.'>' . "\r\n";
$subject = 'Your support request ticket #'.$pid.' has been submitted';
$subject_approver = 'Please review this support ticket #'.$pid.' request.';
wp_mail($support_author_email, $subject, $message, $headers);
wp_mail($app_email, $subject_approver, $message2, $headers);
// end of email stuff
}
}
}

add_action('init','simple_support_add_post');

function simple_support_email_comment () {
global $post;
$support_front =  get_permalink( $post->ID );
$ss_comment_author= get_post_meta($post->ID, 'si_support_author_email' );
$ss_subject = 'A comment has been made on support request ticket #'.$post->ID.'';
$ss_message = 'Please view this comment on your support post URL: '.esc_url($support_front);
$ws_title = get_bloginfo('name');
$ws_email = get_bloginfo('admin_email');
$headers = 'From: '.$ws_title.' <'.$ws_email.'>' . "\r\n";
wp_mail($ss_comment_author, $ss_subject, $ss_message, $headers);
}

add_action('comment_post','simple_support_email_comment');

function simple_support_custom_help_tab() {
	global $post_ID;
	$screen = get_current_screen();

	if( isset($_GET['post_type']) ) {
		$post_type = $_GET['post_type'];
	}
	else {
	$post_type = get_post_type( $post_ID );
	}

	if( $post_type == 'support' ) {
		$screen->add_help_tab( array(
			'id' => 'support_custom_id', //unique id for the tab
			'title' => 'Simple Support Help', //unique visible title for the tab
			'content' => '<h3>Adding A Form To Front-end</h3><p>To add a support request form to the front-end, insert the shortcode <strong>[support]</strong> into a post or page.</p>',  //actual help text
		));		
	}
}

add_action('admin_head', 'simple_support_custom_help_tab');