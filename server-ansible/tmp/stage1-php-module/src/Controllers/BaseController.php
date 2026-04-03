<?php

namespace SnipeIT\Module\Controllers;

/**
 * Base controller providing view rendering and HTTP helpers.
 */
abstract class BaseController
{
    protected string $viewsPath;

    public function __construct()
    {
        $this->viewsPath = dirname(__DIR__) . '/Views';
    }

    protected function render(string $template, array $data = []): void
    {
        $file = $this->viewsPath . '/' . $template . '.php';
        if (!file_exists($file)) {
            http_response_code(500);
            echo "View not found: $template";
            return;
        }
        extract($data, EXTR_SKIP);
        require $this->viewsPath . '/layouts/main.php';
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function notFound(string $message = 'Not found'): void
    {
        http_response_code(404);
        $this->render('errors/404', compact('message'));
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
