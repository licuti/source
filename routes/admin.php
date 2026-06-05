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
    
    // Dashboard (Yêu cầu đăng nhập, sẽ bị AdminAuthMiddleware kiểm tra)
    $r->get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Category Routes
    $r->get('/category', [\App\Controllers\Admin\CategoryController::class, 'index'])->name('admin.category.index');
    $r->get('/category/create', [\App\Controllers\Admin\CategoryController::class, 'create'])->name('admin.category.create');
    $r->post('/category/store', [\App\Controllers\Admin\CategoryController::class, 'store'])->name('admin.category.store');
    $r->get('/category/edit/{id}', [\App\Controllers\Admin\CategoryController::class, 'edit'])->name('admin.category.edit');
    $r->post('/category/update/{id}', [\App\Controllers\Admin\CategoryController::class, 'update'])->name('admin.category.update');
    $r->get('/category/delete/{id}', [\App\Controllers\Admin\CategoryController::class, 'destroy'])->name('admin.category.destroy');
    $r->post('/category/delete-multiple', [\App\Controllers\Admin\CategoryController::class, 'destroyMultiple'])->name('admin.category.destroy_multiple');

    // Attribute Routes
    $r->get('/attribute', [\App\Controllers\Admin\AttributeController::class, 'index'])->name('admin.attribute.index');
    $r->get('/attribute/create', [\App\Controllers\Admin\AttributeController::class, 'create'])->name('admin.attribute.create');
    $r->post('/attribute/store', [\App\Controllers\Admin\AttributeController::class, 'store'])->name('admin.attribute.store');
    $r->get('/attribute/edit/{id}', [\App\Controllers\Admin\AttributeController::class, 'edit'])->name('admin.attribute.edit');
    $r->post('/attribute/update/{id}', [\App\Controllers\Admin\AttributeController::class, 'update'])->name('admin.attribute.update');
    $r->get('/attribute/delete/{id}', [\App\Controllers\Admin\AttributeController::class, 'destroy'])->name('admin.attribute.destroy');

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
