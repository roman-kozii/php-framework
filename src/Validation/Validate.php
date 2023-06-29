<?php

namespace Nebula\Validation;

use stdClass;

class Validate
{
    /**
     * Note: you can modify these messages to anything you like
     */
    public static $messages = [
        "string" => "%value must be a string",
        "email" => "you must supply a valid email address",
        "required" => "%field is a required field",
        "match" => "%field does not match",
        "min_length" => "%field is too short (min length: %rule_extra)",
        "max_length" => "%field is too long (max length: %rule_extra)",
        "uppercase" =>
        "%field requires at least %rule_extra uppercase character",
        "lowercase" =>
        "%field requires at least %rule_extra lowercase character",
        "symbol" => "%field requires at least %rule_extra symbol character",
        "reg_ex" => "%field is invalid",
    ];
    public static $errors = [];
    public static $custom = [];

    /**
     * Add arbitrary error to $errors array
     */
    public static function addError($item, $replacements): void
    {
        self::$errors[$replacements["%field"]][] = strtr(
            self::$messages[$item],
            $replacements
        );
    }

    /**
     * Validate the request data
     */
    public static function request(array $data, array $request_rules): ?stdClass
    {
        foreach ($request_rules as $request_item => $ruleset) {
            $value = $data[$request_item] ?? null;
            foreach ($ruleset as $rule_raw) {
                $rule_split = explode("=", $rule_raw);
                $rule = $rule_split[0];
                $extra = count($rule_split) == 2 ? $rule_split[1] : "";
                $result = match ($rule) {
                    "string" => self::isString($value),
                    "email" => self::isEmail($value),
                    "required" => self::isRequired($value),
                    "match" => self::isMatch($data, $request_item, $value),
                    "min_length" => self::isMinLength($value, $extra),
                    "max_length" => self::isMaxLength($value, $extra),
                    "uppercase" => self::isUppercase($value, $extra),
                    "lowercase" => self::isLowercase($value, $extra),
                    "symbol" => self::isSymbol($value, $extra),
                    "reg_ex" => self::regEx($value, $extra),
                };
                if (!$result) {
                    self::addError($rule, [
                        "%rule" => $rule,
                        "%rule_extra" => $extra,
                        "%field" => $request_item,
                        "%value" => $value,
                    ]);
                }
                foreach (self::$custom as $custom_rule => $callback) {
                    if ($custom_rule === $rule) {
                        $result = $callback($rule, $value);
                        if (!is_null($result)) {
                            self::$errors[$rule][] = $result;
                        }
                    }
                }
            }
        }
        return count(self::$errors) == 0 ? (object) $data : null;
    }

    /**
     * Request value must be a string
     */
    public static function isString($value): bool
    {
        return is_string($value);
    }

    /**
     * Request value must be an email
     */
    public static function isEmail($value): mixed
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Request value is required
     */
    public static function isRequired($value): bool
    {
        return !is_null($value) && $value != "";
    }

    /**
     * Two request values match
     * You must have a field prefixed with _check for this to work
     * ie) password and password_check (must match)
     */
    public static function isMatch($request_data, $item, $value): bool
    {
        $filtered_name = str_replace("_check", "", $item);
        if (!isset($request_data[$filtered_name])) {
            return false;
        }
        return $request_data[$filtered_name] === $value;
    }

    /**
     * Request value has min length restriction
     */
    public static function isMinLength($value, $min_length): bool
    {
        return strlen($value) >= $min_length;
    }

    /**
     * Request value has max length restriction
     */
    public static function isMaxLength($value, $max_length): bool
    {
        return strlen($value) <= $max_length;
    }

    /**
     * Request value is uppercase
     */
    public static function isUppercase($value, $count): bool
    {
        preg_match_all("/[A-Z]/", $value, $matches);
        return !empty($matches[0]) && count($matches) >= $count;
    }

    /**
     * Request value is lowercase
     */
    public static function isLowercase($value, $count): bool
    {
        preg_match_all("/[a-z]/", $value, $matches);
        return !empty($matches[0]) && count($matches) >= $count;
    }

    /**
     * Request value has a symbol
     */
    public static function isSymbol($value, $count): bool
    {
        preg_match_all("/[$&+,:;=?@#|'<>.^*()%!-]/", $value, $matches);
        return !empty($matches[0]) && count($matches) >= $count;
    }

    /**
     * Request value matches regex pattern
     */
    public static function regEx($value, $pattern): int|bool
    {
        return preg_match("/{$pattern}/", $value);
    }
}