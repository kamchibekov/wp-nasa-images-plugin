<?php

add_action('admin_menu', 'nasa_images_add_plugin_page');

function nasa_images_add_plugin_page()
{
    add_menu_page(
        'Nasa Images', // page_title
        'Nasa Images', // menu_title
        'manage_options', // capability
        'nasa-images', // menu_slug
        'nasa_images_create_admin_page', // function
        'dashicons-admin-generic', // icon_url
    );
}

function nasa_images_pull_task_func()
{
    $api_key = get_transient('nasa_images_api');
    if ($api_key) {
        $image = Requests::request('https://api.nasa.gov/planetary/apod', null, [
            'api_key' =>  $api_key,
            'date' => date('Y-m-d', strtotime('now'))
        ]);

        $post = json_decode($image->body, true);
        // Create post object
        $my_post = array(
            'post_title'    => wp_strip_all_tags($post['title']) . ' by ' . $post['copyright'],
            'post_content'  => $post['explanation'],
            'post_status'   => 'publish',
            'post_date'     => $post['date'],
            'post_type'     => 'post-nasa-gallery'
        );

        // Insert the post into the database
        $post_id = wp_insert_post($my_post);

        // set featured image
        setFeaturedImage($post_id, $post['url']);
    } else {
        wp_clear_scheduled_hook('nasa_images_pull_task');
    }
}

function startDailyCron()
{
    if (!wp_next_scheduled('nasa_images_pull_task')) {
        wp_schedule_event(time(), 'daily', 'nasa_images_pull_task');
    }
    add_action('nasa_images_pull_task', 'nasa_images_pull_task_func');
}

function setFeaturedImage($post_id, $url)
{
    // Add Featured Image to Post
    $image_name       = 'wp-nasa-image.jpg';
    $upload_dir       = wp_upload_dir(); // Set upload folder
    $image_data       = file_get_contents($url); // Get image data
    $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
    $filename         = basename($unique_file_name); // Create image file name

    // Check folder permission and define file location
    if (wp_mkdir_p($upload_dir['path'])) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }

    // Create the image  file on the server
    file_put_contents($file, $image_data);

    // Check image file type
    $wp_filetype = wp_check_filetype($filename, null);

    // Set attachment data
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name($filename),
        'post_status'    => 'inherit'
    );

    // Create the attachment
    $attach_id = wp_insert_attachment($attachment, $file, $post_id);

    // Include image.php
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Define attachment metadata
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);

    // Assign metadata to attachment
    wp_update_attachment_metadata($attach_id, $attach_data);

    // And finally assign featured image to post
    set_post_thumbnail($post_id, $attach_id);
}

function load5images($api_key)
{
    if ($api_key) {
        $images[] = Requests::request('https://api.nasa.gov/planetary/apod', null, [
            'api_key' => $api_key,
            'date' => date('Y-m-d', strtotime('now'))
        ]);
        $images[] = Requests::request('https://api.nasa.gov/planetary/apod', null, [
            'api_key' => $api_key,
            'date' => date('Y-m-d', strtotime('-1 day'))
        ]);
        $images[] = Requests::request('https://api.nasa.gov/planetary/apod', null, [
            'api_key' => $api_key,
            'date' => date('Y-m-d', strtotime('-2 days'))
        ]);
        $images[] = Requests::request('https://api.nasa.gov/planetary/apod', null, [
            'api_key' => $api_key,
            'date' => date('Y-m-d', strtotime('-3 days'))
        ]);
        $images[] = Requests::request('https://api.nasa.gov/planetary/apod', null, [
            'api_key' => $api_key,
            'date' => date('Y-m-d', strtotime('-4 days'))
        ]);

        foreach ($images as $image) {

            $post = json_decode($image->body, true);
            // Create post object
            $my_post = array(
                'post_title'    => wp_strip_all_tags($post['title']) . ' by ' . $post['copyright'],
                'post_content'  => $post['explanation'],
                'post_status'   => 'publish',
                'post_date'     => $post['date'],
                'post_type'     => 'post-nasa-gallery'
            );

            // Insert the post into the database
            $post_id = wp_insert_post($my_post);

            // set featured image
            setFeaturedImage($post_id, $post['url']);
        }
    }
}


function nasa_images_create_admin_page()
{
    $nasa_images_api = get_transient('nasa_images_api'); ?>

    <div class="wrap">
        <h2>Nasa Images</h2>
        <p>Please upload your Nasa Images API key at first</p>

        <?php if (isset($_POST['nasa_images_api'])) :

            if (!get_option('nasa-images-5loaded')) {
                load5images($_POST['nasa_images_api']);
                add_option('nasa-images-5loaded', true);
            }
            if (!wp_next_scheduled('nasa_images_pull_task')) {
                startDailyCron();
            }

            $nasa_images_api = esc_sql($_POST['nasa_images_api']);
            set_transient('nasa_images_api', $nasa_images_api, 60 * 60 * 24 * 90)
        ?>
            <div class="notice notice-success">
                <p> Api Key successfully saved </p>
            </div>
        <?php endif ?>

        <form method="post">
            <table class="form-table">
                <tbody>
                    <th scope="row">
                        <label for="nasa-images-api">API Key: </label>
                    </th>
                    <td>
                        <input type="text" value="<?php echo $nasa_images_api; ?>" id="nasa-images-api" name="nasa_images_api">
                    </td>
                </tbody>
            </table>
            <?php
            submit_button();
            ?>
        </form>
    </div>
<?php }
