<?php
namespace Tabel\Core;

abstract class Command {
    protected array $arguments = [];

    public function __construct() {
        $this->parseArguments();
    }

    protected function parseArguments(): void {
        global $argv;
        $this->arguments = array_slice($argv, 1);
    }

    abstract public function handle(): int;

    protected function info(string $message): void {
        echo "\033[32m" . $message . "\033[0m" . PHP_EOL; // Green text
        logger('info', $message);
    }

    protected function error(string $message): void {
        echo "\033[31mError: " . $message . "\033[0m" . PHP_EOL; // Red text
        logger('error', $message);
    }

    protected function warn(string $message): void {
        echo "\033[33m" . $message . "\033[0m" . PHP_EOL; // Yellow text
        logger('warning', $message);
    }
}
