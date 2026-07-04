<?php
namespace App\Services\Captcha;

class GoogleReCaptchaV3 implements CaptchaInterface {
    
    private $siteKey;
    private $secretKey;
    
    public function __construct($siteKey, $secretKey) {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
    }
    
    public function render(): string {
        if (empty($this->siteKey)) return '';
        
        return '
        <script src="https://www.google.com/recaptcha/api.js?render=' . htmlspecialchars($this->siteKey) . '"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var forms = document.querySelectorAll(".dynamic-form");
                forms.forEach(function(form) {
                    form.addEventListener("submit", function(e) {
                        e.preventDefault();
                        var submitBtn = form.querySelector(".btn-submit-form");
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            var originalText = submitBtn.innerHTML;
                            submitBtn.innerHTML = "<i class=\'fa-solid fa-spinner fa-spin\'></i> Đang xử lý...";
                        }
                        
                        grecaptcha.ready(function() {
                            grecaptcha.execute("' . htmlspecialchars($this->siteKey) . '", {action: "submit"}).then(function(token) {
                                var tokenInput = document.createElement("input");
                                tokenInput.type = "hidden";
                                tokenInput.name = "g-recaptcha-response";
                                tokenInput.value = token;
                                form.appendChild(tokenInput);
                                form.submit();
                            });
                        });
                    });
                });
            });
        </script>
        ';
    }
    
    public function verify(string $token, string $ip = null): bool {
        if (empty($this->secretKey) || empty($token)) return false;
        
        $url = 'https://www.google.com/recaptcha/api/siteverify';
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
        
        // Google reCaptcha v3 trả về điểm (score) từ 0.0 đến 1.0 (1.0 là người thật).
        // Ngưỡng tiêu chuẩn thường là 0.5
        if (isset($responseData['success']) && $responseData['success'] == true) {
            if (isset($responseData['score']) && $responseData['score'] >= 0.5) {
                return true;
            }
        }
        
        return false;
    }
}
