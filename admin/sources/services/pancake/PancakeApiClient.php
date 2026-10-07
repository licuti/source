<?php
/**
 * PancakeApiClient
 *
 * Chịu trách nhiệm giao tiếp với Pancake POS API
 */
class PancakeApiClient
{
    protected $apiKey;
    protected $shopId;
    protected $apiUrl;

    public function __construct($config)
    {
        global $d;
        // Ưu tiên đọc cấu hình lưu trong CSDL (#_thongtin) nếu người dùng đã nhập trong Admin
        if (isset($d) && is_object($d) && method_exists($d, 'simple_fetch')) {
            $dbConfig = $d->simple_fetch("SELECT pancake_api_key, pancake_shop_id, pancake_api_url FROM #_thongtin WHERE pancake_api_key IS NOT NULL AND pancake_api_key != '' LIMIT 1");
            if (!empty($dbConfig['pancake_api_key'])) {
                $config['api_key'] = $dbConfig['pancake_api_key'];
                $config['shop_id'] = $dbConfig['pancake_shop_id'] ?? ($config['shop_id'] ?? '');
                $config['api_url'] = !empty($dbConfig['pancake_api_url']) ? $dbConfig['pancake_api_url'] : ($config['api_url'] ?? 'https://crm.pancake.vn/api');
            }
        }

        $this->apiKey = trim($config['api_key'] ?? '');
        $this->shopId = trim($config['shop_id'] ?? '');
        $this->apiUrl = rtrim(trim($config['api_url'] ?? 'https://crm.pancake.vn/api'), '/');
    }

    /**
     * Gửi request GET tới Pancake API
     */
    protected function requestGet($endpoint, $params = [])
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'message' => 'Missing Pancake API Key'];
        }
        if (empty($this->shopId)) {
            return ['success' => false, 'message' => 'Missing Shop ID / Workspace ID in config.php'];
        }

        // Tự động nhận diện nền tảng (CRM hoặc POS)
        if (strpos($this->apiUrl, 'crm.pancake') !== false) {
            $url = $this->apiUrl . '/workspaces/' . $this->shopId . '/' . ltrim($endpoint, '/');
        } else {
            $url = $this->apiUrl . '/shops/' . $this->shopId . '/' . ltrim($endpoint, '/');
        }

        // Pancake POS có thể yêu cầu api_key hoặc access_token qua URL
        $params['api_key'] = $this->apiKey;
        $params['access_token'] = $this->apiKey;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Thêm User-Agent để tránh bị Firewall/Cloudflare chặn gây ra lỗi 406
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: */*'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            return ['success' => true, 'data' => $data];
        } else {
            // Log raw response for debugging
            return ['success' => false, 'message' => 'API Error HTTP ' . $httpCode . ' - Response: ' . strip_tags($response), 'raw' => $response];
        }
    }

    /**
     * Lấy danh sách sản phẩm từ Pancake
     *
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getProducts($page = 1, $limit = 50)
    {
        return $this->requestGet('products', [
            'page' => $page,
            'per_page' => $limit,
            'show_products' => 'true' // Rất quan trọng đối với bản CRM để không bị tách rời biến thể thành từng dòng
        ]);
    }

    /**
     * Lấy chi tiết 1 sản phẩm từ Pancake
     *
     * @param int|string $id
     * @return array
     */
    public function getProduct($id)
    {
        return $this->requestGet('products/' . $id);
    }


    /**
     * Lấy danh sách kho hàng từ Pancake
     *
     * @return array
     */
    public function getWarehouses()
    {
        return $this->requestGet('warehouses');
    }

    /**
     * Gửi request POST/PUT bằng JSON
     */
    protected function requestJSON($method, $endpoint, $payload = [], $params = [])
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'message' => 'Missing Pancake API Key'];
        }
        if (empty($this->shopId)) {
            return ['success' => false, 'message' => 'Missing Shop ID / Workspace ID in config.php'];
        }

        if (strpos($this->apiUrl, 'crm.pancake') !== false) {
            $url = $this->apiUrl . '/workspaces/' . $this->shopId . '/' . ltrim($endpoint, '/');
        } else {
            $url = $this->apiUrl . '/shops/' . $this->shopId . '/' . ltrim($endpoint, '/');
        }

        $params['api_key'] = $this->apiKey;
        $params['access_token'] = $this->apiKey;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        
        $jsonPayload = empty($payload) ? '' : json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($method != 'GET' && $method != 'DELETE' && !empty($jsonPayload)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        }
        
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
            'Content-Length: ' . strlen($jsonPayload)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            return ['success' => true, 'data' => $data];
        } else {
            return ['success' => false, 'message' => 'API Error HTTP ' . $httpCode . ' - Response: ' . strip_tags($response), 'raw' => $response];
        }
    }

    /**
     * Tạo mới sản phẩm trên CRM
     */
    public function createProduct($data)
    {
        if (strpos($this->apiUrl, 'pages.fm') !== false || strpos($this->apiUrl, 'pos.pancake') !== false) {
            $data = ['product' => $data];
        }
        return $this->requestJSON('POST', 'products', $data);
    }

    /**
     * Cập nhật sản phẩm trên CRM
     */
    public function updateProduct($id, $data)
    {
        if (strpos($this->apiUrl, 'pages.fm') !== false || strpos($this->apiUrl, 'pos.pancake') !== false) {
            if (isset($data['data'])) {
                $data = $data['data'];
            }
            $data = ['product' => $data];
        }
        return $this->requestJSON('PUT', 'products/' . $id, $data);
    }

    /**
     * Lấy danh sách đơn hàng từ Pancake CRM
     */
    public function getOrders($page = 1, $limit = 50)
    {
        return $this->requestGet('orders', [
            'page' => $page,
            'per_page' => $limit
        ]);
    }

    /**
     * Lấy chi tiết 1 đơn hàng từ Pancake CRM
     */
    public function getOrder($id)
    {
        return $this->requestGet('orders/' . $id);
    }

    /**
     * Tạo mới đơn hàng trên Pancake CRM
     */
    public function createOrder($data)
    {
        return $this->requestJSON('POST', 'orders', $data);
    }

    public function updateOrder($id, $data)
    {
        return $this->requestJSON('PUT', 'orders/' . $id, $data);
    }

    /**
     * Cập nhật tồn kho cho 1 biến thể trên Pancake POS
     * POST /shops/{SHOP_ID}/variations/{VARIATION_ID}/update_quantity
     */
    public function updateInventory($variationId, $quantity, $warehouseId = null)
    {
        $payload = [
            "variations_warehouses" => [
                [
                    "remain_quantity" => (int)$quantity
                ]
            ]
        ];
        if ($warehouseId) {
            $payload["variations_warehouses"][0]["warehouse_id"] = $warehouseId;
        }

        // Với POS, endpoint có dạng /shops/{SHOP_ID}/variations/{VARIATION_ID}/update_quantity
        return $this->requestJSON('POST', 'variations/' . $variationId . '/update_quantity', $payload);
    }
    
    /**
     * Cập nhật tồn kho hàng loạt trên Pancake POS
     * POST /shops/{SHOP_ID}/variations/update_quantity
     */
    public function updateInventoryBulk($variationsWarehousesData)
    {
        $payload = [
            "is_actual_remain_quantity" => true,
            "variations_warehouses" => $variationsWarehousesData
        ];
        return $this->requestJSON('POST', 'variations/update_quantity', $payload);
    }
}
?>
