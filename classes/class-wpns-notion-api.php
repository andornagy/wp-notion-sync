<?php

namespace WP_Notion_Sync;

// Ensure this file is called directly.
if (! defined('ABSPATH')) {
    exit;
}

class Notion_API
{
    private string $apiToken;
    private string $apiVersion;
    private string $baseUrl;

    /**
     * Constructor for the NotionApi class.
     *
     * @param string $apiToken Your Notion Integration Token (starts with 'secret_').
     * @param string $apiVersion The Notion API version to use (e.g., '2022-06-28').
     */
    public function __construct(string $apiToken, string $apiVersion = '2022-06-28')
    {
        $this->apiToken = $apiToken;
        $this->apiVersion = $apiVersion;
        $this->baseUrl = 'https://api.notion.com/v1/';
    }

    /**
     * Makes a cURL request to the Notion API.
     *
     * @param string $endpoint The API endpoint (e.g., 'pages', 'databases/{id}/query').
     * @param string $method The HTTP method (GET, POST, PATCH).
     * @param array $data The data payload for POST/PATCH requests (optional).
     * @return array|null Decoded JSON response or null on failure.
     * @throws Exception If the API request fails or returns an error.
     */
    private function _request(string $endpoint, string $method = 'GET', array $data = []): ?array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiToken,
            'Notion-Version: ' . $this->apiVersion,
            'Content-Type: application/json' // Essential for sending JSON payloads
        ]);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'GET':
                // GET requests don't typically have a body
                break;
            default:
                throw new \Exception("Unsupported HTTP method: " . $method);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("cURL error: " . $error);
        }

        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        // Basic error handling for Notion API responses
        if ($httpCode >= 400 || (isset($decodedResponse['object']) && $decodedResponse['object'] === 'error')) {
            $errorMsg = $decodedResponse['message'] ?? 'Unknown Notion API error';
            throw new \Exception("Notion API Error ({$httpCode}): " . $errorMsg);
        }

        return $decodedResponse;
    }

    /**
     * Retrieves a specific Notion page.
     *
     * @param string $pageId The ID of the page to retrieve.
     * @return array|null The page object.
     * @throws Exception
     */
    public function getPage(string $pageId): ?array
    {
        return $this->_request("pages/{$pageId}", 'GET');
    }

    /**
     * Retrieves the children blocks of a Notion block (e.g., a page).
     *
     * @param string $blockId The ID of the block whose children to retrieve.
     * @return array|null An array of block objects.
     * @throws Exception
     */
    public function getBlockChildren(string $blockId): ?array
    {
        return $this->_request("blocks/{$blockId}/children", 'GET');
    }

    /**
     * Queries a Notion database.
     *
     * @param string $databaseId The ID of the database to query.
     * @param array $filter An optional filter object for the query (e.g., ['property' => 'Status', 'select' => ['equals' => 'Done']]).
     * @param array $sorts An optional array of sort objects.
     * @return array|null An array of page objects matching the query.
     * @throws Exception
     */
    public function queryDatabase(string $databaseId, array $filter = [], array $sorts = []): ?array
    {
        $data = [];
        if (!empty($filter)) {
            $data['filter'] = $filter;
        }
        if (!empty($sorts)) {
            $data['sorts'] = $sorts;
        }
        return $this->_request("databases/{$databaseId}/query", 'POST', $data);
    }
}
