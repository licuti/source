<?php

namespace App\Core;

class Validator {

    protected array $data = [];
    protected array $rules = [];
    protected array $customMessages = [];
    protected array $errors = [];

    /**
     * Khởi tạo Validator
     *
     * @param array $data Dữ liệu đầu vào (e.g. $_POST)
     * @param array $rules Các quy tắc (e.g. ['title.vi' => 'required|max:255'])
     * @param array $customMessages Câu báo lỗi tùy chỉnh
     */
    public function __construct(array $data, array $rules, array $customMessages = []) {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
        $this->validate();
    }

    /**
     * Tiến hành kiểm tra dữ liệu
     */
    protected function validate() {
        foreach ($this->rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            $value = $this->getValue($this->data, $field);

            foreach ($rulesArray as $rule) {
                // Parse rule có param (VD: max:255)
                $ruleParts = explode(':', $rule, 2);
                $ruleName = trim($ruleParts[0]);
                $ruleParam = isset($ruleParts[1]) ? trim($ruleParts[1]) : null;

                $this->applyRule($field, $value, $ruleName, $ruleParam);
            }
        }
    }

    /**
     * Áp dụng một rule cụ thể
     */
    protected function applyRule($field, $value, $ruleName, $ruleParam) {
        switch ($ruleName) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, $ruleName, 'Trường này không được bỏ trống.');
                }
                break;

            case 'numeric':
                // Chỉ check numeric nếu có giá trị (bỏ trống thì required lo)
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->addError($field, $ruleName, 'Trường này phải là một số.');
                }
                break;

            case 'max':
                if ($value !== null && $value !== '') {
                    $max = (float)$ruleParam;
                    if (is_numeric($value)) {
                        if ($value > $max) {
                            $this->addError($field, $ruleName, "Giá trị không được lớn hơn {$max}.");
                        }
                    } elseif (is_string($value)) {
                        if (mb_strlen($value, 'UTF-8') > $max) {
                            $this->addError($field, $ruleName, "Độ dài không được vượt quá {$max} ký tự.");
                        }
                    }
                }
                break;

            case 'min':
                if ($value !== null && $value !== '') {
                    $min = (float)$ruleParam;
                    if (is_numeric($value)) {
                        if ($value < $min) {
                            $this->addError($field, $ruleName, "Giá trị không được nhỏ hơn {$min}.");
                        }
                    } elseif (is_string($value)) {
                        if (mb_strlen($value, 'UTF-8') < $min) {
                            $this->addError($field, $ruleName, "Độ dài không được ngắn hơn {$min} ký tự.");
                        }
                    }
                }
                break;
        }
    }

    /**
     * Thêm lỗi vào danh sách
     */
    protected function addError($field, $ruleName, $defaultMessage) {
        // Tránh ghi đè lỗi của cùng 1 field (giữ lỗi đầu tiên)
        if (!isset($this->errors[$field])) {
            $customKey = "{$field}.{$ruleName}";
            $message = $this->customMessages[$customKey] ?? $this->customMessages[$field] ?? $defaultMessage;
            $this->errors[$field] = $message;
        }
    }

    /**
     * Lấy giá trị từ mảng đa chiều bằng dot notation (VD: "title.vi")
     */
    protected function getValue(array $array, string $key) {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return null;
            }
        }
        return $array;
    }

    /**
     * Trả về true nếu có lỗi và tự động lưu Flash Data vào Session
     */
    public function fails(): bool {
        if (!empty($this->errors)) {
            $this->flashData();
            return true;
        }
        return false;
    }

    /**
     * Lưu lỗi và dữ liệu cũ vào Session để dùng cho old() và errors()
     */
    protected function flashData() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['_flash_errors'] = $this->errors;
        $_SESSION['_flash_old_input'] = $this->data;
    }

    /**
     * Trả về true nếu pass tất cả
     */
    public function passes(): bool {
        return empty($this->errors);
    }

    /**
     * Lấy mảng toàn bộ lỗi
     */
    public function errors(): array {
        return $this->errors;
    }

    /**
     * Lấy lỗi đầu tiên
     */
    public function firstError(): ?string {
        if (empty($this->errors)) return null;
        return reset($this->errors);
    }
}
