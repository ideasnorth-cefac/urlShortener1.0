<?php
   /*
   @author      Estrella A.
   @copyright   Estrella A.
   Plugin Name: URL Shortener
   Plugin URI: http://plugins.comounapina.com
   description: Shorten URLs
   Version: 1.0
   Author: estrella
   Author URI: http://ideasnorth.com
   License: GPL2
   */


//Include scripts and pages
add_action('admin_enqueue_scripts', function()
{
    wp_enqueue_script( 'js_scripts', plugin_dir_url( __FILE__ ) .'scripts.js' );
});

add_action('wp_enqueue_scripts', function()
{
    wp_enqueue_script('jquery');
});

include (plugin_dir_path( __FILE__ ) .'settings-pages.php');
include (plugin_dir_path( __FILE__ ) .'urls-list.php');

//Create plugin's DB
global $urlShortener_version;
$urlShortener_version = '1.0';

function create_urlShortener_table() 
{
    global $wpdb;
    global $urlShortener_version;

    $table_name = $wpdb->prefix . 'url_shortener';
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        url_long varchar(191) NOT NULL,
        url_short varchar(191) NOT NULL,
        visits int DEFAULT '0' NOT NULL,
        time_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        time_last_access datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (url_long)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    add_option( 'urlShortener_version', $urlShortener_version );
}
register_activation_hook( __FILE__, 'create_urlShortener_table' ); 

//Delete tables in DB when deleting plugin
function delete_plugin_data(){
    //DB table
    global $wpdb;
    $table_name = $wpdb->prefix . 'url_shortener';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);

    //options
    delete_option('urlShortener_version');
    delete_option('url_shortener_length');
    delete_option('url_shortener_string_option');

    //Custom post type posts
    $urlShortener_args = array('post_type' => 'url_shortener', 'posts_per_page' => -1);
    $urlShortener_posts = get_posts($urlShortener_args);
    foreach ($urlShortener_posts as $post) {
	    wp_delete_post($post->ID, false);
    }
}

register_uninstall_hook(__FILE__, 'delete_plugin_data');

//URL shortener functionality
function insert_url_db($url_long, $url_short) 
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'url_shortener';
    $wpdb->insert( 
        $table_name, 
        array( 
            'time_created' => current_time( 'mysql' ), 
            'time_last_access' => current_time( 'mysql' ), 
            'visits' => '0',
            'url_long' => $url_long, 
            'url_short' => $url_short 
        ) 
    );
}

function delete_url_db ($url,$which_url)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'url_shortener';
    $wpdb->delete( $table_name, array( $which_url => $url ) );
}

function edit_url_db($url_old, $url_new,$which)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'url_shortener';

    $wpdb->update($table_name, array( $which => $url_new), array( $which => $url_old )   );
}

function shortURLexists($url_short) 
{
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'url_shortener';
    $row = $wpdb->get_row("SELECT * FROM $table_name WHERE url_short = '".$url_short."'");

    if ($row !== NULL)
        $result=1;
    else $result=0;
    
    return $result;
}

function URLexists($url,$which_url) 
{
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'url_shortener';
    $row = $wpdb->get_row("SELECT * FROM $table_name WHERE $which_url = '".$url."'");

    if ($row !== NULL)
        $result=1;
    else $result=0;
    
    return $result;
}

function returnValue($url_short,$which_value) 
{
    $result='';

    global $wpdb;
    
    $table_name = $wpdb->prefix . 'url_shortener';
    $result = $wpdb->get_var("SELECT $which_value FROM $table_name WHERE url_short = '".$url_short."'");

    return $result; 
}

function updateStats($url_short) 
{
    global $wpdb;    
    $table_name = $wpdb->prefix . 'url_shortener';
    
    $visits = $wpdb->get_var("SELECT visits FROM $table_name WHERE url_short = '".$url_short."'");

    $wpdb->update( 
        $table_name, 
        array( 
            'time_last_access' => current_time( 'mysql' ), 
            'visits' => $visits + 1,
        ),
        array(
            'url_short' => $url_short
        ) 
    );
}

function authorise_user ()
{
    if(isset($_COOKIE))
    {
        foreach($_COOKIE as $key=>$val)
        {
            if(strpos($key,'wordpress_logged_in_') !== false) 
            {
                return true;
            }
        }
    }   
    return false;
}

function shortenURL ($request)
{
    $length = 6;
    $url_long = $request->get_param( 'url_long');
    $length = $request->get_param( 'length');
    $option = $request->get_param( 'option'); 

    //if long url already exists in DB we don't shorten it
    if ( URLexists($url_long,'url_long') === 1 )
        return null;

    //1=numeric; 2=char; 3=alpha
    switch ($option)
    {
        case 1: $permitted_chars = '0123456789';
                break;
        case 2: $permitted_chars = 'abcdefghijklmnopqrstuvwxyz';
                break;
        default: $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    }

    $alphanumericString = substr(str_shuffle($permitted_chars), 0, $length);
    $url_short= get_home_url().'/'.$alphanumericString;

    //check that it's not being used already
    while ( URLexists($url_short,'url_short') === 1 )
    {
        $alphanumericString = substr(str_shuffle($permitted_chars), 0, 6);
        $url_short= get_home_url().'/'.$alphanumericString;
    }

    echo $url_short;
    insert_url_db($url_long,$url_short);
}

function editURL ($request)
{
    $url_short_old = $request->get_param( 'url_short_old');
    $url_short_new = $request->get_param( 'url_short_new');

    if($url_short_new === get_home_url().'/')
        return 0;

    if($url_short_new === $url_short_old)
        return $url_short_old;

    //if new short url already exists in DB 
    if ( URLexists($url_short_new,'url_short') === 1 )
        return 1;
    
    edit_url_db($url_short_old,$url_short_new,'url_short');
    return $url_short_new;
}

add_action ('template_redirect', 'redirect');
function redirect ()
{
    //Check if url is a short one in DB
    $uri=$_SERVER['REQUEST_URI'];
    $url_short=get_home_url().$uri;
    $long_url=returnValue($url_short,'url_long');

    if ($long_url !== '')
    {
        wp_redirect($long_url);
        updateStats($url_short);
    }
}

function getURLvisits($data)
{
    echo returnValue($data['url_short'],'visits');
}

function getURLlastAccess($data)
{
    echo returnValue($data['url_short'],'time_last_access');
}

function getURLcreationDate($data)
{
    echo returnValue($data['url_short'],'time_created');
}

function getURLlist($data)
{
    //parameters: order = asc|desc ; type = visits| time_created | time_last_access
    global $wpdb;
    $order=$data['order'];
    if (!$order) $order='asc';
    $type=$data['type'];
    If (!$type) $type='visits';
    
    $table_name = $wpdb->prefix . 'url_shortener';
    $row = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY ".$type." ".$order);

    $urlList=[];

    $count=0;
    foreach ( $row as $row ) 
    { 
        $urlList[$count]=$row->url_short;
        $count++;
    } 
    return $urlList;
}

function deleteURL ($request)
{
    $url_short = $request->get_param( 'url_short');

    //if long url already exists in DB we don't shorten it
    if ( URLexists($url_short,'url_short') === 0 )
        return null;

    echo "deleting: ".$url_short;
    delete_url_db($url_short,'url_short');
}

// --- API methods ---
//POST
add_action( 'rest_api_init', function () 
{
    register_rest_route( 'urlShortener/v1', '/post-call', array(
      'methods' => 'POST',
      'callback' => 'shortenURL',
      'permission_callback' => 'authorise_user'      
    ) );
} );

add_action( 'rest_api_init', function () 
{
    register_rest_route( 'urlShortener/v1', '/edit-url', array(
      'methods' => 'POST',
      'callback' => 'editURL',
      'permission_callback' => 'authorise_user'      
    ) );
} );

//GET
add_action( 'rest_api_init', function () 
{
    register_rest_route( 'urlShortener/v1', '/visits', array(
      'methods' => 'GET',
      'callback' => 'getURLvisits',
      'permission_callback' => 'authorise_user'      
    ) );
} );

add_action( 'rest_api_init', function () 
{
    register_rest_route( 'urlShortener/v1', '/last-accessed', array(
      'methods' => 'GET',
      'callback' => 'getURLlastAccess',
      'permission_callback' => 'authorise_user'      
    ) );
} );

add_action( 'rest_api_init', function () 
{
    register_rest_route( 'urlShortener/v1', '/creation-date', array(
      'methods' => 'GET',
      'callback' => 'getURLcreationDate',
      'permission_callback' => 'authorise_user'      
    ) );
} );

add_action( 'rest_api_init', function () 
{
    register_rest_route( 'urlShortener/v1', '/url-list', array(
      'methods' => 'GET',
      'callback' => 'getURLlist',
    ) );
} );

//DELETE
add_action( 'rest_api_init', function () 
{
    register_rest_route( 'urlShortener/v1', '/delete', array(
      'methods' => 'DELETE',
      'callback' => 'deleteURL',
      'permission_callback' => 'authorise_user'      
    ) );
} );

register_activation_hook(__FILE__, 'add_my_custom_page');

?>