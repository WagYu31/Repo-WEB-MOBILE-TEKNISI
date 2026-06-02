<?php
require_once 'config.php';

function callAccurateAPI(
    string $endpoint,
    string $method = 'GET',
    array $data = [],
    bool $isBusinessData = false
): array {
    $baseUrl = $isBusinessData
        ? ($_SESSION['accurate_host'] ?? API_ACCURATE_URL)
        : API_ACCOUNT_URL;

    $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

    $headers = [
        'Authorization: Bearer ' . ($_SESSION['accurate_access_token'] ?? ''),
        'Accept: application/json'
    ];

    if ($isBusinessData && !empty($_SESSION['accurate_session'])) {
        $headers[] = 'X-Session-ID: ' . $_SESSION['accurate_session'];
    }

    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HEADER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_FOLLOWLOCATION => true
    ];

    if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
        $encodedData = json_encode($data);
        $options[CURLOPT_POSTFIELDS] = $encodedData;
        $headers[] = 'Content-Type: application/json';

        file_put_contents('api_debug.log',
            "\n---\nURL: $url\nMethod: $method\nRequest Headers:\n" . print_r($headers, true) . "\nRequest Body (JSON Encoded):\n" . $encodedData . "\n---\n",
            FILE_APPEND
        );
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $responseHeaders = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    curl_close($ch);

    file_put_contents('api_debug.log',
        "URL: $url\nMethod: $method\nStatus: $httpCode\nResponse Headers:\n" . print_r(explode("\r\n", $responseHeaders), true) . "\nResponse Body:\n$body\nError: $error\n\n",
        FILE_APPEND
    );

    if ($response === false) {
        return [
            'success' => false,
            'code' => $httpCode,
            'error' => $error ?: 'Empty response',
            'raw' => $response
        ];
    }

    $contentType = '';
    foreach (explode("\r\n", $responseHeaders) as $header) {
        if (stripos($header, 'Content-Type:') === 0) {
            $contentType = trim(substr($header, strlen('Content-Type:')));
            break;
        }
    }

    if (stripos($contentType, 'application/json') !== false) {
        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'code' => $httpCode,
                'error' => 'Invalid JSON: ' . json_last_error_msg(),
                'raw' => $body,
                'content_type' => $contentType
            ];
        }
    } else {
        return [
            'success' => false,
            'code' => $httpCode,
            'error' => 'Expected application/json, but received: ' . $contentType,
            'raw' => $body,
            'content_type' => $contentType
        ];
    }

    if (isset($decoded['error']) && (stripos($decoded['error'], 'expired') !== false || stripos($decoded['error_description'], 'expired') !== false)) {
        $refreshResponse = refreshToken();
        if (!$refreshResponse['success']) {
            return $refreshResponse;
        }
        return callAccurateAPI($endpoint, $method, $data, $isBusinessData);
    }

    if (isset($decoded['s']) && $decoded['s'] === false && isset($decoded['er'])) {
        return [
            'success' => false,
            'code' => $httpCode,
            'error' => $decoded['er'],
            'raw' => $body,
            'content_type' => $contentType,
            'full_response' => $decoded
        ];
    } elseif (isset($decoded['error'])) {
        return [
            'success' => false,
            'code' => $httpCode,
            'error' => $decoded['error_description'] ?? $decoded['error'],
            'raw' => $body,
            'content_type' => $contentType,
            'full_response' => $decoded
        ];
    }

    return [
        'success' => true,
        'code' => $httpCode,
        'data' => $decoded,
        'headers' => $responseHeaders,
        'content_type' => $contentType
    ];
}

function refreshToken(): array {
    if (empty($_SESSION['accurate_refresh_token'])) {
        return ['success' => false, 'error' => 'No refresh token available'];
    }

    $ch = curl_init(TOKEN_ENDPOINT);
    $params = [
        'grant_type' => 'refresh_token',
        'refresh_token' => $_SESSION['accurate_refresh_token']
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . getAuthorizationHeader(),
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HEADER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $responseHeaders = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    file_put_contents('api_debug.log',
        "\n---REFRESH TOKEN RESPONSE---\nURL: " . TOKEN_ENDPOINT . "\nStatus: $httpCode\nRequest Body:\n" . print_r($params, true) . "\nResponse Headers:\n" . print_r(explode("\r\n", $responseHeaders), true) . "\nResponse Body:\n$body\nError: $error\n---\n",
        FILE_APPEND
    );

    curl_close($ch);

    if (!$response) {
        return ['success' => false, 'error' => 'Empty refresh response'];
    }

    $decoded = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid JSON response: ' . json_last_error_msg(), 'raw' => $body];
    }

    if (isset($decoded['error'])) {
        return ['success' => false, 'error' => $decoded['error_description']];
    }

    $_SESSION['accurate_access_token'] = $decoded['access_token'];
    $_SESSION['accurate_refresh_token'] = $decoded['refresh_token'] ?? $_SESSION['accurate_refresh_token'];

    return ['success' => true];
}

function getAccessToken(string $code): array {
    $ch = curl_init(TOKEN_ENDPOINT);

    $params = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => REDIRECT_URI
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . getAuthorizationHeader(),
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HEADER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $responseHeaders = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    file_put_contents('api_debug.log',
        "\n---GET ACCESS TOKEN RESPONSE---\nURL: " . TOKEN_ENDPOINT . "\nStatus: $httpCode\nRequest Body:\n" . print_r($params, true) . "\nResponse Headers:\n" . print_r(explode("\r\n", $responseHeaders), true) . "\nResponse Body:\n$body\nError: $error\n---\n",
        FILE_APPEND
    );

    curl_close($ch);

    if (!$response) {
        return ['success' => false, 'error' => 'Empty token response'];
    }

    $decoded = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid JSON response: ' . json_last_error_msg(), 'raw' => $body];
    }

    if (isset($decoded['error'])) {
        return ['success' => false, 'error' => $decoded['error_description']];
    }

    return ['success' => true, 'data' => $decoded];
}


function openDatabase(int $dbId): array {
    $response = callAccurateAPI('/open-db.do?id=' . $dbId);

    if ($response['success'] && isset($response['data']['session'])) {
        $_SESSION['accurate_session'] = $response['data']['session'];
        $_SESSION['accurate_host'] = $response['data']['host'];
        $_SESSION['accurate_db_id'] = $dbId;
        $_SESSION['accurate_db_alias'] = $response['data']['alias'] ?? 'Database ' . $dbId;
    }

    return $response;
}

function getDatabaseList(): array {
    return callAccurateAPI('/db-list.do');
}

function getCustomers(int $page = 1, string $search = ''): array {
    $params = [
        'sp.page' => $page,
        'sp.pageSize' => ITEMS_PER_PAGE,
        'sp.sort' => 'name|asc'
    ];

    if (!empty($search)) {
        $params['filter.keywords'] = $search;
        $params['filter.keywords.op'] = 'CONTAIN';
    }

    $response = callAccurateAPI(
        '/customer/list.do?' . http_build_query($params),
        'GET',
        [],
        true
    );

    return $response;
}

function getCustomerDetail($id): array {
    $response = callAccurateAPI(
        '/customer/detail.do?id=' . urlencode($id),
        'GET',
        [],
        true
    );

    return $response;
}

function addCustomer($customerData)
{
    if (empty($_SESSION['accurate_access_token']) || empty($_SESSION['accurate_session']) || empty($_SESSION['accurate_host'])) {
        return ['success' => false, 'error' => 'Session data missing. Please re-login or re-open database.'];
    }

    $accessToken = $_SESSION['accurate_access_token'];
    $sessionId = $_SESSION['accurate_session'];
    $baseUrl = $_SESSION['accurate_host'];

    $url = rtrim($baseUrl, '/') . '/customer/save.do';

    $headers = [
        "Authorization: Bearer $accessToken",
        "X-Session-ID: $sessionId",
        "Content-Type: application/json"
    ];

    $postData = json_encode($customerData);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);
    file_put_contents('debug_add_customer.log',
        "POST Data:\n" . print_r($customerData, true) .
        "\nHTTP Code: $httpCode\nRequest Headers:\n" . print_r($headers, true) .
        "\nResponse Headers:\n" . print_r(explode("\r\n", $responseHeaders), true) .
        "\nResponse Body:\n$responseBody\nCurl Error:\n$curlError\n",
        FILE_APPEND
    );

    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'error' => "cURL Error: $curlError"];
    }

    $result = json_decode($responseBody, true);

    if ($httpCode == 200 && isset($result['s']) && $result['s'] === true) {
        return ['success' => true, 'data' => $result];
    } else {
        $errorMessage = $result['errorDescription'] ?? ($result['d'] ?? 'Unknown error occurred');
        return ['success' => false, 'error' => $errorMessage, 'raw' => $responseBody, 'code' => $httpCode];
    }
}


function formatDate(?string $date): string {
    if (empty($date)) return '-';
    try {
        return (new DateTime($date))->format('d/m/Y H:i');
    } catch (Exception $e) {
        return $date;
    }
}

function formatPhone(?string $phone): string {
    if (empty($phone)) return '-';
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1-$2-$3', $phone);
}

function displayValue($value, int $maxLength = null): string {
    if (is_null($value)) return '-';
    if (is_array($value)) $value = implode(', ', $value);

    $value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    return $maxLength ? mb_substr($value, 0, $maxLength, 'UTF-8') . '...' : $value;
}
?>