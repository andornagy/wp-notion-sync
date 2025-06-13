<?php

namespace WP_Notion_Sync;

// Ensure this file is called directly.
if (! defined('ABSPATH')) {
    exit;
}

class Logger
{
    public static function log($message, $data = [])
    {
        $log_path = defined('JLD_COFFEE_LOG_PATH') ? WP_NOTION_SYNC_LOG_PATH : __DIR__ . '/../logs/debug.log';

        if (! file_exists(dirname($log_path))) {
            mkdir(dirname($log_path), 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $output = "[$timestamp] $message";

        if (! empty($data)) {
            $output .= ' | ' . print_r($data, true);
        }

        file_put_contents($log_path, $output . "\n", FILE_APPEND);
    }
}
