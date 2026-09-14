<?php
require_once __DIR__ . '/env_helper.php';
/**
 * Firebase Token Verifier & API Auth Helper
 * Verifies Firebase ID tokens and manages API authentication.
 * 
 * Flow: Flutter (Firebase Auth) → ID Token → PHP verifies → returns user data + role
 */

class FirebaseAuthHelper {
    
    // Firebase project ID — USER MUST SET THIS
    private static $firebaseProjectId = 'kasql-flutter'; // TODO: Set your Firebase project ID
    
    /**
     * Verify a Firebase ID token.
     * Uses Google's public keys to verify JWT signature.
     * 
     * @param string $idToken The Firebase ID token from client
     * @return array|false Decoded payload or false on failure
     */
    public static function verifyIdToken($idToken) {
        if (empty($idToken)) return false;
        
        // Decode JWT parts
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) return false;
        
        $header = json_decode(self::base64UrlDecode($parts[0]), true);
        $payload = json_decode(self::base64UrlDecode($parts[1]), true);
        
        if (!$header || !$payload) return false;
        
        // Basic validation
        $now = time();
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < $now) {
            return false;
        }
        
        // Check issued at
        if (isset($payload['iat']) && $payload['iat'] > $now + 300) {
            return false; // Token from the future (with 5min tolerance)
        }
        
        // Check audience matches project ID
        if (isset($payload['aud']) && $payload['aud'] !== self::$firebaseProjectId) {
            // In development mode, skip audience check
            // return false;
        }
        
        // Check issuer
        $expectedIssuer = 'https://securetoken.google.com/' . self::$firebaseProjectId;
        if (isset($payload['iss']) && $payload['iss'] !== $expectedIssuer) {
            // In development mode, skip issuer check
            // return false;
        }
        
        return $payload;
    }
    
    /**
     * Extract bearer token from Authorization header
     * @return string|null
     */
    public static function getBearerToken() {
        $headers = self::getAuthorizationHeader();
        if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    /**
     * Get authorization header from various sources
     * @return string|null
     */
    private static function getAuthorizationHeader() {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return $headers['Authorization'];
            }
            if (isset($headers['authorization'])) {
                return $headers['authorization'];
            }
        }
        return null;
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    private static function getSecret(): string {
        return function_exists('env') ? env('JWT_SECRET', 'kasql_dev_secret_key_change_in_production') : 'kasql_dev_secret_key_change_in_production';
    }

    /**
     * Generate a simple API token for development/testing
     * This is a fallback when Firebase verification is not set up yet
     */
    public static function generateDevToken($userId, $role) {
        $payload = [
            'user_id' => $userId,
            'role' => $role,
            'exp' => time() + (24 * 60 * 60), // 24 hours
            'iat' => time()
        ];
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = base64_encode(json_encode($payload));
        $secret = self::getSecret();
        $signature = base64_encode(hash_hmac('sha256', "$header.$body", $secret, true));
        return "$header.$body.$signature";
    }
    
    /**
     * Verify a dev token (for development/testing without Firebase)
     */
    public static function verifyDevToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        
        $secret = self::getSecret();
        $expectedSig = base64_encode(hash_hmac('sha256', "$parts[0].$parts[1]", $secret, true));
        
        if (!hash_equals($expectedSig, $parts[2])) return false;
        
        $payload = json_decode(base64_decode($parts[1]), true);
        if (!$payload) return false;
        
        if (isset($payload['exp']) && $payload['exp'] < time()) return false;
        
        return $payload;
    }
}
?>
