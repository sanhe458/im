<?php

namespace App\Utils;

class Validator
{
    private array $errors = [];
    private array $rules = [];

    public static function make(array $data, array $rules)
    {
        $instance = new self();
        $instance->rules = $rules;

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $instance->errors[$field][] = "{$field} is required";
                } elseif ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $instance->errors[$field][] = "{$field} must be a valid email";
                } elseif ($rule === 'min:6' && $value && strlen($value) < 6) {
                    $instance->errors[$field][] = "{$field} must be at least 6 characters";
                } elseif ($rule === 'unique' && $value) {
                }
            }
        }

        return $instance;
    }

    public function fails()
    {
        return !empty($this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function firstError()
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0];
        }
        return null;
    }
}
