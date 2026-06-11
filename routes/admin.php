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

    // System Sidebar Menu Management Routes (Drag & Drop)
    $r->get('/system-menu', [\App\Controllers\Admin\MenuAdminController::class, 'index'])->name('admin.system_menu.index');
    $r->post('/system-menu/store', [\App\Controllers\Admin\MenuAdminController::class, 'store'])->name('admin.system_menu.store');
    $r->get('/system-menu/edit/{id}', [\App\Controllers\Admin\MenuAdminController::class, 'edit'])->name('admin.system_menu.edit');
    $r->post('/system-menu/update/{id}', [\App\Controllers\Admin\MenuAdminController::class, 'update'])->name('admin.system_menu.update');
    $r->get('/system-menu/delete/{id}', [\App\Controllers\Admin\MenuAdminController::class, 'destroy'])->name('admin.system_menu.destroy');
    $r->post('/system-menu/update-sort-ajax', [\App\Controllers\Admin\MenuAdminController::class, 'updateSortAjax'])->name('admin.system_menu.updateSortAjax');

    // Category Routes
    $r->get('/category', [\App\Controllers\Admin\CategoryController::class, 'index'])->name('admin.category.index');
    $r->get('/category/create', [\App\Controllers\Admin\CategoryController::class, 'create'])->name('admin.category.create');
    $r->post('/category/store', [\App\Controllers\Admin\CategoryController::class, 'store'])->name('admin.category.store');
    $r->get('/category/edit/{id}', [\App\Controllers\Admin\CategoryController::class, 'edit'])->name('admin.category.edit');
    $r->post('/category/update/{id}', [\App\Controllers\Admin\CategoryController::class, 'update'])->name('admin.category.update');
    $r->get('/category/delete/{id}', [\App\Controllers\Admin\CategoryController::class, 'destroy'])->name('admin.category.destroy');
    $r->post('/category/delete-multiple', [\App\Controllers\Admin\CategoryController::class, 'destroyMultiple'])->name('admin.category.destroy_multiple');
    $r->post('/category/update-status-ajax', [\App\Controllers\Admin\CategoryController::class, 'updateStatusAjax'])->name('admin.category.updateStatusAjax');

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

    $r->get('/api-integration', [\App\Controllers\Admin\ApiIntegrationController::class, 'index'])->name('admin.api_integration.index');
    $r->post('/api-integration/save', [\App\Controllers\Admin\ApiIntegrationController::class, 'save'])->name('admin.api_integration.save');

    $r->get('/language', [\App\Controllers\Admin\LanguageSettingController::class, 'index'])->name('admin.language.index');
    $r->get('/language/create', [\App\Controllers\Admin\LanguageSettingController::class, 'create'])->name('admin.language.create');
    $r->post('/language/store', [\App\Controllers\Admin\LanguageSettingController::class, 'store'])->name('admin.language.store');
    $r->get('/language/edit/{id}', [\App\Controllers\Admin\LanguageSettingController::class, 'edit'])->name('admin.language.edit');
    $r->post('/language/update/{id}', [\App\Controllers\Admin\LanguageSettingController::class, 'update'])->name('admin.language.update');
    $r->get('/language/delete/{id}', [\App\Controllers\Admin\LanguageSettingController::class, 'destroy'])->name('admin.language.destroy');

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
    $r->post('/backup-cache/save', [\App\Controllers\Admin\BackupController::class, 'save'])->name('admin.backup.save');

    $r->get('/maintenance', [\App\Controllers\Admin\MaintenanceController::class, 'index'])->name('admin.maintenance.index');
    $r->post('/maintenance/save', [\App\Controllers\Admin\MaintenanceController::class, 'save'])->name('admin.maintenance.save');

    $r->get('/payment', [\App\Controllers\Admin\PaymentSettingController::class, 'index'])->name('admin.payment.index');
    $r->post('/payment/save', [\App\Controllers\Admin\PaymentSettingController::class, 'save'])->name('admin.payment.save');
});
