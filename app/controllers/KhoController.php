<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class KhoController extends Controller
{
    public function stock(): void
    {
        $summary = (new StockLedgerModel())->stockSummary();
        $this->view('kho/ton_kho', ['summary' => $summary]);
    }

    public function materialDetail(string $materialId): void
    {
        $material = (new MaterialModel())->find((int) $materialId);
        if (!$material) {
            $this->abort404();
        }
        $ledger = new StockLedgerModel();
        $history = $ledger->historyForMaterial((int) $materialId);
        $reservations = (new MaterialReservationModel())->activeForMaterial((int) $materialId);
        $stock = $ledger->currentStock((int) $materialId);
        $reserved = $ledger->reservedActive((int) $materialId);

        $this->view('kho/material_detail', [
            'material' => $material,
            'history' => $history,
            'reservations' => $reservations,
            'stock' => $stock,
            'reserved' => $reserved,
            'available' => $stock - $reserved,
        ]);
    }

    public function voucherList(): void
    {
        $vouchers = (new StockVoucherModel())->allRecent();
        $this->view('kho/phieu_list', ['vouchers' => $vouchers]);
    }

    public function voucherCreateForm(): void
    {
        $materials = (new MaterialModel())->allActiveWithGroup();
        $this->view('kho/phieu_form', ['materials' => $materials, 'errors' => []]);
    }

    public function voucherStore(): void
    {
        $this->requireCsrf();
        $materials = (new MaterialModel())->allActiveWithGroup();

        $type = (string) $this->input('voucher_type', '');
        $voucherNo = trim((string) $this->input('voucher_no', ''));
        $voucherNo = $voucherNo === '' ? null : $voucherNo;
        $note = trim((string) $this->input('note', ''));
        $note = $note === '' ? null : $note;

        $materialIds = $this->input('material_id', []);
        $quantities = $this->input('quantity', []);

        $validator = new Validator();
        if (!in_array($type, ['nhap', 'tra', 'hong', 'dieu_chinh'], true)) {
            $validator->addError('voucher_type', 'Vui lòng chọn loại phiếu.');
        }

        $lines = [];
        if (is_array($materialIds)) {
            foreach ($materialIds as $i => $materialId) {
                $qty = $quantities[$i] ?? '';
                if ($materialId === '' || $qty === '') {
                    continue;
                }
                $lines[] = ['material_id' => (int) $materialId, 'quantity' => (float) $qty];
            }
        }
        if (!$lines) {
            $validator->addError('lines', 'Vui lòng nhập ít nhất 1 dòng vật tư với số lượng.');
        }

        if ($validator->fails()) {
            $this->view('kho/phieu_form', ['materials' => $materials, 'errors' => $validator->errors()]);
            return;
        }

        $voucherId = (new StockVoucherModel())->createWithLines($type, $voucherNo, $note, $lines, (int) Auth::user()['id']);
        flash('success', 'Đã ghi nhận phiếu kho.');
        $this->redirect("/kho/phieu/{$voucherId}");
    }

    public function voucherDetail(string $id): void
    {
        $model = new StockVoucherModel();
        $voucher = $model->find((int) $id);
        if (!$voucher) {
            $this->abort404();
        }
        $lines = $model->linesForVoucher((int) $id);
        $this->view('kho/phieu_detail', ['voucher' => $voucher, 'lines' => $lines]);
    }
}
