<?php
$content = file_get_contents('app/Core/Model.php');

// Remove duplicate jsonSerialize near line 160
$content = preg_replace('/public function jsonSerialize\(\): mixed \{\s*return \$this->toArray\(\);\s*\}/s', '', $content);

// Find the real toArray and jsonSerialize at the bottom
$pattern = '/#\[\\\\ReturnTypeWillChange\]\s*public function jsonSerialize\(\) \{\s*return \$this->toArray\(\);\s*\}/';
$content = preg_replace($pattern, '', $content);

$pattern_to_array = '/public function toArray\(\): array \{\s*\$data = \$this->attributes;\s*if \(!empty\(\$this->visible\)\) \{\s*\$data = array_intersect_key\(\$data, array_flip\(\$this->visible\)\);\s*\}\s*if \(!empty\(\$this->hidden\)\) \{\s*\$data = array_diff_key\(\$data, array_flip\(\$this->hidden\)\);\s*\}\s*return \$data;\s*\}/s';

$replacement = <<<'EOD'
#[\ReturnTypeWillChange]
    public function jsonSerialize(): mixed {
        return $this->toArray();
    }

    public function toArray(): array {
        $data = $this->attributes;
        
        $getPublic = function($obj) { return get_object_vars($obj); };
        $getPublic = $getPublic->bindTo(null, null);
        $publicVars = $getPublic($this);
        
        foreach ($publicVars as $key => $val) {
            if ($key !== 'use_lang' && $key !== 'wasRecentlyCreated' && $key !== 'table') {
                $data[$key] = $val;
            }
        }

        if (!empty($this->visible)) {
            $data = array_intersect_key($data, array_flip($this->visible));
        }
        if (!empty($this->hidden)) {
            $data = array_diff_key($data, array_flip($this->hidden));
        }
        return $data;
    }
EOD;

$content = preg_replace($pattern_to_array, $replacement, $content);
$content = str_replace("class Model implements \JsonSerializable {", "#[AllowDynamicProperties]\nclass Model implements \JsonSerializable {", $content);

file_put_contents('app/Core/Model.php', $content);
echo "Fixed Model.php\n";
