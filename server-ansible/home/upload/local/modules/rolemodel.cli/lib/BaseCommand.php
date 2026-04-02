<?php
namespace RoleModel\Cli;

abstract class BaseCommand
{
    /**
     * Execute the command.
     *
     * @param array $args CLI arguments (everything after the command name)
     * @return int Exit code (0 = success, non-zero = error)
     */
    abstract public function handle(array $args): int;

    protected function info(string $message): void
    {
        echo "[INFO]  {$message}\n";
    }

    protected function success(string $message): void
    {
        echo "[OK]    {$message}\n";
    }

    protected function error(string $message): void
    {
        fwrite(STDERR, "[ERROR] {$message}\n");
    }

    protected function warn(string $message): void
    {
        echo "[WARN]  {$message}\n";
    }

    /**
     * Perform an HTTP GET and return the HTTP status code.
     */
    protected function httpGet(string $url): int
    {
        $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
        @file_get_contents($url, false, $ctx);
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $m);
            return (int)($m[1] ?? 0);
        }
        return 0;
    }
}
