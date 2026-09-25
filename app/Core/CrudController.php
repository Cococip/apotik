<?php

namespace App\Core;

use App\Services\AuditLogger;

/**
 * Shared list+modal CRUD flow for simple master-data modules (§8/§35/§36).
 * Concrete controllers only declare the model, view, rules and audit
 * module name — pagination, search, soft delete and audit logging are
 * handled once here instead of being copy-pasted per module.
 */
abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $modelClass;
    protected string $viewIndex;
    protected string $routeBase;
    protected string $auditModule;
    protected array $searchColumns = [];
    protected string $orderBy = 'id';
    protected string $orderDirection = 'DESC';
    protected int $perPage = 10;
    protected string $entityLabel = 'Data';

    /** Validation rules. Override per module. */
    abstract protected function rules(bool $isUpdate, ?int $id = null): array;

    /** Hook to transform submitted data before create/update (e.g. slug). */
    protected function beforeSave(array $data, bool $isUpdate, ?int $id = null): array
    {
        return $data;
    }

    /** Extra view data merged into the index view. */
    protected function extraViewData(): array
    {
        return [];
    }

    public function index(Request $request): void
    {
        /** @var class-string<Model> $model */
        $model = $this->modelClass;
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));

        $result = $model::paginate($page, $this->perPage, [], $search ?: null, $this->searchColumns, $this->orderBy, $this->orderDirection);

        $this->view($this->viewIndex, array_merge([
            'title' => $this->entityLabel,
            'rows' => $result['data'],
            'pagination' => $result,
            'search' => $search,
            'routeBase' => $this->routeBase,
            'openModal' => flash('open_modal'),
            'editRecord' => null,
        ], $this->extraViewData()));
    }

    public function store(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $input = $request->all();
        $validator = $this->validate($input, $this->rules(false));

        if ($validator->fails()) {
            $this->withOldAndErrors($input, $validator);
            Session::flash('open_modal', 'create');
            $this->redirect($this->routeBase);
            return;
        }

        $data = $this->beforeSave($input, false);
        /** @var class-string<Model> $model */
        $model = $this->modelClass;

        try {
            $id = $model::create($data);
        } catch (\PDOException $e) {
            $this->flashDuplicateError($input);
            return;
        }

        AuditLogger::log('create', $this->auditModule, $id, null, $data);
        Session::flash('success', $this->entityLabel . ' berhasil ditambahkan.');
        $this->redirect($this->routeBase);
    }

    public function update(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        /** @var class-string<Model> $model */
        $model = $this->modelClass;
        $existing = $model::find($id);
        if (!$existing) {
            Session::flash('error', 'Data tidak ditemukan.');
            $this->redirect($this->routeBase);
            return;
        }

        $input = $request->all();
        $validator = $this->validate($input, $this->rules(true, $id));

        if ($validator->fails()) {
            $this->withOldAndErrors($input, $validator);
            Session::flash('open_modal', 'edit:' . $id);
            $this->redirect($this->routeBase);
            return;
        }

        $data = $this->beforeSave($input, true, $id);

        try {
            $model::update($id, $data);
        } catch (\PDOException $e) {
            $this->flashDuplicateError($input, $id);
            return;
        }

        AuditLogger::log('update', $this->auditModule, $id, $existing, $data);
        Session::flash('success', $this->entityLabel . ' berhasil diperbarui.');
        $this->redirect($this->routeBase);
    }

    private function flashDuplicateError(array $input, ?int $id = null): void
    {
        Session::flash('error', $this->entityLabel . ' dengan data tersebut sudah ada. Periksa kembali kode/nama yang unik.');
        flash_old($input);
        Session::flash('open_modal', $id ? 'edit:' . $id : 'create');
        $this->redirect($this->routeBase);
    }

    public function destroy(Request $request, string $id): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $id = (int) $id;
        /** @var class-string<Model> $model */
        $model = $this->modelClass;
        $existing = $model::find($id);
        if (!$existing) {
            Session::flash('error', 'Data tidak ditemukan.');
            $this->redirect($this->routeBase);
            return;
        }

        try {
            $model::delete($id);
            AuditLogger::log('delete', $this->auditModule, $id, $existing, null);
            Session::flash('success', $this->entityLabel . ' berhasil dihapus.');
        } catch (\PDOException $e) {
            Session::flash('error', $this->entityLabel . ' tidak dapat dihapus karena masih digunakan pada data lain.');
        }

        $this->redirect($this->routeBase);
    }
}
