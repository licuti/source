<?php
namespace App\Services\Captcha;

class CloudflareTurnstile implements CaptchaInterface {
    
    private $siteKey;
    private $secretKey;
    
    public function __construct($siteKey, $secretKey) {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
    }
    
    public function render(): string {
        if (empty($this->siteKey)) return '';
        
        return '
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <div class="cf-turnstile mt-3" data-sitekey="' . htmlspecialchars($this->siteKey) . '"></div>
        ';
    }
    
    public function verify(string $token, string $ip = null): bool {
        if (empty($this->secretKey) || empty($token)) return false;
        
        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $data = [
            'secret' => $this->secretKey,
            'response' => $token
        ];
        if ($ip) {
            $data['remoteip'] = $ip;
        }
        
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === FALSE) return false;
        
        $responseData = json_decode($result, true);
        
        if (isset($responseData['success']) && $responseData['success'] == true) {
            return true;
        }
        
        return false;
    }
}
