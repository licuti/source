<?php

namespace App\Traits;

trait Timestampable {
    public function getCreatedAtColumn(): string {
        return property_exists($this, 'createdAt') ? $this->createdAt : 'created_at';
    }

    public function getUpdatedAtColumn(): string {
        return property_exists($this, 'updatedAt') ? $this->updatedAt : 'updated_at';
    }

    public function touch() {
        $this->{$this->getUpdatedAtColumn()} = date('Y-m-d H:i:s');
        return $this->save();
    }
}
