<?php
/**
 * File Manager adapter - CKFinder only.
 *
 * Yeu cau: admin/ckfinder/ da duoc giai nen va cau hinh.
 */

if (!defined("FM_DRIVER")) {
    define("FM_DRIVER", "ckfinder");
}

if (!function_exists("fm_ckfinder_installed")) {
    function fm_ckfinder_installed()
    {
        $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . "ckfinder";
        return is_file($base . DIRECTORY_SEPARATOR . "ckfinder.html")
            || is_file($base . DIRECTORY_SEPARATOR . "ckfinder.js")
            || is_file($base . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "connector" . DIRECTORY_SEPARATOR . "php" . DIRECTORY_SEPARATOR . "connector.php");
    }
}

if (!function_exists("fm_driver")) {
    /**
     * @return string "ckfinder"
     */
    function fm_driver()
    {
        return "ckfinder";
    }
}

if (!function_exists("fm_type_code")) {
    /**
     * Chuan hoa loai file -> CKFinder resource.
     *
     * @param string|int $type  image|file|video|1|2|3
     * @return array{resource:string,preview:string}
     */
    function fm_type_code($type = "image")
    {
        if (is_numeric($type)) {
            $n = (int) $type;
            if ($n === 2) {
                return array("resource" => "Files", "preview" => "file");
            }
            if ($n === 3) {
                return array("resource" => "Videos", "preview" => "video");
            }
            return array("resource" => "Images", "preview" => "image");
        }

        $t = strtolower(trim((string) $type));
        if ($t === "file" || $t === "files" || $t === "document") {
            return array("resource" => "Files", "preview" => "file");
        }
        if ($t === "video" || $t === "videos") {
            return array("resource" => "Videos", "preview" => "video");
        }
        return array("resource" => "Images", "preview" => "image");
    }
}

if (!function_exists("fm_dialog_url")) {
    /**
     * URL mo CKFinder (dung khi can mo truc tiep, chu yeu mo qua JS modal).
     *
     * @param array $opts type
     * @return string
     */
    function fm_dialog_url(array $opts = array())
    {
        $meta = fm_type_code(isset($opts["type"]) ? $opts["type"] : "image");
        return "ckfinder/ckfinder.html?type=" . urlencode($meta["resource"]);
    }
}

if (!function_exists("fm_button_attrs")) {
    /**
     * Attribute HTML cho nut chon file (adapter JS doc data-fm-*).
     *
     * @return string HTML attributes (da escape)
     */
    function fm_button_attrs(array $opts = array())
    {
        $field    = isset($opts["field_id"]) ? (string) $opts["field_id"] : "";
        $multiple = !empty($opts["multiple"]);
        $meta     = fm_type_code(isset($opts["type"]) ? $opts["type"] : "image");
        $href     = fm_dialog_url($opts);

        $attrs = array(
            "href"           => $href,
            "class"          => isset($opts["class"]) ? $opts["class"] : "btn btn-sm btn-outline-primary iframe-btn js-fm-open",
            "data-type"      => "iframe",
            "data-fm-open"   => "1",
            "data-fm-driver" => "ckfinder",
            "data-fm-field"  => $field,
            "data-fm-type"   => $meta["preview"],
            "data-fm-resource" => $meta["resource"],
            "data-fm-multiple" => $multiple ? "1" : "0",
        );

        if (function_exists("attrs")) {
            return trim(attrs($attrs));
        }

        $out = array();
        foreach ($attrs as $k => $v) {
            $out[] = htmlspecialchars($k, ENT_QUOTES, "UTF-8")
                . "=\"" . htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8") . "\"";
        }
        return implode(" ", $out);
    }
}

if (!function_exists("fm_ckeditor_options")) {
    /**
     * Mang option cho CKEDITOR.replace (filebrowser*).
     *
     * @return array
     */
    function fm_ckeditor_options()
    {
        return array(
            "filebrowserBrowseUrl"      => "/admin/ckfinder/ckfinder.html?type=Files",
            "filebrowserImageBrowseUrl" => "/admin/ckfinder/ckfinder.html?type=Images",
            "filebrowserUploadUrl"      => "/admin/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files",
            "filebrowserImageUploadUrl" => "/admin/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images",
        );
    }
}

if (!function_exists("fm_client_config_json")) {
    /** JSON config cho JS adapter */
    function fm_client_config_json()
    {
        $cfg = array(
            "driver"            => "ckfinder",
            "ckfinderInstalled" => fm_ckfinder_installed(),
            "ckfinderScript"    => "ckfinder/ckfinder.js",
            "ckfinderConnector" => "/admin/ckfinder/core/connector/php/connector.php",
            "mediaBase"         => array(
                "image" => "../media/images/",
                "file"  => "../media/files/",
                "video" => "../media/videos/",
            ),
        );
        return json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
