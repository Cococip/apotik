<?php

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Services\CashShiftService;
use App\Services\CheckoutException;
use App\Services\SalesService;

class PosApiController extends Controller
{
    public function search(Request $request): void
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id') ? (int) $request->query('category_id') : null;

        $where = ["m.deleted_at IS NULL", "m.status = 'active'"];
        $params = [];
        if ($q !== '') {
            $where[] = '(m.name LIKE :q1 OR m.code LIKE :q2 OR m.generic_name LIKE :q3 OR m.barcode LIKE :q4)';
            $like = '%' . $q . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }
        if ($categoryId) {
            $where[] = 'm.category_id = :cat';
            $params['cat'] = $categoryId;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $stmt = Database::connection()->prepare(
            "SELECT m.id, m.code, m.name, m.barcode, m.selling_price, m.selling_price_prescription, u.symbol AS unit_symbol,
                    mg.requires_prescription,
                    COALESCE((SELECT SUM(mb.available_qty) FROM medicine_batches mb WHERE mb.medicine_id = m.id AND mb.status = 'active' AND mb.expired_date >= CURDATE()), 0) AS stock
             FROM medicines m
             LEFT JOIN units u ON u.id = m.unit_id
             LEFT JOIN medicine_groups mg ON mg.id = m.medicine_group_id
             {$whereSql}
             ORDER BY m.name ASC LIMIT 40"
        );
        $stmt->execute($params);

        $this->json(['success' => true, 'message' => 'OK', 'errors' => [], 'data' => $stmt->fetchAll()]);
    }

    /**
     * Full active-medicine catalog for the offline POS cache (§28/§32 —
     * IndexedDB needs a complete snapshot, not just a 40-row search page).
     */
    public function catalog(Request $request): void
    {
        $stmt = Database::connection()->query(
            "SELECT m.id, m.code, m.name, m.barcode, m.selling_price, m.selling_price_prescription, u.symbol AS unit_symbol,
                    mg.requires_prescription,
                    COALESCE((SELECT SUM(mb.available_qty) FROM medicine_batches mb WHERE mb.medicine_id = m.id AND mb.status = 'active' AND mb.expired_date >= CURDATE()), 0) AS stock
             FROM medicines m
             LEFT JOIN units u ON u.id = m.unit_id
             LEFT JOIN medicine_groups mg ON mg.id = m.medicine_group_id
             WHERE m.deleted_at IS NULL AND m.status = 'active'
             ORDER BY m.name ASC"
        );

        $this->json(['success' => true, 'message' => 'OK', 'errors' => [], 'data' => $stmt->fetchAll(), 'generated_at' => date('c')]);
    }

    public function barcodeLookup(Request $request, string $code): void
    {
        $stmt = Database::connection()->prepare(
            "SELECT m.id, m.code, m.name, m.barcode, m.selling_price, m.selling_price_prescription, u.symbol AS unit_symbol,
                    mg.requires_prescription,
                    COALESCE((SELECT SUM(mb.available_qty) FROM medicine_batches mb WHERE mb.medicine_id = m.id AND mb.status = 'active' AND mb.expired_date >= CURDATE()), 0) AS stock
             FROM medicines m
             LEFT JOIN units u ON u.id = m.unit_id
             LEFT JOIN medicine_groups mg ON mg.id = m.medicine_group_id
             WHERE (m.barcode = :code1 OR m.id IN (SELECT medicine_id FROM medicine_barcodes WHERE barcode = :code2))
             AND m.deleted_at IS NULL AND m.status = 'active' LIMIT 1"
        );
        $stmt->execute(['code1' => $code, 'code2' => $code]);
        $medicine = $stmt->fetch();

        if (!$medicine) {
            $this->json(['success' => false, 'message' => 'Barcode tidak ditemukan.', 'errors' => [], 'data' => null], 404);
            return;
        }

        $this->json(['success' => true, 'message' => 'OK', 'errors' => [], 'data' => $medicine]);
    }

    public function checkout(Request $request): void
    {
        $body = $request->isJson() ? $request->jsonBody() : $request->all();

        if (!Csrf::verify($body['_csrf_token'] ?? null)) {
            $this->json(['success' => false, 'message' => 'Sesi tidak valid, silakan muat ulang halaman.', 'errors' => [], 'data' => null], 419);
            return;
        }

        $shift = CashShiftService::currentOpenShift(Auth::id());
        if (!$shift) {
            $this->json(['success' => false, 'message' => 'Shift kasir belum dibuka.', 'errors' => [], 'data' => null], 422);
            return;
        }

        try {
            $result = SalesService::checkout([
                'items' => $body['items'] ?? [],
                'payment_method' => $body['payment_method'] ?? 'Cash',
                'paid_amount' => $body['paid_amount'] ?? 0,
                'discount' => $body['discount'] ?? 0,
                'customer_id' => $body['customer_id'] ?? null,
                'doctor_id' => $body['doctor_id'] ?? null,
                'prescription_id' => $body['prescription_id'] ?? null,
                'notes' => $body['notes'] ?? null,
                'cashier_id' => Auth::id(),
                'shift_id' => (int) $shift['id'],
                'device_id' => $body['device_id'] ?? null,
                'uuid' => $body['uuid'] ?? null,
            ]);
        } catch (CheckoutException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage(), 'errors' => [], 'data' => null], 422);
            return;
        } catch (\Throwable $e) {
            \App\Core\Logger::error('POS checkout failed: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Terjadi kesalahan saat memproses transaksi. Silakan coba lagi.', 'errors' => [], 'data' => null], 500);
            return;
        }

        $this->json(['success' => true, 'message' => 'Transaksi berhasil.', 'errors' => [], 'data' => $result]);
    }
}
