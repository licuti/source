<?php

namespace App\Core\Config;

class Repository {
    protected $items = [];

    public function __construct(array $items = []) {
        $this->items = $items;
    }

    public function has($key) {
        return $this->get($key) !== null;
    }

    public function get($key, $default = null) {
        if ($key === null) {
            return $this->items;
        }

        $array = $this->items;
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    public function set($key, $value = null) {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $k => $v) {
            $array = &$this->items;
            $segments = explode('.', $k);
            $lastSegment = array_pop($segments);

            foreach ($segments as $segment) {
                if (!isset($array[$segment]) || !is_array($array[$segment])) {
                    $array[$segment] = [];
                }
                $array = &$array[$segment];
            }

            $array[$lastSegment] = $v;
        }
    }

    public function all() {
        return $this->items;
    }
}
