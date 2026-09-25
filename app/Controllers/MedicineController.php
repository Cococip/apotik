<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Medicine;
use App\Models\MedicineGroup;
use App\Models\MedicineType;
use App\Models\Rack;
use App\Models\Unit;
use App\Services\AuditLogger;
use App\Services\MedicineService;

class MedicineController extends Controller
{
    public function index(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id') ? (int) $request->query('category_id') : null;
        $status = $request->query('status') ?: null;

        $result = MedicineService::paginateList($page, 10, $search ?: null, $categoryId, $status);

        $this->view('medicines.index', [
            'title' => 'Daftar Obat',
            'rows' => $result['data'],
            'pagination' => $result,
            'search' => $search,
            'categoryId' => $categoryId,
            'status' => $status,
            'categories' => Category::all('name'),
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('medicines.form', [
            'title' => 'Tambah Obat',
            'medicine' => null,
            'nextCode' => MedicineService::nextCode(),
            'categories' => Category::all('name'),
            'types' => MedicineType::all('name'),
            'groups' => MedicineGroup::all('name'),
            'manufacturers' => Manufacturer::all('name'),
            'units' => Unit::all('name'),
            'racks' => Rack::all('code'),
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        $validator = $this->validate($input, [
            'name' => 'required|max:180',
            'unit_id' => 'required',
            'selling_price' => 'required|numeric',
            'purchase_price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $this->withOldAndErrors($input, $validator);
            $this->redirect('/medicines/create');
            return;
        }

        $data = $this->mapInput($input);

        try {
            $id = Medicine::create($data);
        } catch (\PDOException $e) {
            Session::flash('error', 'Kode atau barcode obat sudah digunakan.');
            flash_old($input);
            $this->redirect('/medicines/create');
            return;
        }

        AuditLogger::log('create', 'medicine', $id, null, $data);
        Session::flash('success', 'Obat berhasil ditambahkan.');
        $this->redirect('/medicines/' . $id . '/edit');
    }

    public function edit(Request $request, string $id): void
    {
        $medicine = MedicineService::findDetailed((int) $id);
        if (!$medicine) {
            Session::flash('error', 'Obat tidak ditemukan.');
            $this->redirect('/medicines');
            return;
        }

        $this->view('medicines.form', [
            'title' => 'Edit Obat',
            'medicine' => $medicine,
            'nextCode' => null,
            'categories' => Category::all('name'),
            'types' => MedicineType::all('name'),
            'groups' => MedicineGroup::all('name'),
            'manufacturers' => Manufacturer::all('name'),
            'units' => Unit::all('name'),
            'racks' => Rack::all('code'),
            'batches' => MedicineService::batchesFor((int) $id),
            'unitConversions' => MedicineService::unitConversionsFor((int) $id),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        $existing = Medicine::find($id);
        if (!$existing) {
            Session::flash('error', 'Obat tidak ditemukan.');
            $this->redirect('/medicines');
            return;
        }

        $input = $request->all();
        $validator = $this->validate($input, [
            'name' => 'required|max:180',
            'unit_id' => 'required',
            'selling_price' => 'required|numeric',
            'purchase_price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $this->withOldAndErrors($input, $validator);
            $this->redirect('/medicines/' . $id . '/edit');
            return;
        }

        $data = $this->mapInput($input);

        try {
            Medicine::update($id, $data);
        } catch (\PDOException $e) {
            Session::flash('error', 'Kode atau barcode obat sudah digunakan oleh obat lain.');
            $this->redirect('/medicines/' . $id . '/edit');
            return;
        }

        AuditLogger::log('update', 'medicine', $id, $existing, $data);
        Session::flash('success', 'Obat berhasil diperbarui.');
        $this->redirect('/medicines/' . $id . '/edit');
    }

    public function destroy(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        $existing = Medicine::find($id);
        if ($existing) {
            Medicine::delete($id);
            AuditLogger::log('delete', 'medicine', $id, $existing, null);
            Session::flash('success', 'Obat berhasil dinonaktifkan.');
        }

        $this->redirect('/medicines');
    }

    public function storeUnitConversion(Request $request, string $medicineId): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $medicineId = (int) $medicineId;
        $input = $request->all();
        $validator = $this->validate($input, [
            'from_unit_id' => 'required',
            'to_unit_id' => 'required',
            'conversion_factor' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('/medicines/' . $medicineId . '/edit');
            return;
        }

        $id = \App\Models\UnitConversion::create([
            'medicine_id' => $medicineId,
            'from_unit_id' => $input['from_unit_id'],
            'to_unit_id' => $input['to_unit_id'],
            'conversion_factor' => $input['conversion_factor'],
        ]);

        AuditLogger::log('create', 'unit_conversion', $id, null, $input);
        Session::flash('success', 'Konversi satuan berhasil ditambahkan.');
        $this->redirect('/medicines/' . $medicineId . '/edit');
    }

    public function destroyUnitConversion(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $conversion = \App\Models\UnitConversion::find((int) $id);
        $medicineId = $conversion['medicine_id'] ?? null;
        if ($conversion) {
            \App\Models\UnitConversion::delete((int) $id);
            AuditLogger::log('delete', 'unit_conversion', (int) $id, $conversion, null);
        }
        Session::flash('success', 'Konversi satuan berhasil dihapus.');
        $this->redirect($medicineId ? '/medicines/' . $medicineId . '/edit' : '/medicines');
    }

    private function mapInput(array $input): array
    {
        return [
            'code' => $input['code'] ?: MedicineService::nextCode(),
            'barcode' => $input['barcode'] ?: null,
            'name' => $input['name'],
            'generic_name' => $input['generic_name'] ?? null,
            'brand_name' => $input['brand_name'] ?? null,
            'category_id' => $input['category_id'] ?: null,
            'medicine_type_id' => $input['medicine_type_id'] ?: null,
            'medicine_group_id' => $input['medicine_group_id'] ?: null,
            'manufacturer_id' => $input['manufacturer_id'] ?: null,
            'unit_id' => $input['unit_id'],
            'large_unit_id' => $input['large_unit_id'] ?: null,
            'content_per_unit' => $input['content_per_unit'] ?: 1,
            'purchase_price' => $input['purchase_price'],
            'selling_price' => $input['selling_price'],
            'selling_price_prescription' => $input['selling_price_prescription'] ?: $input['selling_price'],
            'minimum_stock' => $input['minimum_stock'] ?: 0,
            'maximum_stock' => $input['maximum_stock'] ?: 0,
            'rack_id' => $input['rack_id'] ?: null,
            'description' => $input['description'] ?? null,
            'status' => $input['status'] ?? 'active',
        ];
    }
}
