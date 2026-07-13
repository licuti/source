<?php

namespace App\Core;

use App\Core\Contracts\ViewInterface;

class View implements ViewInterface
{
    protected static $sharedData = [];
    protected $layout = null;
    protected $data = [];

    public function __construct()
    {
    }

    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    public function share(string $key, $value)
    {
        self::$sharedData[$key] = $value;
        return $this;
    }

    public function render(string $template, array $data = []): string
    {
        // Legacy: export global variables to data to ensure backward compatibility for old views
        if (!isset($data['com'])) {
            $data['com'] = $GLOBALS['com'] ?? '';
        }
        if (!isset($data['row']) && isset($GLOBALS['row'])) {
            $data['row'] = $GLOBALS['row'];
        }

        $data = array_merge(self::$sharedData, $this->data, $data);

        // Export to global scope for legacy including as well
        foreach ($data as $key => $value) {
            $GLOBALS[$key] = $value;
        }

        extract($data);

        $templatePath = dirname(dirname(__DIR__)) . '/resources/views/' . str_replace('.', '/', $template) . '.php';

        if (!file_exists($templatePath)) {
            throw new \Exception("View template not found: $template");
        }

        ob_start();
        include $templatePath;
        $content = ob_get_clean();

        if ($this->layout) {
            return $this->renderLayout($this->layout, array_merge($data, ['content' => $content]));
        }

        return $content;
    }

    protected function renderLayout($layout, $data)
    {
        // Ensure globals are available in layout too
        foreach ($data as $key => $value) {
            $GLOBALS[$key] = $value;
        }

        extract($data);
        $layoutPath = dirname(dirname(__DIR__)) . '/resources/views/' . str_replace('.', '/', $layout) . '.php';

        if (!file_exists($layoutPath)) {
            return $data['content'] ?? '';
        }

        ob_start();
        include $layoutPath;
        return ob_get_clean();
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
    }
}
