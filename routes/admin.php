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
});
