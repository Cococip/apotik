<?php

namespace App\Controllers;

use App\Core\CrudController;

class UnitController extends CrudController
{
    protected string $modelClass = \App\Models\Unit::class;
    protected string $viewIndex = 'units.index';
    protected string $routeBase = '/units';
    protected string $auditModule = 'unit';
    protected array $searchColumns = ['name', 'symbol'];
    protected string $orderBy = 'name';
    protected string $orderDirection = 'ASC';
    protected string $entityLabel = 'Satuan';

    protected function rules(bool $isUpdate, ?int $id = null): array
    {
        return [
            'name' => 'required|max:60',
            'symbol' => 'required|max:20',
        ];
    }

    protected function beforeSave(array $data, bool $isUpdate, ?int $id = null): array
    {
        return ['name' => $data['name'], 'symbol' => $data['symbol']];
    }
}
