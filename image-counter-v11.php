<?php
/*
Plugin Name: WordPress Image Counter
Plugin URI: https://qc-example.com/
Description: This plugin counts images by file type and size.
Version: 11.0
Author: Philani Gumbo
Author URI: https://qc-example.com
*/

// Hook into admin menu
add_action('admin_menu', 'image_counter_menu');

function image_counter_menu()
{
    add_menu_page('Image Counter', 'Image Counter', 'manage_options', 'image-counter', 'image_counter_page');
}

function image_counter_page()
{
    echo "<h2>" . esc_html__('WordPress Image Counter', 'image-counter') . "</h2>";

    // Check if the form is submitted
    if (isset($_POST['image_counter_form'])) {
        // Update excluded directories
        update_option('excluded_directories', $_POST['excluded_directories']);
    }

    // Clear excluded directories
    if (isset($_POST['clear_excluded_directories'])) {
        update_option('excluded_directories', array());
    }

    // Get excluded directories
    $excluded_directories = get_option('excluded_directories', array());

    // List all folders in the WP_CONTENT_DIR directory
    echo "<h5>The WordPress image counter plugin will check for images in the following directories:</h5>";
    $content_folders = list_folders(WP_CONTENT_DIR, $excluded_directories);
    echo "<ul>";
    foreach ($content_folders as $folder) {
        echo "<li>$folder</li>";
    }
    echo "</ul>";

    // Display form for excluding directories
    echo "<h5>Exclude Directories:</h5>";
    echo "<form method='post'>";
    echo "<select class='form-control' name='excluded_directories[]' multiple>";
    foreach (list_folders(WP_CONTENT_DIR) as $folder) {
        $selected = in_array($folder, $excluded_directories) ? 'selected' : '';
        echo "<option value='$folder' $selected>$folder</option>";
    }
    echo "</select>";
    echo "<br>";
    echo "<table>";
    echo "<tr>";
    echo "<td>";
    echo "<input type='submit' class='btn btn-block btn-success' name='image_counter_form' value='Save Excluded Directories'>";
    echo "</td>";
    echo "<td>";
    echo "<input type='submit' class='btn btn-block ml-2 btn-danger' name='clear_excluded_directories' value='Clear Excluded Directories'>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";
    echo "</form>";
    echo "<br>";

    // Check if the button is clicked
    if (isset($_POST['image_count_button'])) {
        // Count images by file type
        $image_count_by_type = count_images_by_type($excluded_directories);
    } else {
        // Initialize counts
        $image_count_by_type = ["Click the 'Check Image Count' button to start"];
    }

    // Enqueue Bootstrap stylesheet
    echo '<style>';
    echo file_get_contents('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
    echo '</style>';

    // Display results and button
    echo "<pre>";
    echo esc_html($image_count_by_type[0]);
    echo "</pre>";

    // Display the button
    echo '<form method="post">';
    echo '<input type="submit" name="image_count_button" value="' . esc_attr__('Check Image Count', 'image-counter') . '" class="btn btn-primary">';
    echo '</form>';
}

// Function to list all folders in a directory
function list_folders($dir, $excluded_directories = array()){
    $folders = array();

    // Open a directory, and read its contents
    if (is_dir($dir)){
        if ($dh = opendir($dir)){
            while (($file = readdir($dh)) !== false){
                if ($file != "." && $file != ".." && is_dir($dir.'/'.$file) && !in_array($file, $excluded_directories)){
                    $folders[] = $file;
                }
            }
            closedir($dh);
        }
    }

    return $folders;
}

function count_images_by_type($excluded_directories)
{
    function countImages($dir, $excluded_directories)
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $imageCount = 0;
        $imagePaths = [];

        // Open the directory
        if ($handle = opendir($dir)) {
            // Loop through the directory
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $filePath = $dir . '/' . $file;
                    if (is_dir($filePath) && !in_array($file, $excluded_directories)) {
                        // If it's a directory and not excluded, recursively count images inside
                        $subDirImageCount = countImages($filePath, $excluded_directories);
                        $imageCount += $subDirImageCount['count'];
                        $imagePaths = array_merge($imagePaths, $subDirImageCount['paths']);
                    } else {
                        // Check if the file has an image extension
                        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                        if (in_array(strtolower($fileExtension), $imageExtensions)) {
                            $imageCount++;
                            $imagePaths[] = $filePath;
                        }
                    }
                }
            }
            closedir($handle);
        }

        return ['count' => $imageCount, 'paths' => $imagePaths];
    }

    $directory = WP_CONTENT_DIR; // Change this to your directory path
    $result = countImages($directory, $excluded_directories);

    echo "<html>";
    echo "<head>";
    // Enqueue Bootstrap stylesheet
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
    echo "</head>";
    echo "<body>";
    echo "<h4 class='text-success'>" . sprintf(esc_html__('Number of images found: %d', 'image-counter'), $result['count']) . "<h4>";
    echo "<br>";
    echo "<table class='table table-bordered'>";
    echo "<thead class='thead-dark'>";
    echo "<tr><td><h6>" . esc_html__('Discovered image paths', 'image-counter') . "</h6></td></tr>";
    echo "</thead>";
    echo "<tbody>";

    // Display images for the current page
    foreach ($result['paths'] as $path) {
        echo "<tr>";
        echo "<td>";
        echo "<h6><small>" . esc_html($path) . "</small></h6>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";

    echo "</body>";
    echo "</html>";

}
?>
