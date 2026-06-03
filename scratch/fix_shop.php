<?php
$content = file_get_contents('assets/script/shop.js');

$search = "    $.ajax({\n        method: $('#form-cart').attr('method'),\n        url: url_ajax,\n        data: $('#form-cart').serialize(),\n    }).done(function(response) {\n        var res = typeof response === 'object' ? response : JSON.parse(response);\n        if(type === 0){\n            swal({ title: 'Success', text: res.message || 'ThAm vAo gi? hAng thAnh cA\'ng', icon: 'success', button: false, timer: 2000 })\n            // Update count\n            if (res.count) {\n                $('.count-cart').text(res.count);\n            }\n        } else {\n            window.location.href = URLPATH + 'gio-hang/';\n        }\n    });";

$replace = "    $.ajax({\n        method: $('#form-cart').attr('method'),\n        url: url_ajax,\n        data: $('#form-cart').serialize(),\n    }).done(function(response) {\n        try {\n            var res = typeof response === 'object' ? response : JSON.parse(response);\n            if(type === 0){\n                swal({ title: 'Success', text: res.message || 'Thêm vào giỏ hàng thành công', icon: 'success', button: false, timer: 2000 })\n                // Update count\n                if (res.count) {\n                    $('.count-cart').text(res.count);\n                }\n            } else {\n                window.location.href = URLPATH + 'gio-hang/';\n            }\n        } catch(e) {\n            console.error(\"JSON parse error: \", e, response);\n            swal({ title: 'Lỗi', text: 'Dữ liệu phản hồi bị lỗi.', icon: 'error', button: 'Đóng' });\n        }\n    }).fail(function(jqXHR, textStatus, errorThrown) {\n        console.error(\"AJAX Error: \", textStatus, errorThrown, jqXHR.responseText);\n        swal({ title: 'Lỗi máy chủ', text: 'Đã xảy ra lỗi kết nối.', icon: 'error', button: 'Đóng' });\n    });";

$content = str_replace($search, $replace, $content);
file_put_contents('assets/script/shop.js', $content);
echo "Fixed shop.js\n";
