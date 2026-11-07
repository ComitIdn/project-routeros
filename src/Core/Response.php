<?php

namespace RouterOSFramework\Core;

/**
 * Class Response
 * Standardized JSON API response formatter for Web Applications, REST APIs, and Bots.
 */
class Response
{
    /**
     * Build success response array.
     *
     * @param mixed $data
     * @param string $message
     * @param array $meta
     * @return array
     */
    public static function success($data = null, string $message = 'Success', array $meta = []): array
    {
        $response = [
            'status'    => 'success',
            'code'      => 200,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        return $response;
    }

    /**
     * Build error response array.
     *
     * @param string $message
     * @param int $code
     * @param mixed $details
     * @return array
     */
    public static function error(string $message, int $code = 400, $details = null): array
    {
        $response = [
            'status'    => 'error',
            'code'      => $code,
            'message'   => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        if ($details !== null) {
            $response['details'] = $details;
        }
        return $response;
    }

    /**
     * Paginated data structure.
     *
     * @param array $items
     * @param int $total
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function paginate(array $items, int $total, int $page = 1, int $perPage = 20): array
    {
        $totalPages = (int)ceil($total / max(1, $perPage));
        return self::success($items, 'Data retrieved successfully', [
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'total_pages'  => $totalPages,
                'has_next'     => $page < $totalPages,
                'has_prev'     => $page > 1,
            ]
        ]);
    }

    /**
     * Output standardized JSON response directly to HTTP client with appropriate headers.
     *
     * @param mixed $data
     * @param int $statusCode
     * @param string $message
     * @param array $meta
     */
    public static function json($data, int $statusCode = 200, string $message = 'OK', array $meta = []): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=UTF-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }

        $payload = ($statusCode >= 200 && $statusCode < 300)
            ? self::success($data, $message, $meta)
            : self::error($message, $statusCode, $data);

        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Get JSON encoded string without sending HTTP headers.
     *
     * @param mixed $data
     * @param int $statusCode
     * @param string $message
     * @return string
     */
    public static function toJson($data, int $statusCode = 200, string $message = 'OK'): string
    {
        $payload = ($statusCode >= 200 && $statusCode < 300)
            ? self::success($data, $message)
            : self::error($message, $statusCode, $data);

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
