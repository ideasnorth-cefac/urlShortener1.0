<?php
// Our custom post type function

add_action('init', function() 
{
    $current_theme=get_current_theme();
    $labels = array(
        'name' => _x('URLs List Posts', 'post type general name', $current_theme),
        'singular_name' => _x('urls_list_post', 'post type singular name', $current_theme),
        'menu_name' => _x('urls_list_posts', 'admin menu', $current_theme),
    );
    
    $args = array(
        'labels' => $labels,
        'description' => __('This is a urls list post type.', $current_theme),
        'menu_icon' => 'dashicons-admin-links',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'query_var' => false, //this was true
        'rewrite' => array('slug' => ''),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 110,
        'supports' => array('title', 'editor', 'author', 'thumbnail', 'custom-fields' )
    );
    register_post_type('url_shortener', $args);
});


function add_my_custom_page() 
{
    // Create post object
    $my_post = array(
      'post_title'    => wp_strip_all_tags( 'my first urls list' ),
      'post_content'  => '',
      'post_status'   => 'publish',
      'post_author'   => 1,
      'post_type'     => 'url_shortener'
    );

    wp_insert_post( $my_post);
}

function wpb_hook_javascript() 
{
    if(get_post_type() == 'url_shortener')
    { 
      ?>
          <script type="text/javascript">
            var requestOptions = {
                method: 'GET',
                redirect: 'follow'
                };
            var element = document.getElementsByClassName("entry-content");     
            var list=document.createElement("ol");
            list.id='#urls_list';

            function createDropdown(values, options, select_name,label_text)
            {
                var select = document.createElement("select");
                select.name = select_name;
                select.id = select_name;

                for (var i=0 ; i<values.length ; i++) 
                {
                    var option = document.createElement("option");
                    option.value = values[i];
                    option.text = options[i];
                    select.appendChild(option);
                }
                var label = document.createElement("label");
                label.innerHTML = label_text;
                label.htmlFor = select_name;
 
                document.getElementsByClassName("entry-content")[0].appendChild(label).appendChild(select);
            }

            function createButton(caption,id)
            {
                var button = document.createElement("button");   
                button.id=id;
                button.innerHTML = caption;                         
                document.getElementsByClassName("entry-content")[0].appendChild(button);
            }

            function parse_result(result)
            {
                var urls_array=JSON.parse(result);

                // var ol = getElementById('#urls_list'));
                while (list.firstChild) 
                    list.removeChild(list.firstChild);

                //load urls in the list
                for(var i=0 ; i<urls_array.length ; i++)
                {
                    var li = document.createElement("li");
                    list.appendChild(li);

                    var anchor = document.createElement("a");
                    anchor.href = urls_array[i];
                    anchor.innerHTML=urls_array[i];
                    li.appendChild(anchor);
                }
                element[0].appendChild(list);
            }

            function send_fetch(sort_type, sort_order)
            {
                fetch(document.location.origin+"/wp-json/urlShortener/v1/url-list?type="+sort_type+"&order="+sort_order, requestOptions)
                .then(response => response.text())
                .then(result => {parse_result(result);})
                .catch(error => console.log('error', error));
            }

            jQuery(document).ready(function($) 
            {
                //create drop-down menus
                createDropdown (values = ["visits", "time_created", "time_last_access"],options = ["Number of visits", "Creation date", "Recently visited"],'sort_type','Sort by: ');
                createDropdown (values = ["asc", "desc"],options = ["ascending", "descending"],'sort_order','');

                //create button
                createButton('Sort','sort');
                
                var sort_type='visits';
                var sort_order='asc';

                send_fetch(sort_type,sort_order);

                $('#sort').on('click', function(e) 
                {
                    e.preventDefault();
                
                    //retrieve drop-down info
                    var select_type = $('#sort_type');
                    var select_order = $('#sort_order');
                    
                    sort_type=select_type[0].options[select_type[0].selectedIndex].value;
                    sort_order=select_order[0].options[select_order[0].selectedIndex].value;

                    console.log(sort_type);
                    console.log(sort_order);

                    send_fetch(sort_type,sort_order);

                });


            });
          </script>
      <?php
    }
}
add_action('wp_head', 'wpb_hook_javascript');

?>