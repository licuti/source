<?php
namespace App\Controllers\Admin;

use App\Models\ProductModel;
use App\Models\FlashSaleModel;
use App\Core\Request;
use Carbon\Carbon;

class FlashSaleController extends BaseAdminController {
    
    // ==========================================
    // QUẢN LÝ CAMPAIGN
    // ==========================================
    
    public function index(Request $request) {
        $keyword = $request->get('keyword');
        $query = FlashSaleModel::orderBy('id', 'desc');
        
        if (!empty($keyword)) {
            $query->where('name', 'like', "%{$keyword}%");
        }
        
        $campaigns = $query->qbPaginate(20);
        
        // Count products for each campaign
        foreach ($campaigns as $camp) {
            $camp->product_count = ProductModel::where('flash_sale', $camp->id)->count();
        }
        
        return view('admin.flash_sale.campaign_list', compact('campaigns', 'keyword'));
    }
    
    public function storeCampaign(Request $request) {
        $id = $request->input('id');
        $name = $request->input('name');
        $start = $request->input('start_time');
        $end = $request->input('end_time');
        $status = $request->input('status', 1);
        
        if (empty($name) || empty($start) || empty($end)) {
            return $this->redirect(route('admin.flash_sale.index'))->with('error', 'Vui lòng nhập đầy đủ thông tin chiến dịch!');
        }
        
        if ($id) {
            $campaign = FlashSaleModel::find($id);
            if (!$campaign) {
                return $this->redirect(route('admin.flash_sale.index'))->with('error', 'Không tìm thấy chiến dịch!');
            }
        } else {
            $campaign = new FlashSaleModel();
        }
        
        $campaign->name = $name;
        $campaign->start_time = $start;
        $campaign->end_time = $end;
        $campaign->status = $status;
        $campaign->save();
        
        // Sync time to all products in this campaign
        $this->syncCampaignProducts($campaign->id);
        
        return $this->redirect(route('admin.flash_sale.index'))->with('success', 'Đã lưu chiến dịch thành công!');
    }
    
    public function destroyCampaign(Request $request) {
        $id = $request->input('id');
        $campaign = FlashSaleModel::find($id);
        if ($campaign) {
            // Reset all products
            $products = ProductModel::where('flash_sale', $id)->get();
            foreach ($products as $p) {
                $p->flash_sale = 0;
                $p->flash_sale_start = null;
                $p->flash_sale_end = null;
                $p->gia_flash_sale = 0;
                $p->save();
            }
            $campaign->delete();
            return response()->json(['success' => true, 'message' => 'Đã xóa chiến dịch và gỡ các sản phẩm liên quan!']);
        }
        return response()->json(['success' => false, 'message' => 'Không tìm thấy chiến dịch!']);
    }
    
    // ==========================================
    // QUẢN LÝ PRODUCTS TRONG CAMPAIGN
    // ==========================================
    
    public function products(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $campaign = FlashSaleModel::find($id);
        if (!$campaign) {
            return $this->redirect(route('admin.flash_sale.index'))->with('error', 'Không tìm thấy chiến dịch!');
        }
        
        $keyword = $request->get('keyword');
        $query = ProductModel::where('flash_sale', $id)->orderBy('updated_at', 'desc');
        
        if (!empty($keyword)) {
            $query->where('title', 'like', "%{$keyword}%");
        }
        
        $products = $query->qbPaginate(20);
        return view('admin.flash_sale.index', compact('products', 'keyword', 'campaign'));
    }
    
    public function searchProductAjax(Request $request) {
        $q = $request->get('q', '');
        
        $query = ProductModel::query();
        if (!empty($q)) {
            $query->whereRaw("(`title` LIKE ? OR `sku` LIKE ?)", ["%{$q}%", "%{$q}%"]);
        }
        
        $products = $query->orderBy('updated_at', 'desc')
                          ->limit(20)
                          ->get(['id', 'title', 'thumbnail', 'price', 'flash_sale']);
                                
        $results = [];
        foreach ($products as $p) {
            $disabled = ($p->flash_sale > 0); // If it's already in a campaign
            $statusText = $disabled ? ' (Đang tham gia chiến dịch khác)' : '';
            $results[] = [
                'id' => $p->id,
                'text' => $p->title . ' - Giá: ' . number_format($p->price, 0, ',', '.') . 'đ' . $statusText,
                'title' => $p->title,
                'price' => $p->price,
                'thumbnail' => getImageUrl($p->thumbnail),
                'disabled' => $disabled
            ];
        }
        
        return response()->json(['results' => $results]);
    }
    
    public function storeProduct(Request $request) {
        $campaign_id = $request->input('campaign_id');
        $product_id = $request->input('product_id');
        $gia_flash_sale = $request->input('gia_flash_sale');
        
        if (empty($campaign_id) || empty($product_id) || empty($gia_flash_sale)) {
            return back()->with('error', 'Vui lòng nhập đầy đủ thông tin!');
        }
        
        $campaign = FlashSaleModel::find($campaign_id);
        if (!$campaign) return back()->with('error', 'Không tìm thấy chiến dịch!');
        
        $product = ProductModel::find($product_id);
        if (!$product) return back()->with('error', 'Không tìm thấy sản phẩm!');
        
        $product->flash_sale = $campaign_id;
        $product->gia_flash_sale = str_replace(['.', ','], '', $gia_flash_sale);
        
        // Sync time from campaign
        $product->flash_sale_start = $campaign->start_time;
        $product->flash_sale_end = $campaign->end_time;
        $product->save();
        
        return back()->with('success', 'Đã thêm sản phẩm vào chiến dịch thành công!');
    }
    
    public function destroyProduct(Request $request) {
        $id = $request->input('id');
        $product = ProductModel::find($id);
        if ($product) {
            $product->flash_sale = 0;
            $product->flash_sale_start = null;
            $product->flash_sale_end = null;
            $product->gia_flash_sale = 0;
            $product->save();
            return response()->json(['success' => true, 'message' => 'Đã gỡ sản phẩm khỏi Chiến dịch!']);
        }
        return response()->json(['success' => false, 'message' => 'Không tìm thấy sản phẩm!']);
    }
    
    // ==========================================
    // HELPER
    // ==========================================
    
    private function syncCampaignProducts($campaign_id) {
        $campaign = FlashSaleModel::find($campaign_id);
        if (!$campaign) return;
        
        // Get all products in this campaign
        $products = ProductModel::where('flash_sale', $campaign_id)->get();
        foreach ($products as $p) {
            $p->flash_sale_start = $campaign->start_time;
            $p->flash_sale_end = $campaign->end_time;
            
            // If campaign is disabled, we could technically reset them to null,
            // but usually setting flash_sale to 0 is enough if we wanted to hide it.
            // Since our logic looks at start/end time, we just sync the time.
            $p->save();
        }
    }
}
