<?php

namespace App\Controllers;

use App\Core\CrudController;

class CategoryController extends CrudController
{
    protected string $modelClass = \App\Models\Category::class;
    protected string $viewIndex = 'categories.index';
    protected string $routeBase = '/categories';
    protected string $auditModule = 'category';
    protected array $searchColumns = ['name'];
    protected string $orderBy = 'name';
    protected string $orderDirection = 'ASC';
    protected string $entityLabel = 'Kategori Obat';

    protected function rules(bool $isUpdate, ?int $id = null): array
    {
        return [
            'name' => 'required|max:100',
            'description' => 'max:255',
        ];
    }

    protected function beforeSave(array $data, bool $isUpdate, ?int $id = null): array
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $data['name']), '-'));
        return [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];
    }
}
