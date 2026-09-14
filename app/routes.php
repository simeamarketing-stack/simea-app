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

// ---------------- BOM & định mức (DP full, Xưởng/LD view) ----------------
$viewBom = [ROLE_DIEU_PHOI, ROLE_XUONG, ROLE_LANH_DAO];
$editBom = [ROLE_DIEU_PHOI];

$router->get('/bom/sku/{skuId}', [BomController::class, 'listForSku'], $viewBom);
$router->post('/bom/sku/{skuId}/tao-phien-ban-moi', [BomController::class, 'createVersion'], $editBom);
$router->get('/bom/{id}', [BomController::class, 'show'], $viewBom);
$router->post('/bom/{id}/dong', [BomController::class, 'addLine'], $editBom);
$router->post('/bom/line/{lineId}/sua', [BomController::class, 'updateLine'], $editBom);
$router->post('/bom/line/{lineId}/xoa', [BomController::class, 'deleteLine'], $editBom);
$router->post('/bom/{id}/case-spec', [BomController::class, 'saveCaseSpec'], $editBom);
$router->post('/bom/{id}/duyet', [BomController::class, 'approve'], $editBom);

$router->get('/dinh-muc/sku/{skuId}', [DinhMucController::class, 'listForSku'], $viewBom);
$router->post('/dinh-muc/sku/{skuId}/tao-phien-ban-moi', [DinhMucController::class, 'createVersion'], $editBom);
$router->get('/dinh-muc/{id}', [DinhMucController::class, 'show'], $viewBom);
$router->post('/dinh-muc/{id}/sua', [DinhMucController::class, 'update'], $editBom);
$router->post('/dinh-muc/{id}/duyet', [DinhMucController::class, 'approve'], $editBom);

// ---------------- Kho vật tư ----------------
$viewStock = [ROLE_KHO, ROLE_DIEU_PHOI, ROLE_XUONG, ROLE_LANH_DAO];
$editStock = [ROLE_KHO];

$router->get('/kho/ton-kho', [KhoController::class, 'stock'], $viewStock);
$router->get('/kho/vat-tu/{materialId}', [KhoController::class, 'materialDetail'], $viewStock);
$router->get('/kho/phieu', [KhoController::class, 'voucherList'], $editStock);
$router->get('/kho/phieu/tao', [KhoController::class, 'voucherCreateForm'], $editStock);
$router->post('/kho/phieu/tao', [KhoController::class, 'voucherStore'], $editStock);
$router->get('/kho/phieu/{id}', [KhoController::class, 'voucherDetail'], $editStock);

// ---------------- Lệnh sản xuất ----------------
$router->get('/lenh-san-xuat', [LenhSanXuatController::class, 'list'], ALL_ROLES);
$router->get('/lenh-san-xuat/tao', [LenhSanXuatController::class, 'createForm'], [ROLE_DIEU_PHOI]);
$router->post('/lenh-san-xuat/tao', [LenhSanXuatController::class, 'store'], [ROLE_DIEU_PHOI]);
$router->get('/lenh-san-xuat/{id}', [LenhSanXuatController::class, 'show'], ALL_ROLES);
$router->post('/lenh-san-xuat/{id}/cap-nhat-thong-tin', [LenhSanXuatController::class, 'updateInfo'], [ROLE_DIEU_PHOI]);
$router->post('/lenh-san-xuat/{id}/phat-hanh', [LenhSanXuatController::class, 'release'], [ROLE_DIEU_PHOI]);
$router->post('/lenh-san-xuat/{id}/xac-nhan-lich', [LenhSanXuatController::class, 'confirmSchedule'], [ROLE_XUONG]);
$router->post('/lenh-san-xuat/{id}/doi-lich', [LenhSanXuatController::class, 'reschedule'], [ROLE_XUONG]);
$router->post('/lenh-san-xuat/{id}/gan-nhan-su', [LenhSanXuatController::class, 'assign'], [ROLE_XUONG]);
$router->post('/lenh-san-xuat/{id}/cap-nhat-trang-thai', [LenhSanXuatController::class, 'updateStatus'], [ROLE_XUONG]);
$router->post('/lenh-san-xuat/{id}/giu-cho', [LenhSanXuatController::class, 'reserve'], [ROLE_DIEU_PHOI, ROLE_KHO]);
$router->post('/lenh-san-xuat/reservation/{reservationId}/huy', [LenhSanXuatController::class, 'releaseReservation'], [ROLE_DIEU_PHOI, ROLE_KHO]);
$router->post('/lenh-san-xuat/reservation/{reservationId}/xuat', [LenhSanXuatController::class, 'issueReservation'], [ROLE_KHO]);

// ---------------- Báo cáo ca ----------------
$router->get('/bao-cao-ca', [BaoCaoCaController::class, 'list'], ALL_ROLES);
$router->get('/bao-cao-ca/tao', [BaoCaoCaController::class, 'createForm'], [ROLE_XUONG]);
$router->post('/bao-cao-ca/tao', [BaoCaoCaController::class, 'store'], [ROLE_XUONG]);
$router->get('/bao-cao-ca/{id}', [BaoCaoCaController::class, 'show'], ALL_ROLES);
$router->post('/bao-cao-ca/{id}/cap-nhat', [BaoCaoCaController::class, 'update'], [ROLE_XUONG, ROLE_DIEU_PHOI]);
$router->post('/bao-cao-ca/{id}/qc-xac-nhan', [BaoCaoCaController::class, 'confirmQc'], [ROLE_QC]);
$router->post('/bao-cao-ca/{id}/khoa', [BaoCaoCaController::class, 'lock'], [ROLE_XUONG, ROLE_QC]);

return $router;
