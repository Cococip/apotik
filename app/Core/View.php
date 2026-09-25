<?php

namespace App\Core;

class View
{
    private static string $viewPath;

    public static function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        self::$viewPath = dirname(__DIR__) . '/Views/';

        extract($data, EXTR_SKIP);

        $csrfField = Csrf::field();
        $currentUser = Auth::user();

        $viewFile = self::$viewPath . str_replace('.', '/', $view) . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException("View tidak ditemukan: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = self::$viewPath . 'layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }

        require $layoutFile;
    }

    public static function partial(string $view, array $data = []): void
    {
        $viewPath = dirname(__DIR__) . '/Views/';
        extract($data, EXTR_SKIP);
        $viewFile = $viewPath . str_replace('.', '/', $view) . '.php';
        if (is_file($viewFile)) {
            require $viewFile;
        }
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
