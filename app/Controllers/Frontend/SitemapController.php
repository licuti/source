<?php
namespace App\Controllers\Frontend;

use App\Models\OptionModel;
use App\Models\PostModel;
use App\Models\ProductModel;
use App\Models\CategoryModel;

class SitemapController extends \App\Controllers\Controller {
    
    private function getOptions() {
        return OptionModel::whereIn('option_key', [
            'sitemap_post_enable', 'sitemap_post_priority', 'sitemap_post_freq',
            'sitemap_product_enable', 'sitemap_product_priority', 'sitemap_product_freq',
            'sitemap_category_enable', 'sitemap_category_priority', 'sitemap_category_freq'
        ])->pluck('option_value', 'option_key');
    }

    public function index() {
        $options = $this->getOptions();
        
        header('Content-Type: text/xml; charset=utf-8');
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->startDocument('1.0', 'UTF-8');
        
        $writer->startElement('sitemapindex');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        
        if (($options['sitemap_post_enable'] ?? 1) == 1) {
            $this->addSitemap($writer, route('sitemap.posts'));
        }
        if (($options['sitemap_product_enable'] ?? 1) == 1) {
            $this->addSitemap($writer, route('sitemap.products'));
        }
        if (($options['sitemap_category_enable'] ?? 1) == 1) {
            $this->addSitemap($writer, route('sitemap.categories'));
        }
        
        $writer->endElement(); // end sitemapindex
        $writer->endDocument();
        echo $writer->outputMemory();
        exit;
    }

    public function posts() {
        $options = $this->getOptions();
        if (($options['sitemap_post_enable'] ?? 1) != 1) {
            return $this->error404();
        }
        
        $posts = PostModel::where('status', 1)->select('slug', 'updated_at', 'image')->get();
        $freq = $options['sitemap_post_freq'] ?? 'daily';
        $pri = $options['sitemap_post_priority'] ?? '0.8';
        
        $this->renderUrlSet($posts, $freq, $pri);
    }
    
    public function products() {
        $options = $this->getOptions();
        if (($options['sitemap_product_enable'] ?? 1) != 1) {
            return $this->error404();
        }
        
        $products = ProductModel::where('status', 1)->select('slug', 'updated_at', 'image')->get();
        $freq = $options['sitemap_product_freq'] ?? 'daily';
        $pri = $options['sitemap_product_priority'] ?? '0.9';
        
        $this->renderUrlSet($products, $freq, $pri);
    }
    
    public function categories() {
        $options = $this->getOptions();
        if (($options['sitemap_category_enable'] ?? 1) != 1) {
            return $this->error404();
        }
        
        $cats = CategoryModel::where('status', 1)->select('slug', 'updated_at', 'image')->get();
        $freq = $options['sitemap_category_freq'] ?? 'weekly';
        $pri = $options['sitemap_category_priority'] ?? '0.7';
        
        $this->renderUrlSet($cats, $freq, $pri);
    }
    
    private function renderUrlSet($items, $freq, $pri) {
        header('Content-Type: text/xml; charset=utf-8');
        
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->startDocument('1.0', 'UTF-8');
        
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
        
        foreach ($items as $item) {
            $writer->startElement('url');
            $writer->writeElement('loc', url($item->slug));
            $writer->writeElement('lastmod', date('c', strtotime($item->updated_at)));
            $writer->writeElement('changefreq', $freq);
            $writer->writeElement('priority', $pri);
            
            // Image Sitemap support
            if (!empty($item->image)) {
                $writer->startElement('image:image');
                $writer->writeElement('image:loc', url($item->image));
                $writer->endElement();
            }
            
            $writer->endElement();
        }
        
        $writer->endElement(); // end urlset
        $writer->endDocument();
        echo $writer->outputMemory();
        exit;
    }
    
    private function addSitemap($writer, $loc) {
        $writer->startElement('sitemap');
        $writer->writeElement('loc', $loc);
        $writer->writeElement('lastmod', date('c'));
        $writer->endElement();
    }
    
    private function error404() {
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
        exit;
    }
}
