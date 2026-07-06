<?php
/**
 * ============================================================
 *  Admin Routes
 * ============================================================
 */

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;

$router->group('/admin', function($r) {
    // Auth Routes
    $r->get('/login', [AuthController::class, 'login'])->name('admin.login');
    $r->post('/login', [AuthController::class, 'loginPost']);
    $r->get('/logout', [AuthController::class, 'logout'])->name('admin.logout');
    
    // Module Quản lý User (RBAC)
    $r->get('/user', [\App\Controllers\Admin\UserAdminController::class, 'index'])->name('admin.user.index');
    $r->get('/user/create', [\App\Controllers\Admin\UserAdminController::class, 'create'])->name('admin.user.create');
    $r->post('/user/store', [\App\Controllers\Admin\UserAdminController::class, 'store'])->name('admin.user.store');
    $r->get('/user/edit/{id}', [\App\Controllers\Admin\UserAdminController::class, 'edit'])->name('admin.user.edit');
    $r->post('/user/update/{id}', [\App\Controllers\Admin\UserAdminController::class, 'update'])->name('admin.user.update');
    $r->get('/user/destroy/{id}', [\App\Controllers\Admin\UserAdminController::class, 'destroy'])->name('admin.user.destroy');
    $r->post('/user/update-status-ajax', [\App\Controllers\Admin\UserAdminController::class, 'updateStatusAjax'])->name('admin.user.updateStatusAjax');

    // Quản lý Role (Nhóm quyền)
    $r->get('/role', [\App\Controllers\Admin\RoleAdminController::class, 'index'])->name('admin.role.index');
    $r->get('/role/create', [\App\Controllers\Admin\RoleAdminController::class, 'create'])->name('admin.role.create');
    $r->post('/role/store', [\App\Controllers\Admin\RoleAdminController::class, 'store'])->name('admin.role.store');
    $r->get('/role/edit/{id}', [\App\Controllers\Admin\RoleAdminController::class, 'edit'])->name('admin.role.edit');
    $r->post('/role/update/{id}', [\App\Controllers\Admin\RoleAdminController::class, 'update'])->name('admin.role.update');
    $r->get('/role/destroy/{id}', [\App\Controllers\Admin\RoleAdminController::class, 'destroy'])->name('admin.role.destroy');
    $r->post('/role/update-status-ajax', [\App\Controllers\Admin\RoleAdminController::class, 'updateStatusAjax'])->name('admin.role.updateStatusAjax');

    // Dashboard (Yêu cầu đăng nhập, sẽ bị AdminAuthMiddleware kiểm tra)
    $r->get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Cấu hình Website (Settings)
    $r->get('/settings', [\App\Controllers\Admin\SettingController::class, 'index'])->name('admin.setting.index');
    $r->post('/settings', [\App\Controllers\Admin\SettingController::class, 'update'])->name('admin.setting.update');

    // System Sidebar Menu Management Routes (Drag & Drop)
    $r->get('/system-menu', [\App\Controllers\Admin\MenuAdminController::class, 'index'])->name('admin.system_menu.index');
    $r->post('/system-menu/store', [\App\Controllers\Admin\MenuAdminController::class, 'store'])->name('admin.system_menu.store');
    $r->get('/system-menu/edit/{id}', [\App\Controllers\Admin\MenuAdminController::class, 'edit'])->name('admin.system_menu.edit');
    $r->post('/system-menu/update/{id}', [\App\Controllers\Admin\MenuAdminController::class, 'update'])->name('admin.system_menu.update');
    $r->get('/system-menu/delete/{id}', [\App\Controllers\Admin\MenuAdminController::class, 'destroy'])->name('admin.system_menu.destroy');
    $r->post('/system-menu/update-sort-ajax', [\App\Controllers\Admin\MenuAdminController::class, 'updateSortAjax'])->name('admin.system_menu.updateSortAjax');

    // Website Menu Management Routes (Front-end Menu)
    $r->get('/menu', [\App\Controllers\Admin\MenuController::class, 'index'])->name('admin.menu.index');
    $r->post('/menu/store', [\App\Controllers\Admin\MenuController::class, 'ajaxCreate'])->name('admin.menu.store');
    $r->post('/menu/delete', [\App\Controllers\Admin\MenuController::class, 'ajaxDelete'])->name('admin.menu.delete');
    $r->post('/menu/save', [\App\Controllers\Admin\MenuController::class, 'ajaxSave'])->name('admin.menu.save');
    $r->get('/menu/search-source', [\App\Controllers\Admin\MenuController::class, 'ajaxSearchSource'])->name('admin.menu.searchSource');
    $r->post('/menu/ajax-create', [\App\Controllers\Admin\MenuController::class, 'ajaxCreate'])->name('admin.menu.ajax_create');
    $r->post('/menu/ajax-save', [\App\Controllers\Admin\MenuController::class, 'ajaxSave'])->name('admin.menu.ajax_save');
    $r->post('/menu/ajax-delete', [\App\Controllers\Admin\MenuController::class, 'ajaxDelete'])->name('admin.menu.ajax_delete');

    // Redirect 301 Routes
    $r->get('/redirect', [\App\Controllers\Admin\RedirectController::class, 'index'])->name('admin.redirect.index');
    $r->get('/redirect/create', [\App\Controllers\Admin\RedirectController::class, 'create'])->name('admin.redirect.create');
    $r->post('/redirect/store', [\App\Controllers\Admin\RedirectController::class, 'store'])->name('admin.redirect.store');
    $r->get('/redirect/edit/{id}', [\App\Controllers\Admin\RedirectController::class, 'edit'])->name('admin.redirect.edit');
    $r->post('/redirect/update/{id}', [\App\Controllers\Admin\RedirectController::class, 'update'])->name('admin.redirect.update');
    $r->delete('/redirect/destroy/{id}', [\App\Controllers\Admin\RedirectController::class, 'destroy'])->name('admin.redirect.destroy');
    $r->post('/redirect/delete-multiple', [\App\Controllers\Admin\RedirectController::class, 'destroyMultiple'])->name('admin.redirect.destroyMultiple');
    $r->post('/redirect/update-status-ajax', [\App\Controllers\Admin\RedirectController::class, 'updateStatusAjax'])->name('admin.redirect.status');

    // Category Routes
    $r->get('/category', [\App\Controllers\Admin\CategoryController::class, 'index'])->name('admin.category.index');
    $r->get('/category/create', [\App\Controllers\Admin\CategoryController::class, 'create'])->name('admin.category.create');
    $r->post('/category/store', [\App\Controllers\Admin\CategoryController::class, 'store'])->name('admin.category.store');
    $r->get('/category/edit/{id}', [\App\Controllers\Admin\CategoryController::class, 'edit'])->name('admin.category.edit');
    $r->post('/category/update/{id}', [\App\Controllers\Admin\CategoryController::class, 'update'])->name('admin.category.update');
    $r->get('/category/delete/{id}', [\App\Controllers\Admin\CategoryController::class, 'destroy'])->name('admin.category.destroy');
    $r->post('/category/delete-multiple', [\App\Controllers\Admin\CategoryController::class, 'destroyMultiple'])->name('admin.category.destroy_multiple');
    $r->post('/category/update-status-ajax', [\App\Controllers\Admin\CategoryController::class, 'updateStatusAjax'])->name('admin.category.updateStatusAjax');

    // Blocks (Khối giao diện) Routes
    $r->get('/blocks', [\App\Controllers\Admin\BlockController::class, 'index'])->name('admin.block.index');
    $r->get('/blocks/create', [\App\Controllers\Admin\BlockController::class, 'create'])->name('admin.block.create');
    $r->post('/blocks/store', [\App\Controllers\Admin\BlockController::class, 'store'])->name('admin.block.store');
    $r->get('/blocks/edit/{id}', [\App\Controllers\Admin\BlockController::class, 'edit'])->name('admin.block.edit');
    $r->post('/blocks/update/{id}', [\App\Controllers\Admin\BlockController::class, 'update'])->name('admin.block.update');
    $r->get('/blocks/delete/{id}', [\App\Controllers\Admin\BlockController::class, 'destroy'])->name('admin.block.destroy');
    $r->post('/blocks/delete-multiple', [\App\Controllers\Admin\BlockController::class, 'destroyMultiple'])->name('admin.block.destroy_multiple');
    $r->post('/blocks/update-status-ajax', [\App\Controllers\Admin\BlockController::class, 'updateStatusAjax'])->name('admin.block.update_status_ajax');

    // Block Items (Mục con của khối) Routes
    $r->get('/blocks/{block_id}/items', [\App\Controllers\Admin\BlockItemController::class, 'index'])->name('admin.block_item.index');
    $r->get('/blocks/{block_id}/items/create', [\App\Controllers\Admin\BlockItemController::class, 'create'])->name('admin.block_item.create');
    $r->post('/blocks/{block_id}/items/store', [\App\Controllers\Admin\BlockItemController::class, 'store'])->name('admin.block_item.store');
    $r->get('/blocks/{block_id}/items/edit/{id}', [\App\Controllers\Admin\BlockItemController::class, 'edit'])->name('admin.block_item.edit');
    $r->post('/blocks/{block_id}/items/update/{id}', [\App\Controllers\Admin\BlockItemController::class, 'update'])->name('admin.block_item.update');
    $r->get('/blocks/{block_id}/items/delete/{id}', [\App\Controllers\Admin\BlockItemController::class, 'destroy'])->name('admin.block_item.destroy');
    $r->post('/blocks/{block_id}/items/delete-multiple', [\App\Controllers\Admin\BlockItemController::class, 'destroyMultiple'])->name('admin.block_item.destroy_multiple');
    $r->post('/blocks/{block_id}/items/update-status-ajax', [\App\Controllers\Admin\BlockItemController::class, 'updateStatusAjax'])->name('admin.block_item.update_status_ajax');
    $r->post('/blocks/{block_id}/items/sort', [\App\Controllers\Admin\BlockItemController::class, 'updateSort'])->name('admin.block_item.update_sort');


    // Post Routes
    $r->get('/post', [\App\Controllers\Admin\PostController::class, 'index'])->name('admin.post.index');
    $r->get('/post/create', [\App\Controllers\Admin\PostController::class, 'create'])->name('admin.post.create');
    $r->post('/post/store', [\App\Controllers\Admin\PostController::class, 'store'])->name('admin.post.store');
    $r->get('/post/edit/{id}', [\App\Controllers\Admin\PostController::class, 'edit'])->name('admin.post.edit');
    $r->post('/post/update/{id}', [\App\Controllers\Admin\PostController::class, 'update'])->name('admin.post.update');
    $r->get('/post/delete/{id}', [\App\Controllers\Admin\PostController::class, 'destroy'])->name('admin.post.destroy');
    $r->post('/post/delete-multiple', [\App\Controllers\Admin\PostController::class, 'destroyMultiple'])->name('admin.post.destroy_multiple');
    $r->post('/post/update-status-ajax', [\App\Controllers\Admin\PostController::class, 'updateStatusAjax'])->name('admin.post.updateStatusAjax');

    // Product Routes
    $r->get('/product', [\App\Controllers\Admin\ProductController::class, 'index'])->name('admin.product.index');
    $r->get('/product/create', [\App\Controllers\Admin\ProductController::class, 'create'])->name('admin.product.create');
    $r->post('/product/store', [\App\Controllers\Admin\ProductController::class, 'store'])->name('admin.product.store');
    $r->get('/product/edit/{id}', [\App\Controllers\Admin\ProductController::class, 'edit'])->name('admin.product.edit');
    $r->post('/product/update/{id}', [\App\Controllers\Admin\ProductController::class, 'update'])->name('admin.product.update');
    $r->get('/product/delete/{id}', [\App\Controllers\Admin\ProductController::class, 'destroy'])->name('admin.product.destroy');
    $r->post('/product/delete-multiple', [\App\Controllers\Admin\ProductController::class, 'destroyMultiple'])->name('admin.product.destroy_multiple');
    $r->post('/product/update-status-ajax', [\App\Controllers\Admin\ProductController::class, 'updateStatusAjax'])->name('admin.product.updateStatusAjax');

    // Tax Class Routes
    $r->get('/tax-class', [\App\Controllers\Admin\TaxClassController::class, 'index'])->name('admin.tax_class.index');
    $r->get('/tax-class/create', [\App\Controllers\Admin\TaxClassController::class, 'create'])->name('admin.tax_class.create');
    $r->post('/tax-class/store', [\App\Controllers\Admin\TaxClassController::class, 'store'])->name('admin.tax_class.store');
    $r->get('/tax-class/edit/{id}', [\App\Controllers\Admin\TaxClassController::class, 'edit'])->name('admin.tax_class.edit');
    $r->post('/tax-class/update/{id}', [\App\Controllers\Admin\TaxClassController::class, 'update'])->name('admin.tax_class.update');
    $r->post('/tax-class/delete/{id}', [\App\Controllers\Admin\TaxClassController::class, 'destroy'])->name('admin.tax_class.destroy');
    $r->post('/tax-class/update-status-ajax', [\App\Controllers\Admin\TaxClassController::class, 'updateStatusAjax'])->name('admin.tax_class.updateStatusAjax');

    // Tax Rate Routes
    $r->get('/tax-rate', [\App\Controllers\Admin\TaxRateController::class, 'index'])->name('admin.tax_rate.index');
    $r->get('/tax-rate/create', [\App\Controllers\Admin\TaxRateController::class, 'create'])->name('admin.tax_rate.create');
    $r->post('/tax-rate/store', [\App\Controllers\Admin\TaxRateController::class, 'store'])->name('admin.tax_rate.store');
    $r->get('/tax-rate/edit/{id}', [\App\Controllers\Admin\TaxRateController::class, 'edit'])->name('admin.tax_rate.edit');
    $r->post('/tax-rate/update/{id}', [\App\Controllers\Admin\TaxRateController::class, 'update'])->name('admin.tax_rate.update');
    $r->post('/tax-rate/delete/{id}', [\App\Controllers\Admin\TaxRateController::class, 'destroy'])->name('admin.tax_rate.destroy');
    $r->post('/tax-rate/update-status-ajax', [\App\Controllers\Admin\TaxRateController::class, 'updateStatusAjax'])->name('admin.tax_rate.updateStatusAjax');
    $r->post('/location/get-provinces', [\App\Controllers\Admin\LocationController::class, 'getProvincesAjax'])->name('admin.location.get_provinces');
    $r->post('/location/get-districts', [\App\Controllers\Admin\LocationController::class, 'getDistrictsAjax'])->name('admin.location.get_districts');
    $r->post('/location/get-wards', [\App\Controllers\Admin\LocationController::class, 'getWardsAjax'])->name('admin.location.get_wards');

    // Shop / Marketplace Routes
    $r->get('/shop', [\App\Controllers\Admin\ShopController::class, 'index'])->name('admin.shop.index');
    $r->get('/shop/create', [\App\Controllers\Admin\ShopController::class, 'create'])->name('admin.shop.create');
    $r->post('/shop/store', [\App\Controllers\Admin\ShopController::class, 'store'])->name('admin.shop.store');
    $r->get('/shop/edit/{id}', [\App\Controllers\Admin\ShopController::class, 'edit'])->name('admin.shop.edit');
    $r->post('/shop/update/{id}', [\App\Controllers\Admin\ShopController::class, 'update'])->name('admin.shop.update');
    $r->post('/shop/delete/{id}', [\App\Controllers\Admin\ShopController::class, 'destroy'])->name('admin.shop.destroy');
    $r->post('/shop/delete-multiple', [\App\Controllers\Admin\ShopController::class, 'destroyMultiple'])->name('admin.shop.destroyMultiple');
    $r->post('/shop/update-status-ajax', [\App\Controllers\Admin\ShopController::class, 'updateStatusAjax'])->name('admin.shop.updateStatusAjax');

    // Attribute Routes
    $r->get('/attribute', [\App\Controllers\Admin\AttributeController::class, 'index'])->name('admin.attribute.index');
    $r->get('/attribute/create', [\App\Controllers\Admin\AttributeController::class, 'create'])->name('admin.attribute.create');
    $r->post('/attribute/store', [\App\Controllers\Admin\AttributeController::class, 'store'])->name('admin.attribute.store');
    $r->get('/attribute/edit/{id}', [\App\Controllers\Admin\AttributeController::class, 'edit'])->name('admin.attribute.edit');
    $r->post('/attribute/update/{id}', [\App\Controllers\Admin\AttributeController::class, 'update'])->name('admin.attribute.update');
    $r->get('/attribute/delete/{id}', [\App\Controllers\Admin\AttributeController::class, 'destroy'])->name('admin.attribute.destroy');
    $r->post('/attribute/update-status-ajax', [\App\Controllers\Admin\AttributeController::class, 'updateStatusAjax'])->name('admin.attribute.updateStatusAjax');

    // Language Settings Routes

    // System Configuration Routes
    $r->get('/email-smtp', [\App\Controllers\Admin\EmailController::class, 'index'])->name('admin.email.index');
    $r->post('/email-smtp/save', [\App\Controllers\Admin\EmailController::class, 'save'])->name('admin.email.save');
    $r->post('/email-smtp/test', [\App\Controllers\Admin\EmailController::class, 'testEmail'])->name('admin.email.test');

    $r->get('/api-integration', [\App\Controllers\Admin\ApiIntegrationController::class, 'index'])->name('admin.api_integration.index');
    $r->post('/api-integration/save', [\App\Controllers\Admin\ApiIntegrationController::class, 'save'])->name('admin.api_integration.save');

    $r->get('/language', [\App\Controllers\Admin\LanguageSettingController::class, 'index'])->name('admin.language.index');
    $r->get('/language/create', [\App\Controllers\Admin\LanguageSettingController::class, 'create'])->name('admin.language.create');
    $r->post('/language/store', [\App\Controllers\Admin\LanguageSettingController::class, 'store'])->name('admin.language.store');
    $r->get('/language/scan', 'LanguageSettingController@scan', ['name' => 'admin.language.scan']);
    
    // Sitemap
    $r->get('/sitemap', [\App\Controllers\Admin\SitemapController::class, 'index'])->name('admin.sitemap.index');
    $r->post('/sitemap/save', [\App\Controllers\Admin\SitemapController::class, 'save'])->name('admin.sitemap.save');
    $r->post('/sitemap/ping', [\App\Controllers\Admin\SitemapController::class, 'ping'])->name('admin.sitemap.ping');
    
    // Cấu hình SEO
    $r->get('/seo-config', [\App\Controllers\Admin\SeoConfigController::class, 'index'])->name('admin.seo_config.index');
    $r->post('/seo-config/save', [\App\Controllers\Admin\SeoConfigController::class, 'save'])->name('admin.seo_config.save');
    
    // Flash Sale
    $r->get('/flash-sale', [\App\Controllers\Admin\FlashSaleController::class, 'index'])->name('admin.flash_sale.index');
    $r->post('/flash-sale/store-campaign', [\App\Controllers\Admin\FlashSaleController::class, 'storeCampaign'])->name('admin.flash_sale.store_campaign');
    $r->post('/flash-sale/delete-campaign', [\App\Controllers\Admin\FlashSaleController::class, 'destroyCampaign'])->name('admin.flash_sale.destroy_campaign');
    
    $r->get('/flash-sale/{id}/products', [\App\Controllers\Admin\FlashSaleController::class, 'products'])->name('admin.flash_sale.products');
    $r->get('/flash-sale/search-products', [\App\Controllers\Admin\FlashSaleController::class, 'searchProductAjax'])->name('admin.flash_sale.search_products');
    $r->post('/flash-sale/store-product', [\App\Controllers\Admin\FlashSaleController::class, 'storeProduct'])->name('admin.flash_sale.store_product');
    $r->post('/flash-sale/delete-product-ajax', [\App\Controllers\Admin\FlashSaleController::class, 'destroyProduct'])->name('admin.flash_sale.destroy_product');

    // Gallery
    $r->get('/gallery', [\App\Controllers\Admin\GalleryController::class, 'index'])->name('admin.gallery.index');
    $r->get('/gallery/create', [\App\Controllers\Admin\GalleryController::class, 'create'])->name('admin.gallery.create');
    $r->get('/gallery/edit/{id}', [\App\Controllers\Admin\GalleryController::class, 'edit'])->name('admin.gallery.edit');
    $r->post('/gallery/store', [\App\Controllers\Admin\GalleryController::class, 'store'])->name('admin.gallery.store');
    $r->post('/gallery/delete-ajax', [\App\Controllers\Admin\GalleryController::class, 'destroyAjax'])->name('admin.gallery.destroy_ajax');
    $r->post('/gallery/bulk-delete-ajax', [\App\Controllers\Admin\GalleryController::class, 'bulkDeleteAjax'])->name('admin.gallery.bulkDeleteAjax');
    $r->post('/gallery/update-status-ajax', [\App\Controllers\Admin\GalleryController::class, 'updateStatusAjax'])->name('admin.gallery.updateStatusAjax');

    $r->get('/language/edit/{id}', [\App\Controllers\Admin\LanguageSettingController::class, 'edit'])->name('admin.language.edit');
    $r->post('/language/update/{id}', [\App\Controllers\Admin\LanguageSettingController::class, 'update'])->name('admin.language.update');
    $r->get('/language/delete/{id}', [\App\Controllers\Admin\LanguageSettingController::class, 'destroy'])->name('admin.language.destroy');
    $r->post('/language/delete-multiple', [\App\Controllers\Admin\LanguageSettingController::class, 'destroyMultiple'])->name('admin.language.destroy_multiple');
    $r->post('/language/update-status-ajax', [\App\Controllers\Admin\LanguageSettingController::class, 'updateStatusAjax'])->name('admin.language.updateStatusAjax');

    // Quản lý Dịch Chuỗi (Text Translations)
    $r->get('/translations', [\App\Controllers\Admin\TextTranslationController::class, 'index'])->name('admin.translation.index');
    $r->post('/translations/update-ajax', [\App\Controllers\Admin\TextTranslationController::class, 'updateAjax'])->name('admin.translation.updateAjax');
    $r->post('/translations/update-key-ajax', [\App\Controllers\Admin\TextTranslationController::class, 'updateKeyAjax'])->name('admin.translation.updateKeyAjax');
    $r->post('/translations/update-group-ajax', [\App\Controllers\Admin\TextTranslationController::class, 'updateGroupAjax'])->name('admin.translation.updateGroupAjax');
    $r->post('/translations/update-bulk-group-ajax', [\App\Controllers\Admin\TextTranslationController::class, 'updateBulkGroupAjax'])->name('admin.translation.updateBulkGroupAjax');
    $r->post('/translations/rename-group-ajax', [\App\Controllers\Admin\TextTranslationController::class, 'renameGroupAjax'])->name('admin.translation.renameGroupAjax');
    $r->post('/translations/delete-group-ajax', [\App\Controllers\Admin\TextTranslationController::class, 'deleteGroupAjax'])->name('admin.translation.deleteGroupAjax');
    $r->post('/translations/store', [\App\Controllers\Admin\TextTranslationController::class, 'store'])->name('admin.translation.store');
    $r->get('/translations/delete/{id}', [\App\Controllers\Admin\TextTranslationController::class, 'destroy'])->name('admin.translation.destroy');
    $r->post('/translations/scan', [\App\Controllers\Admin\TextTranslationController::class, 'scan'])->name('admin.translation.scan');

    $r->get('/backup-cache', [\App\Controllers\Admin\BackupController::class, 'index'])->name('admin.backup.index');
    $r->post('/backup-cache/create', [\App\Controllers\Admin\BackupController::class, 'createBackup'])->name('admin.backup.create');
    $r->post('/backup-cache/create-source', [\App\Controllers\Admin\BackupController::class, 'createSourceBackup'])->name('admin.backup.create_source');
    $r->get('/backup-cache/download/{file}', [\App\Controllers\Admin\BackupController::class, 'downloadBackup'])->name('admin.backup.download');
    $r->post('/backup-cache/delete/{file}', [\App\Controllers\Admin\BackupController::class, 'deleteBackup'])->name('admin.backup.delete');
    $r->post('/backup-cache/restore/{file}', [\App\Controllers\Admin\BackupController::class, 'restoreBackup'])->name('admin.backup.restore');
    $r->post('/backup-cache/save-settings', [\App\Controllers\Admin\BackupController::class, 'saveSettings'])->name('admin.backup.save_settings');
    $r->post('/backup-cache/clear-cache', [\App\Controllers\Admin\BackupController::class, 'clearCache'])->name('admin.backup.clear_cache');

    // Maintenance Module
    $r->get('/maintenance', [\App\Controllers\Admin\MaintenanceController::class, 'index'])->name('admin.maintenance.index');
    $r->post('/maintenance/save', [\App\Controllers\Admin\MaintenanceController::class, 'save'])->name('admin.maintenance.save');
    $r->get('/maintenance/preview', [\App\Controllers\Admin\MaintenanceController::class, 'preview'])->name('admin.maintenance.preview');

    $r->get('/payment', [\App\Controllers\Admin\PaymentController::class, 'index'])->name('admin.payment.index');
    $r->get('/payment/create', [\App\Controllers\Admin\PaymentController::class, 'create'])->name('admin.payment.create');
    $r->post('/payment/store', [\App\Controllers\Admin\PaymentController::class, 'store'])->name('admin.payment.store');
    $r->get('/payment/edit/{id}', [\App\Controllers\Admin\PaymentController::class, 'edit'])->name('admin.payment.edit');
    $r->post('/payment/update/{id}', [\App\Controllers\Admin\PaymentController::class, 'update'])->name('admin.payment.update');
    $r->post('/payment/destroy', [\App\Controllers\Admin\PaymentController::class, 'destroy'])->name('admin.payment.destroy');
    $r->post('/payment/update-status-ajax', [\App\Controllers\Admin\PaymentController::class, 'updateStatusAjax'])->name('admin.payment.updateStatusAjax');
    $r->post('/payment/update-sort', [\App\Controllers\Admin\PaymentController::class, 'updateSortAjax'])->name('admin.payment.update_sort');

    // Shipping Configuration Routes
    $r->get('/shipping', [\App\Controllers\Admin\ShippingController::class, 'index'])->name('admin.shipping.index');
    $r->get('/shipping/create', [\App\Controllers\Admin\ShippingController::class, 'createMethod'])->name('admin.shipping.create_method');
    $r->post('/shipping/store', [\App\Controllers\Admin\ShippingController::class, 'storeMethod'])->name('admin.shipping.store_method');
    $r->get('/shipping/edit/{id}', [\App\Controllers\Admin\ShippingController::class, 'editMethod'])->name('admin.shipping.edit_method');
    $r->post('/shipping/update/{id}', [\App\Controllers\Admin\ShippingController::class, 'updateMethod'])->name('admin.shipping.update_method');
    $r->post('/shipping/delete', [\App\Controllers\Admin\ShippingController::class, 'destroyMethod'])->name('admin.shipping.destroy_method');
    $r->post('/shipping/update-status-ajax', [\App\Controllers\Admin\ShippingController::class, 'updateStatusAjax'])->name('admin.shipping.updateStatusAjax');
    
    $r->get('/shipping/{methodId}/rates', [\App\Controllers\Admin\ShippingController::class, 'rates'])->name('admin.shipping.rates');
    $r->get('/shipping/{methodId}/rates/create', [\App\Controllers\Admin\ShippingController::class, 'createRate'])->name('admin.shipping.create_rate');
    $r->post('/shipping/{methodId}/rates/store', [\App\Controllers\Admin\ShippingController::class, 'storeRate'])->name('admin.shipping.store_rate');
    $r->get('/shipping/{methodId}/rates/edit/{rateId}', [\App\Controllers\Admin\ShippingController::class, 'editRate'])->name('admin.shipping.edit_rate');
    $r->post('/shipping/{methodId}/rates/update/{rateId}', [\App\Controllers\Admin\ShippingController::class, 'updateRate'])->name('admin.shipping.update_rate');
    $r->post('/shipping/rates/delete', [\App\Controllers\Admin\ShippingController::class, 'destroyRate'])->name('admin.shipping.destroy_rate');
    $r->post('/shipping/rates/update-status-ajax', [\App\Controllers\Admin\ShippingController::class, 'updateRateStatusAjax'])->name('admin.shipping.updateRateStatusAjax');

    // Promo Code Routes
    $r->get('/promo-code', [\App\Controllers\Admin\PromoCodeController::class, 'index'])->name('admin.promo_code.index');
    $r->get('/promo-code/create', [\App\Controllers\Admin\PromoCodeController::class, 'create'])->name('admin.promo_code.create');
    $r->post('/promo-code/store', [\App\Controllers\Admin\PromoCodeController::class, 'store'])->name('admin.promo_code.store');
    $r->get('/promo-code/edit/{id}', [\App\Controllers\Admin\PromoCodeController::class, 'edit'])->name('admin.promo_code.edit');
    $r->post('/promo-code/update/{id}', [\App\Controllers\Admin\PromoCodeController::class, 'update'])->name('admin.promo_code.update');
    $r->post('/promo-code/delete/{id}', [\App\Controllers\Admin\PromoCodeController::class, 'destroy'])->name('admin.promo_code.destroy');
    $r->post('/promo-code/delete-multiple', [\App\Controllers\Admin\PromoCodeController::class, 'destroyMultiple'])->name('admin.promo_code.destroy_multiple');
    $r->post('/promo-code/update-status-ajax', [\App\Controllers\Admin\PromoCodeController::class, 'updateStatusAjax'])->name('admin.promo_code.updateStatusAjax');
    $r->get('/promo-code/generate-code', [\App\Controllers\Admin\PromoCodeController::class, 'generateCodeAjax'])->name('admin.promo_code.generateCodeAjax');

    // Customer Routes
    $r->get('/customers', [\App\Controllers\Admin\CustomerController::class, 'index'])->name('admin.customer.index');
    $r->get('/customers/create', [\App\Controllers\Admin\CustomerController::class, 'create'])->name('admin.customer.create');
    $r->post('/customers/store', [\App\Controllers\Admin\CustomerController::class, 'store'])->name('admin.customer.store');
    $r->get('/customers/edit/{id}', [\App\Controllers\Admin\CustomerController::class, 'edit'])->name('admin.customer.edit');
    $r->post('/customers/update/{id}', [\App\Controllers\Admin\CustomerController::class, 'update'])->name('admin.customer.update');
    $r->post('/customers/delete/{id}', [\App\Controllers\Admin\CustomerController::class, 'destroy'])->name('admin.customer.destroy');
    $r->post('/customers/delete-multiple', [\App\Controllers\Admin\CustomerController::class, 'destroyMultiple'])->name('admin.customer.destroy_multiple');
    $r->post('/customers/update-status-ajax', [\App\Controllers\Admin\CustomerController::class, 'updateStatusAjax'])->name('admin.customer.updateStatusAjax');

    // Order Routes
    $r->get('/orders', [\App\Controllers\Admin\OrderController::class, 'index'])->name('admin.order.index');
    $r->get('/orders/show/{id}', [\App\Controllers\Admin\OrderController::class, 'show'])->name('admin.order.show');
    $r->post('/orders/update-status/{id}', [\App\Controllers\Admin\OrderController::class, 'updateStatus'])->name('admin.order.updateStatus');
    $r->get('/orders/print/{id}', [\App\Controllers\Admin\OrderController::class, 'print'])->name('admin.order.print');
    // Form Builder (Liên hệ)
    $r->get('/forms', [\App\Controllers\Admin\FormBuilderController::class, 'index'])->name('admin.form.index');
    $r->post('/forms/ajax', [\App\Controllers\Admin\FormBuilderController::class, 'ajax'])->name('admin.form.ajax');
    $r->get('/forms/builder/{id}', [\App\Controllers\Admin\FormBuilderController::class, 'builder'])->name('admin.form.builder');
    $r->get('/forms/preview/{id}', [\App\Controllers\Admin\FormBuilderController::class, 'preview'])->name('admin.form.preview');
    $r->get('/forms/submissions/{id}', [\App\Controllers\Admin\FormBuilderController::class, 'submissions'])->name('admin.form.submissions');
    $r->get('/forms/export/{id}', [\App\Controllers\Admin\FormBuilderController::class, 'export'])->name('admin.form.export');
    $r->post('/forms/submissions/ajax', [\App\Controllers\Admin\FormBuilderController::class, 'submissionAjax'])->name('admin.form.submission_ajax');

});
