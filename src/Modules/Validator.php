<?php

namespace Tabel\Modules;

use Tabel\Core\Request;


class Validator {

    public static $instance = null;

    public const DEFAULT_VALIDATION_ERRORS = [
        'required' => 'Please enter the %s',
        'email' => 'The %s is not a valid email address',
        'min' => 'The %s must have at least %s characters',
        'max' => 'The %s must have at most %s characters',
        'between' => 'The %s must have between %d and %d characters',
        'same' => 'The %s must match with %s',
        'alphanumeric' => 'The %s should have only letters and numbers',
        'secure' => 'The %s must have between 8 and 64 characters and contain at least one number, one upper case letter, one lower case letter and one special character',
        'unique' => 'The %s already exists',
        'disposable' => 'The %s is a disposable email!',
        'string' => 'The %s must be a sring',
    ];

    private array $errors = [];

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Validate
     * @param array $data
     * @param array $fields
     * @param array $messages
     * @return bool
     */
    public function validate(Request $request, array $rules, array $messages = []) {
        $data = $request->all();

        $split = fn ($str, $separator) => array_map('trim', explode($separator, $str));

        $rule_messages = array_filter($messages, fn ($message) =>  is_string($message));

        $validation_errors = array_merge(static::DEFAULT_VALIDATION_ERRORS, $rule_messages);

        foreach ($rules as $field => $option) {
            $fieldData = $data[$field] ?? null; // Retrieve data for the field.

            $rules = $split($option, '|');



            foreach ($rules as $rule) {
                $params = [];
                if (strpos($rule, ':')) {
                    [$rule_name, $param_str] = $split($rule, ':');
                    $params = $split($param_str, ',');
                } else {
                    $rule_name = trim($rule);
                }
                $fn = 'is_' . $rule_name;
              
                if (method_exists($this, $fn)) {
                    $pass = $this->$fn($data, $field, ...$params);
                    if (!$pass) {
                        $this->errors[$field] = sprintf(
                            $messages[$field][$rule_name] ?? $validation_errors[$rule_name],
                            $field,
                            ...$params
                        );
                    }
                }
            }
        }

        return empty($this->errors);
    }
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Return true if a string is not empty
     * @param array $data
     * @param string $field
     * @return bool
     */
    public function is_required(array $data, string $field): bool {
        return isset($data[$field]) && trim($data[$field]) !== '';
    }
    public function is_string(array $data, string $field): bool {
        return isset($data[$field]) && is_string($data[$field]);
    }

    /**
     * Return true if the value is a valid email
     * @param array $data
     * @param string $field
     * @return bool
     */
    public function is_email(array $data, string $field): bool {
        if (empty($data[$field])) {
            return false;
        }

        return filter_var($data[$field], FILTER_VALIDATE_EMAIL);
    }

    /**
     * Return true if a string has at least min length
     * @param array $data
     * @param string $field
     * @param int $min
     * @return bool
     */
    public function is_min(array $data, string $field, int $min): bool {
        if (!isset($data[$field])) {
            return false;
        }

        return mb_strlen($data[$field]) >= $min;
    }

    /**
     * Return true if a string cannot exceed max length
     * @param array $data
     * @param string $field
     * @param int $max
     * @return bool
     */
    public function is_max(array $data, string $field, int $max): bool {
        if (!isset($data[$field])) {
            return false;
        }

        return mb_strlen($data[$field]) <= $max;
    }

    /**
     * @param array $data
     * @param string $field
     * @param int $min
     * @param int $max
     * @return bool
     */
    public function is_between(array $data, string $field, int $min, int $max): bool {
        if (!isset($data[$field])) {
            return false;
        }

        $len = mb_strlen($data[$field]);
        return $len >= $min && $len <= $max;
    }

    /**
     * Return true if a string equals the other
     * @param array $data
     * @param string $field
     * @param string $other
     * @return bool
     */
    public function is_same(array $data, string $field, string $other): bool {
        if (isset($data[$field], $data[$other])) {
            return $data[$field] === $data[$other];
        }

        if (!isset($data[$field]) && !isset($data[$other])) {
            return true;
        }

        return false;
    }

    /**
     * Return true if a string is alphanumeric
     * @param array $data
     * @param string $field
     * @return bool
     */
    function is_alphanumeric(array $data, string $field): bool {
        if (!isset($data[$field])) {
            return false;
        }
        return ctype_alnum($data[$field]);
    }

    /**
     * Return true if a password is secure
     * @param array $data
     * @param string $field
     * @return bool
     */
    public function is_secure(array $data, string $field): bool {
        if (!isset($data[$field])) {
            return false;
        }

        $pattern = "#.*^(?=.{8,64})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*\W).*$#";
        return preg_match($pattern, $data[$field]);
    }
    public function is_disposable(array $data, string $field) {

        if (!isset($data[$field])) {
            return false;
        }
        return $this->checkIfEmailIsDisposable($data[$field]);
    }


    // /**
    //  * Return true if the $value is unique in the column of a table
    //  * @param array $data
    //  * @param string $field
    //  * @param string $table
    //  * @param string $column
    //  * @return bool
    //  */
    // public function is_unique(array $data, string $field, string $table, string $column): bool {
    //     if (!isset($data[$field])) {
    //         return true;
    //     }

    //     $sql = "SELECT $column FROM $table WHERE $column = :value";

    //     $stmt = $this->db()->prepare($sql);
    //     $stmt->bindValue(":value", $data[$field]);

    //     $stmt->execute();

    //     return $stmt->fetchColumn() === false;
    // }




    public function checkIfEmailIsDisposable($email) {
        $domain = substr(strrchr($email, "@"), 1);

        $disposableDomains = [];

        // Read additional domains from a file
        $disposableFile = __DIR__ . '/../../../static/disposable-emails.txt';
        if (file_exists($disposableFile)) {
            $fileDomains = file($disposableFile, FILE_IGNORE_NEW_LINES);
            $disposableDomains = array_merge($disposableDomains, $fileDomains);
        }

        if (in_array($domain, $disposableDomains)) {
            return false;
        }

        $pattern = "/(ThrowAwayMail|DeadAddress|10MinuteMail|20MinuteMail|AirMail|Dispostable|Email Sensei|EmailThe|FilzMail|Guerrillamail|IncognitoEmail|Koszmail|Mailcatch|Mailinator|Mailnesia|MintEmail|MyTrashMail|NoClickEmail|SpamSpot|Spamavert|Spamfree24|TempEmail|Thrashmail.ws|Yopmail|EasyTrashMail|Jetable|MailExpire|MeltMail|Spambox|empomail|33Mail|E4ward|GishPuppy|InboxAlias|MailNull|Spamex|Spamgourmet|BloodyVikings|SpamControl|MailCatch|Tempomail|EmailSensei|Yopmail|Trasmail|Guerrillamail|Yopmail|boximail|ghacks|Maildrop|MintEmail|fixmail|gelitik.in|ag.us.to|mobi.web.id|fansworldwide.de|privymail.de|gishpuppy|spamevader|uroid|tempmail|soodo|deadaddress|trbvm)/i";
        if (preg_match($pattern, $domain)) {
            return false;
        }

        return true;
    }
}
