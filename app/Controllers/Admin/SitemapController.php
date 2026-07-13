<?php
namespace App\Controllers\Admin;

use App\Models\OptionModel;
use App\Models\PostModel;
use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Core\Request;

class SitemapController extends BaseAdminController {
    
    public function index() {
        $options = OptionModel::whereIn('option_key', [
            'sitemap_post_enable', 'sitemap_post_priority', 'sitemap_post_freq',
            'sitemap_product_enable', 'sitemap_product_priority', 'sitemap_product_freq',
            'sitemap_category_enable', 'sitemap_category_priority', 'sitemap_category_freq'
        ])->pluck('option_value', 'option_key');
        
        // Defaults
        $settings = [
            'post' => [
                'enable' => $options['sitemap_post_enable'] ?? 1,
                'priority' => $options['sitemap_post_priority'] ?? '0.8',
                'freq' => $options['sitemap_post_freq'] ?? 'daily'
            ],
            'product' => [
                'enable' => $options['sitemap_product_enable'] ?? 1,
                'priority' => $options['sitemap_product_priority'] ?? '0.9',
                'freq' => $options['sitemap_product_freq'] ?? 'daily'
            ],
            'category' => [
                'enable' => $options['sitemap_category_enable'] ?? 1,
                'priority' => $options['sitemap_category_priority'] ?? '0.7',
                'freq' => $options['sitemap_category_freq'] ?? 'weekly'
            ]
        ];
        
        $sitemapUrl = url('sitemap.xml');
        
        // Đọc file robots.txt
        $robotsPath = public_path('robots.txt');
        $robotsContent = file_exists($robotsPath) ? file_get_contents($robotsPath) : '';
        
        return $this->render('admin.sitemap.index', compact('settings', 'sitemapUrl', 'robotsContent'));
    }
    
    public function save(Request $request) {
        $data = $_POST;
        
        $keys = [
            'sitemap_post_enable', 'sitemap_post_priority', 'sitemap_post_freq',
            'sitemap_product_enable', 'sitemap_product_priority', 'sitemap_product_freq',
            'sitemap_category_enable', 'sitemap_category_priority', 'sitemap_category_freq'
        ];
        
        foreach ($keys as $key) {
            $val = $data[$key] ?? '';
            $opt = OptionModel::where('option_key', $key)->first();
            if ($opt) {
                $opt->option_value = $val;
                $opt->save();
            } else {
                OptionModel::create([
                    'option_key' => $key,
                    'option_value' => $val
                ]);
            }
        }
        
        // Cập nhật robots.txt
        if (isset($_POST['robots_txt'])) {
            $robotsPath = public_path('robots.txt');
            file_put_contents($robotsPath, $_POST['robots_txt']);
        }
        
        return $this->redirect(route('admin.sitemap.index'))->with('success', 'Đã lưu cấu hình sitemap thành công.');
    }
    
    public function ping() {
        try {
            $sitemapUrl = urlencode(url('sitemap.xml'));
            
            // Ping Google
            $googleUrl = "http://www.google.com/ping?sitemap=" . $sitemapUrl;
            $ch1 = curl_init($googleUrl);
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch1, CURLOPT_TIMEOUT, 10);
            $res1 = curl_exec($ch1);
            $httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
            curl_close($ch1);
            
            // Ping Bing
            $bingUrl = "http://www.bing.com/ping?sitemap=" . $sitemapUrl;
            $ch2 = curl_init($bingUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
            $res2 = curl_exec($ch2);
            $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);
            
            if ($httpCode1 == 200 && $httpCode2 == 200) {
                return response()->json(['success' => true, 'message' => 'Đã gửi thông báo (Ping) thành công tới Google và Bing!']);
            }
            
            return response()->json(['success' => true, 'message' => "Ping Google: $httpCode1, Ping Bing: $httpCode2. Đã xử lý."]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi kết nối: ' . $e->getMessage()]);
        }
    }
}
