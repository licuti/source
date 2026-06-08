<?php
namespace App\Core;

class Paginator implements \IteratorAggregate, \Countable, \ArrayAccess {
    protected $items;
    protected $total;
    protected $perPage;
    protected $currentPage;
    protected $lastPage;

    public function __construct($items, $total, $perPage, $currentPage) {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
        $this->lastPage = max(1, (int)ceil($total / $perPage));
    }

    public function items() {
        return $this->items;
    }

    public function total() {
        return $this->total;
    }

    public function perPage() {
        return $this->perPage;
    }

    public function currentPage() {
        return $this->currentPage;
    }

    public function lastPage() {
        return $this->lastPage;
    }

    /**
     * Tương thích Laravel: render HTML phân trang
     */
    public function links($url = null) {
        if ($url === null) {
            $url = getCurrentUrlWithoutPage();
        }
        return paging($this->total, $this->perPage, $this->currentPage, $url);
    }

    /**
     * Tương thích ngược với mã cũ (truy cập thuộc tính public)
     */
    public function __get($name) {
        switch ($name) {
            case 'data': return $this->items;
            case 'total': return $this->total;
            case 'per_page': return $this->perPage;
            case 'current_page': return $this->currentPage;
            case 'last_page': return $this->lastPage;
        }
        return null;
    }

    // --- IteratorAggregate ---
    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->items);
    }

    // --- Countable ---
    public function count(): int {
        return count($this->items);
    }

    // --- ArrayAccess (cho phép dùng như mảng) ---
    public function offsetExists($offset): bool {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void {
        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset): void {
        unset($this->items[$offset]);
    }
}
