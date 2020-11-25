<?php


// --- settings page BEGIN ---
add_action('admin_menu', 'url_shortener_create_menu');

function url_shortener_create_menu() 
{
	//create new top-level menu
	add_menu_page('URL Shortener Settings', 'URL Shortener', 'manage_options', 'url_shortener_settings_page','url_shortener_settings_page_display');
	add_submenu_page('url_shortener_settings_page', 'URL Stats', 'Stats', 'manage_options','url_shortener_stats_page', 'url_shortener_stats_page_display' );    

    //call register settings function
    add_action( 'admin_init', 'url_shortener_settings' );
    add_action( 'admin_init', 'url_shortener_stats' );
}

function url_shortener_settings() 
{
    // add_settings_section( Section ID,       Section Title,                 Callback function (can be null),    Settings page slug);  
    add_settings_section( 'section_options', 'Options to generate your URL', 'print_section_options_info', 'options_page');  
    add_settings_field('url_shortener_options' , null, 'url_shortener_options_display', 'options_page', 'section_options'); 
    register_setting( 'section_options', 'url_shortener_string_option' );
    register_setting( 'section_options', 'url_shortener_length' );
    
    add_settings_section( 'section_generation', 'Generate your short URL', 'print_section_generate_info', 'options_page');  
    add_settings_field('urls' , null, 'urls_display', 'options_page', 'section_generation'); 
}

function url_shortener_stats() 
{
    add_settings_section( 'section_list', 'Your URLs', 'print_section_list_info', 'stats_page');  
    add_settings_field('url_shortener_url_list' , null, 'url_shortener_url_stats_display', 'stats_page', 'section_list'); 
    register_setting( 'section_list', 'url_shortener_delete' );
    register_setting( 'section_list', 'url_shortener_edit' );
    register_setting( 'section_list', 'url_shortener_edit_error' );
}

function print_section_options_info()
{
    print 'You can choose one of the following options to generate your short URL. 
    Then type the length you want it to be (counting from the \'/\' on).';
}

function print_section_generate_info()
{
    print 'You can generate your short URL here. Paste the long URL on the corresponding field and hit \'Generate\.';
}

function url_shortener_options_display ()
{
    ?>

        <table>
            <tr valign="top">
            <td><input type="radio" name="url_shortener_string_option" value="1" <?php checked(1, get_option('url_shortener_string_option'), true); ?> />Numeric string</td>
            </tr>
            
            <tr valign="top">
            <td><input type="radio" name="url_shortener_string_option" value="2" <?php checked(2, get_option('url_shortener_string_option'), true); ?> />Character string</td>
            </tr>

            <tr valign="top">
            <td><input type="radio" name="url_shortener_string_option" value="3" <?php checked(3, get_option('url_shortener_string_option'), true); ?> />Alphanumeric string</td>
            </tr>

            <tr valign="top">
                <td>
                    <label for="url_shortener_length">Length: </label>
                    <input id="url_length" type="text" name="url_shortener_length" value="<?php echo esc_attr( get_option('url_shortener_length') ); ?>" />
                    <label>(value between 1 and 10)</label>
                </td>
            </tr>
        </table>
    <?php
}

function urls_display ()
{
    ?>

    <table>
        <tr>
            <td>
                <label for="url_long">Long URL: </label>
                <input id="url_long" type="text" name="url_shortener_long_url" value="<?php echo esc_attr( get_option('url_shortener_long_url') ); ?>" />
                <input id="generate_short_url" type="submit" name="url_shortener_generate" class="button button-primary" value="generate">
                <output id="url_long_error" type="text" name="url_shortener_long_url_error" value="<?php echo esc_attr( get_option('url_shortener_long_url_error') ); ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label for="url_short">Short URL: </label>
                <output id="url_short" type="text" name="url_shortener_short_url" value="<?php echo esc_attr( get_option('url_shortener_short_url') ); ?>">
            </td>
        </tr>
    </table>
    <?php
}

function url_shortener_settings_page_display() 
{
    ?>
        <div class="wrap">
            <h1>URL Shortener Settings</h1>

            <form method="post" action="options.php">
                <?php do_settings_sections( 'options_page' ); ?>
                <?php settings_fields( 'section_options' ); ?>
                <?php submit_button(); ?>
            </form>
            
        </div>
    <?php
} 

function print_section_list_info()
{
    print 'You can see the URLs you have set up in the next list.';
}

function url_shortener_url_stats_display ()
{
    global $wpdb;
    $url_number=1;
    $table_name = $wpdb->prefix . 'url_shortener';
    $row = $wpdb->get_results( "SELECT * FROM $table_name");
    
    ?>

    <table>
        <tr> 
            <td><strong></strong></td>
            <td><strong>Short URL</strong></td>
            <td><strong></strong></td>
            <td><strong>Long URL</strong></td>
            <td><strong>Visits</strong></td>
            <td><strong>Time created</strong></td>
            <td><strong>Time last access</strong></td>
        </tr>
        <?php foreach ( $row as $row ) { ?>
            <tr> 
                <?php $url=parse_url($row->url_short); ?>
                <td><input id="short_url_edit_<?php echo $url_number?>" type="submit" name="url_shortener_edit" class="edit_short_url" value="Edit" 
                onclick="edit_url('<?php echo $url['scheme']?>','<?php echo $url['host'];?>','<?php echo substr($url['path'],1) ;?>','short_url_uri_<?php echo $url_number?>','short_url_edit_<?php echo $url_number?>','short_url_error_<?php echo $url_number?>','visit_<?php echo $url_number ?>',event)"></td>
                <td>
                    <span><?php echo $url['scheme'].'://'.$url['host'].'/';?></span>
                    <span id="short_url_uri_<?php echo $url_number?>" contenteditable="false" ><?php echo substr($url['path'],1) ;?></span>
                </td>
                <td>
                    <a id="visit_<?php echo $url_number ?>" href="<?php echo $row->url_short ?>" target="_blank">Visit</a>
                </td>
                <td>
                    <?php echo $row->url_long;?>
                </td>
                <td><?php echo $row->visits;?></td>
                <td><?php echo $row->time_created;?></td>
                <td><?php echo $row->time_last_access;?></td>
                <td><input type="submit" name="url_shortener_delete" class="delete_short_url" value="Delete" onclick="delete_url('<?php echo $row->url_short;?>')"></td>
                <td><output id="short_url_error_<?php echo $url_number?>" type="text" name="url_shortener_edit_error" class="edit_short_url" value="<?php echo esc_attr( get_option('url_shortener_edit_error') ); ?>" ></td>
            </tr>
            <?php $url_number++;} ?>
    </table>
    <?php   
}

function url_shortener_stats_page_display() 
{
    ?>
        <div class="wrap">
            <h1>URL List</h1>

            <form method="post" action="options.php">
                 
                <?php settings_fields( 'section_list' ); ?>
                <?php do_settings_sections( 'stats_page' ); ?>
            </form>
            
        </div>
    <?php
}


?>