<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Pharmacy;
use App\Models\PrinterSetting;
use App\Services\AuditLogger;
use App\Services\SettingsService;

class SettingsController extends Controller
{
    public function pharmacy(Request $request): void
    {
        $pharmacy = Pharmacy::all()[0] ?? null;
        $this->view('settings.pharmacy', ['title' => 'Profil Apotek', 'pharmacy' => $pharmacy]);
    }

    public function updatePharmacy(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        $validator = $this->validate($input, ['name' => 'required|max:150']);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('/settings/pharmacy');
            return;
        }

        $data = [
            'name' => $input['name'],
            'address' => $input['address'] ?? null,
            'phone' => $input['phone'] ?? null,
            'email' => $input['email'] ?? null,
            'npwp' => $input['npwp'] ?? null,
            'receipt_footer' => $input['receipt_footer'] ?? null,
        ];

        $existing = Pharmacy::all();
        if (empty($existing)) {
            $id = Pharmacy::create($data);
        } else {
            $id = (int) $existing[0]['id'];
            Pharmacy::update($id, $data);
        }

        SettingsService::set('pharmacy.name', $input['name'], 'pharmacy');

        AuditLogger::log('update', 'pharmacy_profile', $id, $existing[0] ?? null, $data);
        Session::flash('success', 'Profil apotek berhasil diperbarui.');
        $this->redirect('/settings/pharmacy');
    }

    public function system(Request $request): void
    {
        $this->view('settings.system', [
            'title' => 'Pengaturan Sistem',
            'settings' => SettingsService::all(),
        ]);
    }

    public function updateSystem(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        SettingsService::setMany([
            'system.timezone' => $input['timezone'] ?? 'Asia/Jakarta',
            'system.currency' => $input['currency'] ?? 'IDR',
            'system.date_format' => $input['date_format'] ?? 'd/m/Y',
        ], 'system');

        AuditLogger::log('update', 'settings_system', null, null, $input);
        Session::flash('success', 'Pengaturan sistem berhasil disimpan.');
        $this->redirect('/settings/system');
    }

    public function inventory(Request $request): void
    {
        $this->view('settings.inventory', [
            'title' => 'Pengaturan Persediaan',
            'settings' => SettingsService::all(),
        ]);
    }

    public function updateInventory(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        SettingsService::setMany([
            'inventory.default_min_stock' => (int) ($input['default_min_stock'] ?? 10),
            'inventory.expiry_watch_days' => (int) ($input['expiry_watch_days'] ?? 90),
            'inventory.expiry_medium_days' => (int) ($input['expiry_medium_days'] ?? 60),
            'inventory.expiry_high_days' => (int) ($input['expiry_high_days'] ?? 30),
            'inventory.expiry_critical_days' => (int) ($input['expiry_critical_days'] ?? 7),
            'inventory.fefo_enabled' => isset($input['fefo_enabled']) ? '1' : '0',
        ], 'inventory');

        AuditLogger::log('update', 'settings_inventory', null, null, $input);
        Session::flash('success', 'Pengaturan persediaan berhasil disimpan.');
        $this->redirect('/settings/inventory');
    }

    public function printer(Request $request): void
    {
        $printer = PrinterSetting::all()[0] ?? null;
        $this->view('settings.printer', ['title' => 'Pengaturan Printer', 'printer' => $printer]);
    }

    public function updatePrinter(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        $data = [
            'name' => $input['name'] ?? 'Printer Kasir Utama',
            'paper_size' => in_array($input['paper_size'] ?? '', ['58mm', '80mm'], true) ? $input['paper_size'] : '80mm',
            'is_default' => 1,
            'auto_print' => isset($input['auto_print']) ? 1 : 0,
            'header_text' => $input['header_text'] ?? null,
            'footer_text' => $input['footer_text'] ?? null,
        ];

        $existing = PrinterSetting::all();
        if (empty($existing)) {
            PrinterSetting::create($data);
        } else {
            PrinterSetting::update((int) $existing[0]['id'], $data);
        }

        AuditLogger::log('update', 'settings_printer', null, null, $data);
        Session::flash('success', 'Pengaturan printer berhasil disimpan.');
        $this->redirect('/settings/printer');
    }
}
