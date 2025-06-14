<?php

namespace WP_Notion_Sync;

use WP_Notion_Sync\Posts;
use WP_Notion_Sync\Logger;

/**
 * Shortcode callback function for [wpns_test_shortcode]
 *
 * @return string The output for the shortcode.
 */
function wpns_test_shortcode_callback()
{
    $output = '';

    // Initiate Posts class;
    $query = new Posts();

    $posts = $query->get_posts();

    if ($posts) {
        Logger::log(json_encode($posts));

        foreach ($posts as $post) {
            $output .= '<pre>' . json_encode($post, JSON_PRETTY_PRINT) . '</pre>';
        }
    }

    return $output;
}

/**
 * Register the shortcode.
 * This function is hooked to 'init' to ensure it's registered at the correct time.
 */
function wpns_register_test_shortcode()
{
    add_shortcode('wpns_test_shortcode', 'WP_Notion_Sync\wpns_test_shortcode_callback'); // Full namespace for callback
}
// Hook the registration function
add_action('init', 'WP_Notion_Sync\wpns_register_test_shortcode');

// --- Example Usage ---

// Logger::log("<h3>Retrieving a Page:</h3>");
// try {
//     $database = $notion->queryDatabase($myDatabaseId, [
//         'property' => 'Status',
//         'select' => [
//             'equals' => 'draft'
//         ]
//     ]);
//     if ($database) {
//         $results = $database['results'];
//         if ($results) {

//             foreach ($results as $result) {

//                 $pageID = $result['id'];
//                 Logger::log(json_encode($pageID, JSON_PRETTY_PRINT));

//                 $pageData = $notion->getPage($pageID);
//                 $pageBlocks = $notion->getBlockChildren($pageID);

//                 if ($pageBlocks) {
//                     foreach ($pageBlocks['results'] as $block) {
//                         Logger::log("Block ID: " . $block['id'] . ", Type: " . $block['type'] . "\n");
//                         // Logger::log(json_encode($block, JSON_PRETTY_PRINT));
//                         if ($block['type'] === 'paragraph' && !empty($block['paragraph']['rich_text'])) {
//                             foreach ($block['paragraph']['rich_text'] as $text_part) {
//                                 // echo "  Content: " . $text_part['plain_text'] . "\n";
//                                 Logger::log("  Content: " . $text_part['plain_text'] . "\n");
//                             }
//                         }
//                         if ($block['type'] === 'code' && !empty($block['code']['rich_text'])) {
//                             foreach ($block['code']['rich_text'] as $text_part) {
//                                 // echo "  Content: " . $text_part['plain_text'] . "\n";
//                                 Logger::log("  Content: <pre>" . $text_part['plain_text'] . "</pre>\n");
//                             }
//                         }
//                     }
//                 }

//                 // if ($page) {
//                 //     Logger::log(json_encode($page, JSON_PRETTY_PRINT));
//                 // }
//             }
//         }
//     } else {
//         Logger::log("Page not found or error retrieving page.\n");
//     }
// } catch (\Exception $e) {
//     Logger::log("Error: " . $e->getMessage() . "\n");
// }
