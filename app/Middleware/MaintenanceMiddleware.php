<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class MaintenanceMiddleware implements Middleware {
    /**
     * @param Request $request
     * @param \Closure $next
     */
    public function handle($request, $next) {
        $status = get_option('maintenance_status', 0);
        if ($status != 1) {
            return $next($request);
        }

        $uri = '/' . trim($request->uri, '/');

        // 1. Luôn cho phép vào admin
        if (strpos($uri, '/admin') === 0) {
            return $next($request);
        }

        // 2. Bypass bằng Cookie (nếu admin chia sẻ token URL)
        // Nếu user truy cập /?bypass=TOKEN -> check token
        if (isset($_GET['bypass'])) {
            $token = $_GET['bypass'];
            $tokensJson = get_option('maintenance_bypass_tokens', '[]');
            $tokens = json_decode($tokensJson, true) ?: [];
            
            $isValid = false;
            foreach ($tokens as $t) {
                if ($t['token'] === $token) {
                    // Check expire
                    if (empty($t['expires_at']) || strtotime($t['expires_at']) > time()) {
                        $isValid = true;
                        break;
                    }
                }
            }

            if ($isValid) {
                // Set cookie for 7 days (or according to expiration)
                setcookie('maintenance_bypass', $token, time() + 7 * 86400, '/');
                // Redirect immediately to clean URL
                $response = new Response('', 302);
                return $response->header('Location', '/');
            }
        }

        // 3. Kiểm tra cookie hiện tại
        if (isset($_COOKIE['maintenance_bypass'])) {
            $token = $_COOKIE['maintenance_bypass'];
            $tokensJson = get_option('maintenance_bypass_tokens', '[]');
            $tokens = json_decode($tokensJson, true) ?: [];
            
            foreach ($tokens as $t) {
                if ($t['token'] === $token) {
                    if (empty($t['expires_at']) || strtotime($t['expires_at']) > time()) {
                        return $next($request); // Hợp lệ
                    }
                }
            }
        }

        // 4. Các URL ngoại lệ (ngoài admin)
        $exceptionsJson = get_option('maintenance_exceptions', '[]');
        $exceptions = json_decode($exceptionsJson, true) ?: [];
        foreach ($exceptions as $exc) {
            $exc = trim($exc);
            if (!empty($exc)) {
                // wildcard support (ví dụ /api/*)
                $pattern = '#^' . str_replace('\*', '.*', preg_quote($exc, '#')) . '$#';
                if (preg_match($pattern, $uri)) {
                    return $next($request);
                }
            }
        }

        // 5. Kiểm tra Whitelist IP
        $clientIp = $this->getRealIp();
        $whitelistedJson = get_option('maintenance_whitelist_ips', '[]');
        $whitelist = json_decode($whitelistedJson, true) ?: [];
        
        foreach ($whitelist as $item) {
            $ip = trim($item['ip'] ?? '');
            if (!empty($ip)) {
                if ($this->ipMatches($clientIp, $ip)) {
                    return $next($request);
                }
            }
        }

        // NẾU TẤT CẢ ĐỀU FALSE -> CHẶN
        // Đọc cấu hình hiển thị
        $contentJson = get_option('maintenance_content', '{}');
        $content = json_decode($contentJson, true) ?: [];
        
        $viewData = [
            'title' => $content['title'] ?? 'Hệ thống đang bảo trì',
            'description' => $content['description'] ?? '<p>Chúng tôi đang tiến hành nâng cấp hệ thống. Vui lòng quay lại sau.</p>',
            'eta' => $content['eta'] ?? '',
            'bg_color' => $content['bg_color'] ?? '#0f0f13',
            'logo' => $content['logo'] ?? ''
        ];

        return new Response(view('pages.maintenance', $viewData), 503);
    }

    private function getRealIp() {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    // Just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return '127.0.0.1';
    }

    private function ipMatches($clientIp, $whitelistIp) {
        // Trùng khớp hoàn toàn
        if ($clientIp === $whitelistIp) return true;

        // Trùng khớp CIDR (ví dụ: 192.168.1.0/24)
        if (strpos($whitelistIp, '/') !== false) {
            list($subnet, $bits) = explode('/', $whitelistIp);
            
            // Xử lý IPv4
            if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $clientIpLong = ip2long($clientIp);
                $subnetLong = ip2long($subnet);
                $mask = -1 << (32 - $bits);
                $subnetLong &= $mask;
                return ($clientIpLong & $mask) === $subnetLong;
            }
            
            // IPv6 CIDR match (đơn giản hoá, nếu cần có thể implement IPv6 sau)
        }

        return false;
    }
}
