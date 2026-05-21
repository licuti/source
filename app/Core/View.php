<?php

namespace App\Core;

class View
{
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

    public function render($template, $data = [])
    {
        // Inject global 'com' into $data if not already present, to prevent undefined variable errors
        if (!isset($data['com'])) {
            $data['com'] = $GLOBALS['com'] ?? '';
        }

        $data = array_merge($this->data, $data);

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
