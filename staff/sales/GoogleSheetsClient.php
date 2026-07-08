<?php

class GoogleSheetsClient {
    private $keyFile;
    private $accessToken;

    public function __construct($keyFile) {
        $this->keyFile = $keyFile;
    }

    /**
     * Authenticate and get Access Token using JWT
     */
    private function authenticate() {
        if (!file_exists($this->keyFile)) {
            throw new Exception("Google Service Account Key file not found: " . $this->keyFile);
        }

        $keyData = json_decode(file_get_contents($this->keyFile), true);
        if (!$keyData || !isset($keyData['private_key']) || !isset($keyData['client_email'])) {
            throw new Exception("Invalid Google Service Account Key format.");
        }

        $privateKey = $keyData['private_key'];
        $clientEmail = $keyData['client_email'];

        // Build JWT Claim Set
        $now = time();
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $claimSet = json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlClaimSet = $this->base64UrlEncode($claimSet);

        $signatureInput = $base64UrlHeader . '.' . $base64UrlClaimSet;
        $signature = '';

        if (!openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception("Failed to sign JWT assertion using OpenSSL.");
        }

        $base64UrlSignature = $this->base64UrlEncode($signature);
        $assertion = $signatureInput . '.' . $base64UrlSignature;

        // POST request to get Access Token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion
        ]));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("CURL Error obtaining token: " . $err);
        }

        $responseData = json_decode($response, true);
        if (isset($responseData['error'])) {
            throw new Exception("Google API Auth Error: " . ($responseData['error_description'] ?? $responseData['error']));
        }

        if (!isset($responseData['access_token'])) {
            throw new Exception("Access token not found in Google response: " . $response);
        }

        $this->accessToken = $responseData['access_token'];
    }

    public function getAccessToken() {
        if (!$this->accessToken) {
            $this->authenticate();
        }
        return $this->accessToken;
    }

    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Clear a range of values in Google Sheet
     */
    public function clearValues($spreadsheetId, $range) {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        $url = "https://sheets.googleapis.com/v4/spreadsheets/" . urlencode($spreadsheetId) . "/values/" . urlencode($range) . ":clear";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->accessToken,
            "Content-Length: 0"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("CURL Error clearing values: " . $err);
        }

        if ($httpCode !== 200) {
            throw new Exception("Failed to clear range. Google API responded with code $httpCode: " . $response);
        }

        return json_decode($response, true);
    }

    /**
     * Update values at a specific range
     */
    public function updateValues($spreadsheetId, $range, $values) {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        $url = "https://sheets.googleapis.com/v4/spreadsheets/" . urlencode($spreadsheetId) . "/values/" . urlencode($range) . "?valueInputOption=USER_ENTERED";

        $payload = json_encode([
            'range' => $range,
            'majorDimension' => 'ROWS',
            'values' => $values
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->accessToken,
            "Content-Type: application/json",
            "Content-Length: " . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("CURL Error updating values: " . $err);
        }

        if ($httpCode !== 200) {
            throw new Exception("Failed to update range. Google API responded with code $httpCode: " . $response);
        }

        return json_decode($response, true);
    }

    /**
     * Append values to a sheet
     */
    public function appendValues($spreadsheetId, $range, $values) {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        $url = "https://sheets.googleapis.com/v4/spreadsheets/" . urlencode($spreadsheetId) . "/values/" . urlencode($range) . ":append?valueInputOption=USER_ENTERED";

        $payload = json_encode([
            'majorDimension' => 'ROWS',
            'values' => $values
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->accessToken,
            "Content-Type: application/json",
            "Content-Length: " . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("CURL Error appending values: " . $err);
        }

        if ($httpCode !== 200) {
            throw new Exception("Failed to append range. Google API responded with code $httpCode: " . $response);
        }

        return json_decode($response, true);
    }
}
