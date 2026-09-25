<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Category;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\CashShiftService;
use App\Services\SettingsService;

class PosController extends Controller
{
    public function index(Request $request): void
    {
        $shift = CashShiftService::currentOpenShift(Auth::id());
        if (!$shift) {
            Session::flash('error', 'Buka shift terlebih dahulu sebelum menggunakan POS.');
            $this->redirect('/shift');
            return;
        }

        $paymentMethods = array_map('trim', explode(',', SettingsService::get('transaction.payment_methods', 'Cash')));

        $this->view('pos.index', [
            'title' => 'POS Kasir',
            'shift' => $shift,
            'categories' => Category::all('name'),
            'paymentMethods' => $paymentMethods,
            'customers' => Patient::all('name'),
            'doctors' => Doctor::all('name'),
        ], 'pos');
    }

    public function receipt(Request $request, string $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT s.*, u.full_name AS cashier_name, c.name AS customer_name, d.name AS doctor_name
             FROM sales s
             JOIN users u ON u.id = s.cashier_id
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN doctors d ON d.id = s.doctor_id
             WHERE s.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $sale = $stmt->fetch();

        if (!$sale) {
            http_response_code(404);
            echo 'Struk tidak ditemukan.';
            return;
        }

        $detailStmt = $db->prepare(
            'SELECT sd.*, m.name AS medicine_name, m.code AS medicine_code, u.symbol AS unit_symbol
             FROM sale_details sd
             JOIN medicines m ON m.id = sd.medicine_id
             LEFT JOIN units u ON u.id = m.unit_id
             WHERE sd.sale_id = :id'
        );
        $detailStmt->execute(['id' => $id]);

        $pharmacy = $db->query('SELECT * FROM pharmacies LIMIT 1')->fetch();
        $printer = $db->query('SELECT * FROM printer_settings WHERE is_default = 1 LIMIT 1')->fetch();

        $this->view('pos.receipt', [
            'title' => 'Struk ' . $sale['invoice_number'],
            'sale' => $sale,
            'details' => $detailStmt->fetchAll(),
            'pharmacy' => $pharmacy,
            'printer' => $printer,
        ], null);
    }
}
