<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\CashShift;
use App\Services\AuditLogger;
use App\Services\CashShiftService;

class CashShiftController extends Controller
{
    public function index(Request $request): void
    {
        $shift = CashShiftService::currentOpenShift(Auth::id());

        $db = Database::connection();
        $history = $db->prepare(
            "SELECT cs.*, u.full_name AS cashier_name FROM cash_shifts cs JOIN users u ON u.id = cs.cashier_id
             WHERE cs.cashier_id = :id ORDER BY cs.id DESC LIMIT 15"
        );
        $history->execute(['id' => Auth::id()]);

        $this->view('cash_shift.index', [
            'title' => 'Shift Kasir',
            'shift' => $shift,
            'summary' => $shift ? CashShiftService::summary((int) $shift['id']) : null,
            'systemBalance' => $shift ? CashShiftService::systemBalance((int) $shift['id']) : 0,
            'history' => $history->fetchAll(),
        ]);
    }

    public function open(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        if (CashShiftService::currentOpenShift(Auth::id())) {
            Session::flash('error', 'Anda masih memiliki shift yang terbuka.');
            $this->redirect('/shift');
            return;
        }

        $openingBalance = (float) $request->input('opening_balance', 0);
        $shiftNumber = 'SHF-' . date('Ymd-His') . '-' . Auth::id();

        $id = CashShift::create([
            'shift_number' => $shiftNumber,
            'cashier_id' => Auth::id(),
            'opening_balance' => $openingBalance,
            'opening_at' => date('Y-m-d H:i:s'),
            'status' => 'open',
        ]);

        AuditLogger::log('create', 'cash_shift', $id, null, ['opening_balance' => $openingBalance]);
        Session::flash('success', 'Shift berhasil dibuka. Selamat berjualan!');
        $this->redirect('/pos');
    }

    public function close(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        $shift = CashShift::find($id);
        if (!$shift || (int) $shift['cashier_id'] !== Auth::id() || $shift['status'] !== 'open') {
            Session::flash('error', 'Shift tidak valid atau sudah ditutup.');
            $this->redirect('/shift');
            return;
        }

        $systemBalance = CashShiftService::systemBalance($id);
        $actualBalance = (float) $request->input('closing_balance_actual', 0);
        $difference = $actualBalance - $systemBalance;

        CashShift::update($id, [
            'closing_balance_system' => $systemBalance,
            'closing_balance_actual' => $actualBalance,
            'difference' => $difference,
            'closing_at' => date('Y-m-d H:i:s'),
            'status' => 'closed',
            'notes' => $request->input('notes') ?: null,
        ]);

        AuditLogger::log('update', 'cash_shift', $id, $shift, [
            'closing_balance_system' => $systemBalance, 'closing_balance_actual' => $actualBalance, 'difference' => $difference,
        ]);

        Session::flash('success', 'Shift berhasil ditutup. Selisih: ' . format_money($difference));
        $this->redirect('/shift');
    }
}
