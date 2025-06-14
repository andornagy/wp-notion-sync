<?php

namespace WP_Notion_Sync;

class Block_Converter
{
    public function __construct() {}

    private function RichText(array $dataArray): string
    {

        $html = '';

        foreach ($dataArray as $data) {
            $content = $data['text']['content'] ?? '';
            $annotations = $data['annotations'] ?? [];
            $link = $data['text']['link'] ?? null;

            // Start with the raw content
            $formatted_content = esc_html($content); // Escape HTML to prevent XSS from Notion content

            // Apply formatting based on annotations
            if (isset($annotations['bold']) && $annotations['bold']) {
                $formatted_content = '<strong>' . $formatted_content . '</strong>';
            }
            if (isset($annotations['italic']) && $annotations['italic']) {
                $formatted_content = '<em>' . $formatted_content . '</em>';
            }
            if (isset($annotations['strikethrough']) && $annotations['strikethrough']) {
                $formatted_content = '<s>' . $formatted_content . '</s>'; // Or <del>
            }
            if (isset($annotations['underline']) && $annotations['underline']) {
                $formatted_content = '<u>' . $formatted_content . '</u>';
            }
            if (isset($annotations['code']) && $annotations['code']) {
                $formatted_content = '<code>' . $formatted_content . '</code>';
            }
            // Notion colors are complex and best handled via CSS classes if needed,
            // as inline styles can be problematic in WP. For simplicity, we omit direct color conversion here.
            // If you need color, you'd add:
            // if (isset($annotations['color']) && $annotations['color'] !== 'default') {
            //     $formatted_content = '<span style="color:' . esc_attr($annotations['color']) . ';">' . $formatted_content . '</span>';
            // }


            // Apply link if present
            if ($link && isset($link['url'])) {
                $formatted_content = '<a href="' . esc_url($link['url']) . '">' . $formatted_content . '</a>';
            }

            $html .= $formatted_content;
        }

        return $html;
    }

    public function Paragraph($notionBlock)
    {
        if (!isset($notionBlock['paragraph']['rich_text']) || !is_array($notionBlock['paragraph']['rich_text'])) {
            Logger::log('Notion paragraph block missing rich_text property.', 'warning');
            return '<p></p>'; // Return an empty paragraph block
        }

        $rich_text_html = $this->RichText($notionBlock['paragraph']['rich_text']);

        // Wrap in a <p> tag for standard paragraph content
        $paragraph_html = '<p>' . $rich_text_html . '</p>';

        // Wrap in Gutenberg block comments
        $gutenberg_block = '' . $paragraph_html . '';

        return $gutenberg_block;
    }

    public function Code($notionBlock)
    {
        if (!isset($notionBlock['code']['rich_text']) || !is_array($notionBlock['code']['rich_text'])) {
            Logger::log('Notion code block missing rich_text property.', 'warning');
            return '<p></p>'; // Return an empty code block
        }

        $rich_text_html = $this->RichText($notionBlock['code']['rich_text']);

        // Wrap in a <p> tag for standard paragraph content
        $paragraph_html = '<pre class="wp-block-code"><code>' . $rich_text_html . '</code></pre>';

        // Wrap in Gutenberg block comments
        $gutenberg_block = '' . $paragraph_html . '';

        return $gutenberg_block;
    }
}
