<?php

namespace Babel\Core\Mantle;

use Babel\Core\Mantle\App;

class Validator {


    /**
     * Validate
     * @param array $data
     * @param array $fields
     * @param array $messages
     * @return array
     */
    public function validate(array $data, array $fields, array $messages = []): array {
        // Split the array by a separator, trim each element
        // and return the array
        $split = fn ($str, $separator) => array_map('trim', explode($separator, $str));

        // get the message rules
        $rule_messages = array_filter($messages, fn ($message) =>  is_string($message));

        // overwrite the self:DEFault message
        $validation_errors = array_merge(DEFAULT_VALIDATION_ERRORS, $rule_messages);

        $errors = [];

        foreach ($fields as $field => $option) {

            $rules = $split($option, '|');

            foreach ($rules as $rule) {
                // get rule name params
                $params = [];
                // if the rule has parameters e.g., min: 1
                if (strpos($rule, ':')) {
                    [$rule_name, $param_str] = $split($rule, ':');
                    $params = $split($param_str, ',');
                } else {
                    $rule_name = trim($rule);
                }
                // by convention, the callback should be is_<rule> e.g.,is_required
                $fn = 'is_' . $rule_name;
           
                if (is_callable($fn)) {
                    $pass = $fn($data, $field, ...$params);
                    if (!$pass) {
                        // get the error message for a specific field and rule if exists
                        // otherwise get the error message from the $validation_errors
                        $errors[$field] = sprintf(
                            $messages[$field][$rule_name] ?? $validation_errors[$rule_name],
                            $field,
                            ...$params
                        );
                    }
                }
            }
        }

        return $errors;
    }
}
