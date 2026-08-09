<?php

namespace Tabel\Core;

/**
 * Base class for console commands.
 *
 * Handles argument parsing and provides colored console output helpers.
 */
abstract class Command {

    protected const COLOR_GREEN  = "\033[32m";
    protected const COLOR_RED    = "\033[31m";
    protected const COLOR_YELLOW = "\033[33m";
    protected const COLOR_CYAN   = "\033[36m";
    protected const COLOR_GRAY   = "\033[90m";
    protected const COLOR_RESET  = "\033[0m";

    /**
     * Positional arguments passed to the command (excludes the command name itself)
     * @var array<int, string>
     */
    protected array $arguments = [];

    /**
     * Flag/option arguments passed as --key=value or --key
     * @var array<string, string|bool>
     */
    protected array $options = [];

    public function __construct() {
        $this->parseArguments();
    }

    /**
     * Splits $argv into positional arguments and --option flags.
     *
     * e.g. `php tabel route:cache --force --env=production`
     * -> arguments: []
     * -> options:   ['force' => true, 'env' => 'production']
     */
    protected function parseArguments(): void {
        global $argv;

        // argv[0] = entry script, argv[1] = command name, rest = arguments/options
        $raw = array_slice($argv, 2);

        foreach ($raw as $item) {
            if (str_starts_with($item, '--')) {
                $item = substr($item, 2);
                if (str_contains($item, '=')) {
                    [$key, $value] = explode('=', $item, 2);
                    $this->options[$key] = $value;
                } else {
                    $this->options[$item] = true;
                }
                continue;
            }

            $this->arguments[] = $item;
        }
    }

    /**
     * Executes the command.
     *
     * @return int Exit code (0 = success, non-zero = failure)
     */
    abstract public function handle(): int;

    /**
     * Get a positional argument by index, or a default if not provided.
     */
    protected function argument(int $index, mixed $default = null): mixed {
        return $this->arguments[$index] ?? $default;
    }

    /**
     * Get an --option value by name, or a default if not provided.
     */
    protected function option(string $key, mixed $default = null): mixed {
        return $this->options[$key] ?? $default;
    }

    /**
     * Check whether a flag/option was passed at all.
     */
    protected function hasOption(string $key): bool {
        return array_key_exists($key, $this->options);
    }

    /**
     * Prompt the user for free-text input.
     */
    protected function ask(string $question, ?string $default = null): string {
        $suffix = $default !== null ? " [{$default}]" : '';
        echo self::COLOR_CYAN . $question . $suffix . ': ' . self::COLOR_RESET;

        $answer = trim(fgets(STDIN) ?: '');

        return $answer === '' && $default !== null ? $default : $answer;
    }

    /**
     * Prompt the user for a yes/no confirmation. Defaults to "no".
     */
    protected function confirm(string $question, bool $default = false): bool {
        $hint = $default ? 'Y/n' : 'y/N';
        echo self::COLOR_CYAN . $question . " ({$hint}): " . self::COLOR_RESET;

        $answer = strtolower(trim(fgets(STDIN) ?: ''));

        if ($answer === '') {
            return $default;
        }

        return in_array($answer, ['y', 'yes'], true);
    }

    /**
     * Print a plain, uncolored line.
     */
    protected function line(string $message = ''): void {
        echo $message . PHP_EOL;
    }

    /**
     * Print an informational message (green) and log it.
     */
    protected function info(string $message): void {
        $this->write(self::COLOR_GREEN, $message);
        logger('info', $message);
    }

    /**
     * Print a success message (green, with checkmark) and log it as info.
     */
    protected function success(string $message): void {
        $this->write(self::COLOR_GREEN, "✓ {$message}");
        logger('info', $message);
    }

    /**
     * Print an error message (red) and log it.
     */
    protected function error(string $message): void {
        $this->write(self::COLOR_RED, "Error: {$message}");
        logger('error', $message);
    }

    /**
     * Print a warning message (yellow) and log it.
     */
    protected function warn(string $message): void {
        $this->write(self::COLOR_YELLOW, "Warning: {$message}");
        logger('warning', $message);
    }

    /**
     * Print a muted/secondary message (gray). Not logged.
     */
    protected function comment(string $message): void {
        $this->write(self::COLOR_GRAY, $message);
    }

    /**
     * Write a colored line to the console.
     */
    private function write(string $color, string $message): void {
        echo $color . $message . self::COLOR_RESET . PHP_EOL;
    }
}