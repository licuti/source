<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!-- Brand Logo -->
    <div class="sidebar-brand">
        <a href="<?= route('admin.dashboard') ?>" class="brand-link">
            <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="Logo" class="brand-image opacity-75 shadow">
            <span class="brand-text fw-light">CMS Panel</span>
        </a>
    </div>

    <!-- Sidebar Menu -->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="<?= route('admin.dashboard') ?>" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/dashboard') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fa-solid fa-gauge-high"></i>
                        <p>Bảng điều khiển</p>
                    </a>
                </li>
                
                <?php
                // RBAC: Lấy danh sách module được phép xem
                $isAdmin = session('is_admin') == 1;
                $roleId = session('role_id') ?? 0;
                $allowedModuleIds = [];
                
                if (!$isAdmin && $roleId > 0) {
                    if (isset($_SESSION['role_permissions'])) {
                        foreach ($_SESSION['role_permissions'] as $mId => $actions) {
                            if (!empty($actions['can_view'])) {
                                $allowedModuleIds[] = $mId;
                            }
                        }
                    } else {
                        // Fallback
                        $perms = \App\Models\RolePermissionModel::where('role_id', $roleId)->where('can_view', 1)->get();
                        foreach ($perms as $p) {
                            $allowedModuleIds[] = $p->module_id;
                        }
                    }
                }
                
                $mainModules = \App\Models\ModuleAdminModel::where('parent', 0)
                    ->where('is_active', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->get();
                ?>
                
                <?php foreach ($mainModules as $main): ?>
                    <?php
                    $subModulesRaw = \App\Models\ModuleAdminModel::where('parent', $main->id)
                        ->where('is_active', 1)
                        ->orderBy('sort_order', 'ASC')
                        ->get();
                        
                    // Lọc SubModules theo quyền
                    $subModules = [];
                    $hasVisibleChild = false;
                    foreach ($subModulesRaw as $sub) {
                        if ($isAdmin || in_array($sub->id, $allowedModuleIds)) {
                            $subModules[] = $sub;
                            $hasVisibleChild = true;
                        }
                    }
                    
                    // Bỏ qua nếu không có quyền xem parent VÀ cũng không có child nào được phép xem
                    if (!$isAdmin && !in_array($main->id, $allowedModuleIds) && !$hasVisibleChild) continue;
                    
                    $hasSub = count($subModules) > 0;
                    
                    // Check active state
                    $isActive = false;
                    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
                    
                    if ($hasSub) {
                        foreach ($subModules as $sub) {
                            // Ưu tiên check theo route_name, nếu không có thì check theo alias
                            $checkPath = !empty($sub->alias) ? $sub->alias : $sub->route_name;
                            if (!empty($checkPath) && strpos($requestUri, '/admin/' . $checkPath) !== false) {
                                $isActive = true;
                                break;
                            }
                        }
                    } else {
                        $checkPath = !empty($main->alias) ? $main->alias : $main->route_name;
                        if (!empty($checkPath) && strpos($requestUri, '/admin/' . $checkPath) !== false) {
                            $isActive = true;
                        }
                    }
                    ?>
                    
                    <?php if ($hasSub): ?>
                        <li class="nav-item <?= $isActive ? 'menu-open' : '' ?>">
                            <a href="#" class="nav-link <?= $isActive ? 'active' : '' ?>">
                                <i class="nav-icon fa-solid <?= htmlspecialchars($main->icon ?: 'fa-box') ?>"></i>
                                <p>
                                    <?= htmlspecialchars($main->name) ?>
                                    <i class="nav-arrow fa-solid fa-angle-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php foreach ($subModules as $sub): ?>
                                    <?php 
                                        $checkPath = !empty($sub->alias) ? $sub->alias : $sub->route_name;
                                        $subActive = (!empty($checkPath) && strpos($requestUri, '/admin/' . $checkPath) !== false);
                                        
                                        // Try to resolve route, fallback to legacy URL if route doesn't exist
                                        try {
                                            $router = \App\Core\App::getInstance()->router;
                                            // Sử dụng route_name nếu có, ngược lại dùng fallback admin.{alias}.index
                                            $routeName = $sub->route_name ?: 'admin.' . $sub->alias . '.index';
                                            $subUrl = $router->getNamedRoute($routeName);
                                            if (!$subUrl) {
                                                // Fallback to legacy URL
                                                $subUrl = url('admin/index.php?com=' . $sub->alias . '&act=man');
                                            } else {
                                                $subUrl = route($routeName);
                                            }
                                        } catch (\Exception $e) {
                                            $subUrl = '#';
                                        }
                                    ?>
                                    <li class="nav-item">
                                        <a href="<?= $subUrl ?>" class="nav-link <?= $subActive ? 'active' : '' ?>">
                                            <i class="nav-icon fa-regular <?= htmlspecialchars($sub->icon ?: 'fa-circle') ?>"></i>
                                            <p><?= htmlspecialchars($sub->name) ?></p>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <?php 
                            try {
                                $router = \App\Core\App::getInstance()->router;
                                $routeName = $main->route_name ?: 'admin.' . $main->alias . '.index';
                                $mainUrl = $router->getNamedRoute($routeName);
                                if (!$mainUrl) {
                                    $mainUrl = url('admin/index.php?com=' . $main->alias . '&act=man');
                                } else {
                                    $mainUrl = route($routeName);
                                }
                            } catch (\Exception $e) {
                                $mainUrl = '#';
                            }
                        ?>
                        <li class="nav-item">
                            <a href="<?= $mainUrl ?>" class="nav-link <?= $isActive ? 'active' : '' ?>">
                                <i class="nav-icon fa-solid <?= htmlspecialchars($main->icon ?: 'fa-box') ?>"></i>
                                <p><?= htmlspecialchars($main->name) ?></p>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</aside>
