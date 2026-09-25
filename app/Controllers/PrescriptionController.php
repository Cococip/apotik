<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AuditLogger;

class PrescriptionController extends Controller
{
    private array $transitions = [
        'draft' => ['pending_verification', 'cancelled'],
        'pending_verification' => ['verified', 'cancelled'],
        'verified' => ['processed', 'cancelled'],
        'processed' => ['completed', 'cancelled'],
    ];

    public function index(Request $request): void
    {
        $status = $request->query('status', '');
        $where = $status ? 'WHERE p.status = :status' : '';
        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT p.*, c.name AS customer_name, d.name AS doctor_name, u.full_name AS input_by_name
             FROM prescriptions p
             LEFT JOIN customers c ON c.id = p.customer_id
             LEFT JOIN doctors d ON d.id = p.doctor_id
             LEFT JOIN users u ON u.id = p.input_by
             {$where}
             ORDER BY p.id DESC LIMIT 50"
        );
        $stmt->execute($status ? ['status' => $status] : []);

        $this->view('prescriptions.index', ['title' => 'Resep', 'rows' => $stmt->fetchAll(), 'status' => $status]);
    }

    public function create(Request $request): void
    {
        $this->view('prescriptions.form', [
            'title' => 'Input Resep Baru',
            'customers' => Patient::all('name'),
            'doctors' => Doctor::all('name'),
            'medicines' => \App\Models\Medicine::where(['status' => 'active']),
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        if (empty($input['customer_id']) || empty($input['doctor_id'])) {
            Session::flash('error', 'Pasien dan dokter wajib dipilih.');
            $this->redirect('/prescriptions/create');
            return;
        }

        $medicineIds = (array) $request->input('medicine_id', []);
        $dosages = (array) $request->input('dosage', []);
        $frequencies = (array) $request->input('frequency', []);
        $qtys = (array) $request->input('qty', []);
        $instructions = (array) $request->input('usage_instructions', []);

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $prescNumber = 'RSP-' . date('Ymd-His');
            $stmt = $db->prepare(
                'INSERT INTO prescriptions (prescription_number, prescription_date, customer_id, doctor_id, notes, status, input_by)
                 VALUES (:num, CURDATE(), :customer_id, :doctor_id, :notes, "pending_verification", :input_by)'
            );
            $stmt->execute([
                'num' => $prescNumber,
                'customer_id' => $input['customer_id'],
                'doctor_id' => $input['doctor_id'],
                'notes' => $input['notes'] ?? null,
                'input_by' => Auth::id(),
            ]);
            $prescId = (int) $db->lastInsertId();

            $insertDetail = $db->prepare(
                'INSERT INTO prescription_details (prescription_id, medicine_id, dosage, frequency, qty, usage_instructions, note)
                 VALUES (:presc_id, :medicine_id, :dosage, :frequency, :qty, :instructions, :note)'
            );
            $hasItem = false;
            foreach ($medicineIds as $i => $medicineId) {
                if (!$medicineId || empty($qtys[$i])) {
                    continue;
                }
                $insertDetail->execute([
                    'presc_id' => $prescId,
                    'medicine_id' => $medicineId,
                    'dosage' => $dosages[$i] ?? null,
                    'frequency' => $frequencies[$i] ?? null,
                    'qty' => $qtys[$i],
                    'instructions' => $instructions[$i] ?? null,
                    'note' => null,
                ]);
                $hasItem = true;
            }

            if (!$hasItem) {
                throw new \RuntimeException('Minimal satu item obat pada resep harus diisi.');
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', $e->getMessage() ?: 'Gagal menyimpan resep.');
            $this->redirect('/prescriptions/create');
            return;
        }

        AuditLogger::log('create', 'prescription', $prescId, null, ['prescription_number' => $prescNumber]);
        Session::flash('success', 'Resep berhasil diinput, menunggu verifikasi apoteker.');
        $this->redirect('/prescriptions/' . $prescId);
    }

    public function show(Request $request, string $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT p.*, c.name AS customer_name, c.patient_number, d.name AS doctor_name,
                    ui.full_name AS input_by_name, uv.full_name AS verified_by_name
             FROM prescriptions p
             LEFT JOIN customers c ON c.id = p.customer_id
             LEFT JOIN doctors d ON d.id = p.doctor_id
             LEFT JOIN users ui ON ui.id = p.input_by
             LEFT JOIN users uv ON uv.id = p.verified_by
             WHERE p.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $prescription = $stmt->fetch();

        if (!$prescription) {
            Session::flash('error', 'Resep tidak ditemukan.');
            $this->redirect('/prescriptions');
            return;
        }

        $detailStmt = $db->prepare('SELECT pd.*, m.name AS medicine_name FROM prescription_details pd JOIN medicines m ON m.id = pd.medicine_id WHERE pd.prescription_id = :id');
        $detailStmt->execute(['id' => $id]);

        $this->view('prescriptions.show', [
            'title' => 'Resep ' . $prescription['prescription_number'],
            'prescription' => $prescription,
            'details' => $detailStmt->fetchAll(),
            'nextStatuses' => $this->transitions[$prescription['status']] ?? [],
        ]);
    }

    public function transition(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        $newStatus = $request->input('status');
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM prescriptions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $prescription = $stmt->fetch();

        if (!$prescription) {
            Session::flash('error', 'Resep tidak ditemukan.');
            $this->redirect('/prescriptions');
            return;
        }

        $allowed = $this->transitions[$prescription['status']] ?? [];
        if (!in_array($newStatus, $allowed, true)) {
            Session::flash('error', 'Perubahan status tidak valid.');
            $this->redirect('/prescriptions/' . $id);
            return;
        }

        if ($newStatus === 'verified' && !Auth::can('prescription.verify')) {
            Session::flash('error', 'Anda tidak memiliki izin untuk memverifikasi resep.');
            $this->redirect('/prescriptions/' . $id);
            return;
        }

        $params = ['status' => $newStatus, 'id' => $id];
        $sql = 'UPDATE prescriptions SET status = :status';
        if ($newStatus === 'verified') {
            $sql .= ', verified_by = :verified_by, verified_at = NOW()';
            $params['verified_by'] = Auth::id();
        }
        $sql .= ' WHERE id = :id';
        $db->prepare($sql)->execute($params);

        AuditLogger::log('update', 'prescription', $id, $prescription, ['status' => $newStatus]);
        Session::flash('success', 'Status resep berhasil diperbarui.');
        $this->redirect('/prescriptions/' . $id);
    }
}
