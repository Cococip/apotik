<?php

namespace App\Core;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function validate(array $rules): bool
    {
        foreach ($rules as $field => $ruleString) {
            $rulesList = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rulesList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $this->applyRule($field, $value, $rule, $params);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule, array $params): void
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
                    $this->addError($field, "Field {$field} wajib diisi.");
                }
                break;
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Format email tidak valid.");
                }
                break;
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->addError($field, "Field {$field} harus berupa angka.");
                }
                break;
            case 'integer':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "Field {$field} harus berupa bilangan bulat.");
                }
                break;
            case 'min':
                if ($value !== null && $value !== '' && mb_strlen((string) $value) < (int) $params[0]) {
                    $this->addError($field, "Field {$field} minimal {$params[0]} karakter.");
                }
                break;
            case 'max':
                if ($value !== null && $value !== '' && mb_strlen((string) $value) > (int) $params[0]) {
                    $this->addError($field, "Field {$field} maksimal {$params[0]} karakter.");
                }
                break;
            case 'min_value':
                if ($value !== null && $value !== '' && (float) $value < (float) $params[0]) {
                    $this->addError($field, "Field {$field} minimal {$params[0]}.");
                }
                break;
            case 'in':
                if ($value !== null && $value !== '' && !in_array($value, $params, true)) {
                    $this->addError($field, "Field {$field} tidak valid.");
                }
                break;
            case 'date':
                if ($value && !strtotime($value)) {
                    $this->addError($field, "Field {$field} harus berupa tanggal yang valid.");
                }
                break;
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if (($this->data[$confirmField] ?? null) !== $value) {
                    $this->addError($field, "Konfirmasi {$field} tidak cocok.");
                }
                break;
            case 'unique':
                // params: table,column[,ignoreId]
                $table = $params[0];
                $column = $params[1];
                $ignoreId = $params[2] ?? null;
                if ($value !== null && $value !== '') {
                    $sql = "SELECT COUNT(*) AS total FROM {$table} WHERE {$column} = :value";
                    if ($ignoreId) {
                        $sql .= " AND id != :ignore_id";
                    }
                    $stmt = Database::connection()->prepare($sql);
                    $params2 = ['value' => $value];
                    if ($ignoreId) {
                        $params2['ignore_id'] = $ignoreId;
                    }
                    $stmt->execute($params2);
                    if ((int) $stmt->fetch()['total'] > 0) {
                        $this->addError($field, "Field {$field} sudah digunakan.");
                    }
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0];
        }
        return null;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }
}
