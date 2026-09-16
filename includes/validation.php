<?php
/**
 * CreatorAI - Input Validation Helpers
 */

class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    /**
     * Validate required field
     */
    public function required($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }
    
    /**
     * Validate email
     */
    public function email($field, $label = 'Email') {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "Please enter a valid email address.";
            }
        }
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength($field, $min, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = "$label must be at least $min characters.";
        }
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength($field, $max, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = "$label must not exceed $max characters.";
        }
        return $this;
    }
    
    /**
     * Validate password strength
     */
    public function password($field, $label = 'Password') {
        if (isset($this->data[$field])) {
            $pwd = $this->data[$field];
            if (strlen($pwd) < 8) {
                $this->errors[$field] = "$label must be at least 8 characters.";
            } elseif (!preg_match('/[A-Z]/', $pwd)) {
                $this->errors[$field] = "$label must contain at least one uppercase letter.";
            } elseif (!preg_match('/[a-z]/', $pwd)) {
                $this->errors[$field] = "$label must contain at least one lowercase letter.";
            } elseif (!preg_match('/[0-9]/', $pwd)) {
                $this->errors[$field] = "$label must contain at least one number.";
            } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $pwd)) {
                $this->errors[$field] = "$label must contain at least one special character.";
            }
        }
        return $this;
    }
    
    /**
     * Validate field matches another field
     */
    public function matches($field, $matchField, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && isset($this->data[$matchField])) {
            if ($this->data[$field] !== $this->data[$matchField]) {
                $this->errors[$field] = "$label does not match.";
            }
        }
        return $this;
    }
    
    /**
     * Validate username format
     */
    public function username($field, $label = 'Username') {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $this->data[$field])) {
                $this->errors[$field] = "$label must be 3-30 characters and contain only letters, numbers, and underscores.";
            }
        }
        return $this;
    }
    
    /**
     * Validate numeric
     */
    public function numeric($field, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && !empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = "$label must be a number.";
        }
        return $this;
    }
    
    /**
     * Validate value is in allowed list
     */
    public function in($field, $allowed, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && !empty($this->data[$field]) && !in_array($this->data[$field], $allowed)) {
            $this->errors[$field] = "$label contains an invalid value.";
        }
        return $this;
    }
    
    /**
     * Custom validation
     */
    public function custom($field, $callback, $message) {
        if (isset($this->data[$field]) && !$callback($this->data[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Get all errors
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Get first error
     */
    public function firstError() {
        return reset($this->errors) ?: '';
    }
    
    /**
     * Get specific field value (sanitized)
     */
    public function getValue($field, $default = '') {
        return isset($this->data[$field]) ? trim($this->data[$field]) : $default;
    }
}

/**
 * Sanitize input string
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize for SQL (use with prepared statements, not as sole protection)
 */
function cleanInput($input) {
    if (is_array($input)) {
        return array_map('cleanInput', $input);
    }
    $input = trim($input);
    $input = stripslashes($input);
    return $input;
}
