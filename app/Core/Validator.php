<?php

namespace App\Core;

use App\Core\Contracts\ValidatorInterface;

class Validator implements ValidatorInterface {

    protected array $data = [];
    protected array $rules = [];
    protected array $customMessages = [];
    protected array $errors = [];
    protected array $customRules = [];

    /**
     * Khởi tạo Validator
     */
    public function __construct(array $data = [], array $rules = [], array $customMessages = []) {
        if (!empty($rules)) {
            $this->validate($data, $rules, $customMessages);
        }
    }

    /**
     * Tiến hành kiểm tra dữ liệu
     */
    public function validate(array $data, array $rules, array $messages = []): bool {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = array_merge($this->customMessages, $messages);
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            $value = $this->getValue($this->data, $field);

            foreach ($rulesArray as $rule) {
                if (empty(trim($rule))) continue;
                
                $ruleParts = explode(':', $rule, 2);
                $ruleName = trim($ruleParts[0]);
                $ruleParam = isset($ruleParts[1]) ? trim($ruleParts[1]) : null;

                $this->applyRule($field, $value, $ruleName, $ruleParam);
            }
        }
        return $this->passes();
    }

    public function extend(string $ruleName, callable $handler, string $message = '') {
        $this->customRules[$ruleName] = [
            'handler' => $handler,
            'message' => $message
        ];
    }

    /**
     * Áp dụng một rule cụ thể
     */
    protected function applyRule($field, $value, $ruleName, $ruleParam) {
        if (isset($this->customRules[$ruleName])) {
            $handler = $this->customRules[$ruleName]['handler'];
            $pass = call_user_func($handler, $field, $value, $ruleParam, $this->data);
            if (!$pass) {
                $this->addError($field, $ruleName, $this->customRules[$ruleName]['message'] ?: "Lỗi xác thực cho trường {$field}.");
            }
            return;
        }

        // Chuyển đổi tên rule thành tên method (VD: required -> validateRequired)
        $methodName = 'validate' . str_replace(' ', '', ucwords(str_replace('_', ' ', $ruleName)));

        if (method_exists($this, $methodName)) {
            if (!$this->$methodName($field, $value, $ruleParam)) {
                $this->addError($field, $ruleName, $this->getDefaultMessage($ruleName, $ruleParam));
            }
        } else {
            throw new \Exception("Quy tắc xác thực [{$ruleName}] không tồn tại.");
        }
    }

    // ─────────────────────────────────────────────
    // Các hàm Validate chuẩn
    // ─────────────────────────────────────────────

    protected function validateRequired($field, $value, $param): bool {
        return !($value === null || $value === '' || (is_array($value) && empty($value)));
    }

    protected function validateNumeric($field, $value, $param): bool {
        if ($value === null || $value === '') return true; // Required lo việc này
        return is_numeric($value);
    }

    protected function validateInteger($field, $value, $param): bool {
        if ($value === null || $value === '') return true;
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateBoolean($field, $value, $param): bool {
        if ($value === null || $value === '') return true;
        $acceptable = [true, false, 0, 1, '0', '1', 'true', 'false'];
        return in_array($value, $acceptable, true);
    }

    protected function validateEmail($field, $value, $param): bool {
        if ($value === null || $value === '') return true;
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMax($field, $value, $param): bool {
        if ($value === null || $value === '') return true;
        $max = (float)$param;
        if (is_numeric($value)) {
            return $value <= $max;
        } elseif (is_string($value)) {
            return mb_strlen($value, 'UTF-8') <= $max;
        } elseif (is_array($value)) {
            return count($value) <= $max;
        }
        return false;
    }

    protected function validateMin($field, $value, $param): bool {
        if ($value === null || $value === '') return true;
        $min = (float)$param;
        if (is_numeric($value)) {
            return $value >= $min;
        } elseif (is_string($value)) {
            return mb_strlen($value, 'UTF-8') >= $min;
        } elseif (is_array($value)) {
            return count($value) >= $min;
        }
        return false;
    }

    protected function validateIn($field, $value, $param): bool {
        if ($value === null || $value === '') return true;
        $allowed = explode(',', (string)$param);
        return in_array((string)$value, $allowed, true);
    }

    // ─────────────────────────────────────────────
    // Tin nhắn lỗi mặc định
    // ─────────────────────────────────────────────

    protected function getDefaultMessage($rule, $param = null): string {
        $messages = [
            'required' => 'Trường này không được bỏ trống.',
            'numeric'  => 'Trường này phải là một số.',
            'integer'  => 'Trường này phải là số nguyên.',
            'boolean'  => 'Trường này phải là kiểu đúng/sai.',
            'email'    => 'Địa chỉ email không hợp lệ.',
            'max'      => "Giá trị không được vượt quá {$param}.",
            'min'      => "Giá trị không được nhỏ hơn {$param}.",
            'in'       => 'Giá trị đã chọn không hợp lệ.'
        ];
        return $messages[$rule] ?? "Trường này không hợp lệ.";
    }

    // ─────────────────────────────────────────────
    // Tiện ích
    // ─────────────────────────────────────────────

    protected function addError($field, $ruleName, $defaultMessage) {
        if (!isset($this->errors[$field])) {
            $customKey = "{$field}.{$ruleName}";
            $message = $this->customMessages[$customKey] ?? $this->customMessages[$field] ?? $defaultMessage;
            $this->errors[$field] = $message;
        }
    }

    protected function getValue(array $array, string $key) {
        if (array_key_exists($key, $array)) return $array[$key];
        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return null;
            }
        }
        return $array;
    }

    public function fails(): bool {
        if (!empty($this->errors)) {
            $this->flashData();
            return true;
        }
        return false;
    }

    protected function flashData() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['_flash_errors'] = $this->errors;
        $_SESSION['_flash_old_input'] = $this->data;
    }

    public function passes(): bool {
        return empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    public function firstError(): ?string {
        if (empty($this->errors)) return null;
        return reset($this->errors);
    }
}
