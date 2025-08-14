<?php
class Security {
    public static function sanitizeInput($input) {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function generateSlug($string) {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        return trim($string, '-');
    }

    public static function checkRateLimit($ip, $maxRequests = 10, $timeWindow = 60) {
        // Implementação básica de rate limiting
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($ip);
        $now = time();
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $requests = array_filter($data, function($timestamp) use ($now, $timeWindow) {
                return ($now - $timestamp) < $timeWindow;
            });
            
            if (count($requests) >= $maxRequests) {
                return false;
            }
            
            $requests[] = $now;
        } else {
            $requests = [$now];
        }
        
        file_put_contents($cacheFile, json_encode($requests));
        return true;
    }

    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>
