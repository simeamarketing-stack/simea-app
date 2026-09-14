<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    public function required(array $data, string $field, string $label): self
    {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            $this->errors[$field] = "$label là bắt buộc.";
        }
        return $this;
    }

    public function numeric(array $data, string $field, string $label): self
    {
        if (isset($data[$field]) && $data[$field] !== '' && !is_numeric($data[$field])) {
            $this->errors[$field] = "$label phải là số.";
        }
        return $this;
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
