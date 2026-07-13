<?php

namespace App\Middleware;

use App\Models\LanguageModel;

/**
 * LanguageMiddleware
 * Äáº£m nhiá»‡m viá»‡c xÃ¡c Ä‘á»‹nh ngÃ´n ngá»¯ vÃ  náº¡p dá»¯ liá»‡u cáº¥u hÃ¬nh ngÃ´n ngá»¯ tá»« DB.
 */
class LanguageMiddleware implements Middleware {
    /**
     * @param \App\Core\Request $request
     * @param \Closure $next
     */
    public function handle($request, $next) {
        // 1. Nháº­n diá»‡n Ngá»¯ cáº£nh (Admin vs Frontend)
        $isAdmin = (strpos($request->uri, '/admin') === 0);
        $defaultLang = config('app.locale', 'vi');

        if ($isAdmin) {
            // NGá»® Cáº¢NH ADMIN:
            // CÃ³ thá»ƒ láº¥y tá»« $_SESSION['admin_locale'] hoáº·c Ã©p cá»©ng vá» tiáº¿ng Viá»‡t
            $lang = $_SESSION['admin_locale'] ?? 'vi';
        } else {
            // NGá»® Cáº¢NH FRONTEND (WEB):
            // Sá»­ dá»¥ng má»™t chÃ¬a khÃ³a riÃªng biá»‡t 'app_locale' Ä‘á»ƒ khÃ´ng bá»‹ Admin ghi Ä‘Ã¨
            $lang = $_SESSION['app_locale'] ?? $defaultLang;
            
            // 2. Náº¡p dá»¯ liá»‡u ngÃ´n ngá»¯ tá»« Database thÃ´ng qua Model
            // Chuyá»ƒn khá»‘i nÃ y lÃªn trÃªn Ä‘á»ƒ láº¥y danh sÃ¡ch ngÃ´n ngá»¯ há»— trá»£
            if (config('lang') === null || empty(config('lang'))) {
                $dbLangs = LanguageModel::where('is_active', 1)->get();
                if (!empty($dbLangs)) {
                    $langs = [];
                    foreach ($dbLangs as $item) {
                        $langs[$item->code] = [
                            'code'  => $item->code,
                            'name'  => $item->name,
                            'label' => $item->label,
                            'image' => $item->image,
                            'price' => $item->price_unit
                        ];
                    }
                    config(['lang' => $langs]);
                }
            }

            $supportedLangs = array_keys(config('lang', []));
            if (empty($supportedLangs)) $supportedLangs = ['vi', 'en'];

            // Nháº­n diá»‡n Subfolder URL: /en/product/t-shirt -> cáº¯t 'en'
            $uriParts = explode('/', ltrim($request->uri, '/'));
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
                      || strpos($request->uri, '/ajax/') === 0 
                      || strpos($request->uri, '/api/') === 0;

            if (!empty($uriParts[0]) && in_array($uriParts[0], $supportedLangs)) {
                $lang = $uriParts[0];
                $_SESSION['app_locale'] = $lang;
                
                // Rewrite URI: bá» /{lang}/ ra
                array_shift($uriParts);
                
                // Dá»‹ch slug Ä‘áº§u tiÃªn vá» tiáº¿ng Viá»‡t Ä‘á»ƒ Router match Ä‘Ãºng route
                // VÃ­ dá»¥: /product/... â†’ /san-pham/... (vÃ¬ route Ä‘Äƒng kÃ½ lÃ  /san-pham)
                if ($lang !== $defaultLang && !empty($uriParts[0])) {
                    $translations = config('route_translations', []);
                    foreach ($translations as $routeKey => $langs) {
                        if (isset($langs[$lang]) && $langs[$lang] === $uriParts[0]) {
                            $uriParts[0] = $langs[$defaultLang] ?? $uriParts[0];
                            break;
                        }
                    }
                }
                
                $request->uri = '/' . implode('/', $uriParts);
            } elseif (!$isAjax) {
                // Náº¿u URL khÃ´ng cÃ³ tiá»n tá»‘ ngÃ´n ngá»¯ phá»¥ vÃ  KHÃ”NG pháº£i Ajax, ngáº§m Ä‘á»‹nh lÃ  ngÃ´n ngá»¯ máº·c Ä‘á»‹nh (vi)
                // Cáº­p nháº­t láº¡i session Ä‘á»ƒ Ä‘á»“ng bá»™ khi user chuyá»ƒn vá» giao diá»‡n tiáº¿ng Viá»‡t
                $lang = $defaultLang;
                $_SESSION['app_locale'] = $lang;
            }
            
            // Há»— trá»£ tham sá»‘ ?lang=en (dá»± phÃ²ng)
            if ($request->get('lang')) {
                $lang = $request->get('lang');
                $_SESSION['app_locale'] = $lang;
            }
        }
        // 3. Thiáº¿t láº­p ngÃ´n ngá»¯ thá»±c táº¿ cho Request hiá»‡n táº¡i
        config(['app.locale' => $lang]);
        
        // Define legacy constant for old class.php functions
        if (!defined('_where_lang')) {
            define('_where_lang', " AND lang='$lang'");
        }

        // Tiáº¿p tá»¥c chuyá»ƒn request Ä‘áº¿n lá»›p tiáº¿p theo
        return $next($request);
    }
}
