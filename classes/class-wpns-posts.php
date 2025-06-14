<?php

namespace WP_Notion_Sync;

use WP_Notion_Sync\Notion_API;
use WP_Notion_Sync\BlockConverter;

class Posts
{

    private array $posts;
    private $blockCovertor;
    private string $databaseID;
    private $notion;

    public function __construct()
    {
        $this->notion = new Notion_API();
        $this->blockCovertor = new Block_Converter();

        $wpns_options = get_option('wpns_options');
        $this->databaseID = $wpns_options['notion_database_id'];
    }

    public function get_posts(array $filter = [], array $sorts = [])
    {
        $data = [];

        // Define the base filter that is always applied.
        // This is a single condition.
        $baseFilterCondition = [
            'property' => 'Type',
            'select' => [
                'equals' => 'Post'
            ]
        ];

        // Start with the base filter condition as the first element in our 'and' conditions array.
        $andConditions = [$baseFilterCondition];

        // If additional filters are provided by the caller, merge them as 'and' conditions.
        if (!empty($filter)) {
            // If the incoming filter is already a composite 'and' filter,
            // merge its conditions into our main 'and' array.
            if (isset($filter['and']) && is_array($filter['and'])) {
                $andConditions = array_merge($andConditions, $filter['and']);
            }
            // If the incoming filter is a composite 'or' filter,
            // add it as a single element to our main 'and' array.
            // This means: (Base Condition) AND (Incoming OR Condition)
            elseif (isset($filter['or']) && is_array($filter['or'])) {
                $andConditions[] = $filter;
            }
            // If it's a simple property filter (e.g., ['property' => 'Status', 'select' => ['equals' => 'Draft']]),
            // add it as a new condition to our main 'and' array.
            else {
                $andConditions[] = $filter;
            }
        }

        // Always wrap all conditions in an 'and' property for the Notion API.
        // This guarantees the filter object sent to Notion is always valid.
        $data['filter'] = ['and' => $andConditions];

        // Add sorts if provided
        if (!empty($sorts)) {
            $data['sorts'] = $sorts;
        }

        $this->posts = $this->notion->queryDatabase($this->databaseID, $data);

        $postsData = [];

        foreach ($this->posts['results'] as $data) {
            $postsData[] = $this->format_post_data($data);
        }

        return $postsData;
    }

    public function format_post_data($data)
    {

        if (!$data) {
            return;
        }

        $props = $data['properties'];
        $postID = $data['id'];

        // Categories
        $cats = [];
        foreach ($props['Category']['multi_select'] as $cat) {
            $cats[] = $cat['name'];
        }

        $newData = [
            'post_type'     => strtolower($props['Type']['select']['name']),
            'post_date'     => $props['Created time']['created_time'],
            'post_modified' => $props['Last edited']['last_edited_time'],
            'post_title'    => $props['Name']['title'][0]['plain_text'],
            'post_status'   => $props['Status']['select']['name'],
            'cat'           => $cats,
            'content'       => $this->get_post_content_data($postID),
        ];


        return $newData;
    }

    public function get_post_content_data(string $pageID)
    {

        if (!$pageID) {
            return;
        }

        $postData = $this->notion->getBlockChildren($pageID);

        $formattedData = [];

        foreach ($postData['results'] as $key => $postData) {
            $blockType = $postData['type'];

            switch ($blockType) {
                case 'paragraph':
                    $formattedData[] = $this->blockCovertor->Paragraph($postData);
                    break;
                // Add more cases here for other Notion block types (heading_1, bulleted_list_item, etc.)
                // case 'heading_1':
                //     $wordpress_content .= $block_converter->convertHeadingOneBlock($block);
                //     break;
                case 'code':
                    $formattedData[] = $this->blockCovertor->Code($postData);
                    break;
                default:
                    // Handle unsupported block types or log them
                    error_log('Unsupported Notion block type: ' . $blockType);
                    break;
            }
        }

        return $formattedData;
    }

    public function get_post_category_data(string $data) {}
}
