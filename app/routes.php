<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin'], ['*']);
$router->post('/login', [AuthController::class, 'login'], ['*']);
$router->post('/logout', [AuthController::class, 'logout'], ALL_ROLES);

$router->get('/', [HomeController::class, 'index'], ALL_ROLES);

// ---------------- Danh mục: khách hàng ----------------
$viewMasterData = [ROLE_DIEU_PHOI, ROLE_LANH_DAO];
$editMasterData = [ROLE_DIEU_PHOI];

$router->get('/danh-muc/khach-hang', [DanhMucController::class, 'customersList'], $viewMasterData);
$router->get('/danh-muc/khach-hang/tao', [DanhMucController::class, 'customersCreateForm'], $editMasterData);
$router->post('/danh-muc/khach-hang/tao', [DanhMucController::class, 'customersStore'], $editMasterData);
$router->get('/danh-muc/khach-hang/{id}/sua', [DanhMucController::class, 'customersEditForm'], $editMasterData);
$router->post('/danh-muc/khach-hang/{id}/sua', [DanhMucController::class, 'customersUpdate'], $editMasterData);

// ---------------- Danh mục: loại cà phê ----------------
$router->get('/danh-muc/loai-ca-phe', [DanhMucController::class, 'coffeeTypesList'], $viewMasterData);
$router->get('/danh-muc/loai-ca-phe/tao', [DanhMucController::class, 'coffeeTypesCreateForm'], $editMasterData);
$router->post('/danh-muc/loai-ca-phe/tao', [DanhMucController::class, 'coffeeTypesStore'], $editMasterData);
$router->get('/danh-muc/loai-ca-phe/{id}/sua', [DanhMucController::class, 'coffeeTypesEditForm'], $editMasterData);
$router->post('/danh-muc/loai-ca-phe/{id}/sua', [DanhMucController::class, 'coffeeTypesUpdate'], $editMasterData);

// ---------------- Danh mục: vật tư (Kho full, DP/LD view) ----------------
$viewMaterials = [ROLE_KHO, ROLE_DIEU_PHOI, ROLE_LANH_DAO];
$editMaterials = [ROLE_KHO];

$router->get('/danh-muc/vat-tu', [VatTuController::class, 'list'], $viewMaterials);
$router->get('/danh-muc/vat-tu/tao', [VatTuController::class, 'createForm'], $editMaterials);
$router->post('/danh-muc/vat-tu/tao', [VatTuController::class, 'store'], $editMaterials);
$router->get('/danh-muc/vat-tu/{id}/sua', [VatTuController::class, 'editForm'], $editMaterials);
$router->post('/danh-muc/vat-tu/{id}/sua', [VatTuController::class, 'update'], $editMaterials);

// ---------------- Danh mục: SKU ----------------
$router->get('/danh-muc/sku', [SkuController::class, 'list'], $viewMasterData);
$router->get('/danh-muc/sku/tao', [SkuController::class, 'createForm'], $editMasterData);
$router->post('/danh-muc/sku/tao', [SkuController::class, 'store'], $editMasterData);
$router->get('/danh-muc/sku/{id}/sua', [SkuController::class, 'editForm'], $editMasterData);
$router->post('/danh-muc/sku/{id}/sua', [SkuController::class, 'update'], $editMasterData);

return $router;
