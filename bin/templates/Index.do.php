<?php
/**
 * 在这里写上你自己的注释
 *
 * @package {_name_}\controllers\index
 * @copyright Copyright (c) {_year_} {_organization_name_}. All rights reserved.
 * @author {_username_}
 */
class _Index extends Controller {
    function doDefault() {
        // 开启 DEBUG 模式
        setcookie('DEBUG', 1, time() + 86400 * 1000);
        V::response(array(
            'hello' => 'Hello World!',
        ));
    }
}
