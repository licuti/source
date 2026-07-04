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
        
        $sitemapPath = public_path('sitemap.xml');
        $sitemapInfo = null;
        if (file_exists($sitemapPath)) {
            $sitemapInfo = [
                'time' => date('d/m/Y H:i:s', filemtime($sitemapPath)),
                'size' => round(filesize($sitemapPath) / 1024, 2) . ' KB',
                'url' => url('sitemap.xml')
            ];
        }

        return view('admin.sitemap.index', compact('settings', 'sitemapInfo'));
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
        
        return $this->redirect(route('admin.sitemap.index'))->with('success', 'Đã lưu cấu hình sitemap thành công.');
    }
    
    public function generate() {
        try {
            $options = OptionModel::whereIn('option_key', [
                'sitemap_post_enable', 'sitemap_post_priority', 'sitemap_post_freq',
                'sitemap_product_enable', 'sitemap_product_priority', 'sitemap_product_freq',
                'sitemap_category_enable', 'sitemap_category_priority', 'sitemap_category_freq'
            ])->pluck('option_value', 'option_key');
            
            $filePath = public_path('sitemap.xml');
            
            $writer = new \XMLWriter();
            $writer->openURI($filePath);
            $writer->setIndent(true);
            $writer->startDocument('1.0', 'UTF-8');
            
            $writer->startElement('urlset');
            $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            
            // 1. Home page
            $this->addUrl($writer, url('/'), date('c'), 'daily', '1.0');
            
            // 2. Posts
            if (($options['sitemap_post_enable'] ?? 1) == 1) {
                $posts = PostModel::where('is_active', 1)->select('slug', 'updated_at')->get();
                $freq = $options['sitemap_post_freq'] ?? 'daily';
                $pri = $options['sitemap_post_priority'] ?? '0.8';
                foreach ($posts as $post) {
                    $this->addUrl($writer, url($post->slug), date('c', strtotime($post->updated_at)), $freq, $pri);
                }
            }
            
            // 3. Products
            if (($options['sitemap_product_enable'] ?? 1) == 1) {
                $products = ProductModel::where('is_active', 1)->select('slug', 'updated_at')->get();
                $freq = $options['sitemap_product_freq'] ?? 'daily';
                $pri = $options['sitemap_product_priority'] ?? '0.9';
                foreach ($products as $product) {
                    $this->addUrl($writer, url($product->slug), date('c', strtotime($product->updated_at)), $freq, $pri);
                }
            }
            
            // 4. Categories
            if (($options['sitemap_category_enable'] ?? 1) == 1) {
                $cats = CategoryModel::where('is_active', 1)->select('slug', 'updated_at')->get();
                $freq = $options['sitemap_category_freq'] ?? 'weekly';
                $pri = $options['sitemap_category_priority'] ?? '0.7';
                foreach ($cats as $cat) {
                    $this->addUrl($writer, url($cat->slug), date('c', strtotime($cat->updated_at)), $freq, $pri);
                }
            }
            
            $writer->endElement(); // end urlset
            $writer->endDocument();
            $writer->flush();
            
            return response()->json(['success' => true, 'message' => 'Đã tạo sitemap.xml thành công!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }
    
    private function addUrl($writer, $loc, $lastmod, $changefreq, $priority) {
        $writer->startElement('url');
        $writer->writeElement('loc', $loc);
        $writer->writeElement('lastmod', $lastmod);
        $writer->writeElement('changefreq', $changefreq);
        $writer->writeElement('priority', $priority);
        $writer->endElement();
    }
}
