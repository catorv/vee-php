<?php
// 自动对应模版变量 (_name_)
// 注意：这里 APP_ID 的值必须与 index.php 中的 APP_ID 保持一致
define('APP_ID', {_id_});

// 自动对应模版变量 (_id_)
// 注意：这里 APP_NAME 的值必须与 index.php 中的 APP_NAME 保持一致
define('APP_NAME', '{_name_}');

// 用户名，自动对应模版变量 (_username_)，如果为空，自动获取当前系统用户名
define('USERNAME', '');
// 组织或公司名称，自动对应模版变量 (_organization_name_)
define('ORGANIZATION_NAME', '__YOUR_COMPANY__');

/*
 * 创建文件时使用的文档注释
 * 模版变量定义如下：
 *   (_id_)                 项目ID，能用来干嘛自己想喽
 *   (_name_)               项目名称，通常在项目创建的时候指定的
 *   (_username_)           用户名
 *   (_organization_name_)  组织或公司名称
 *   (_year_)               当前年份：date("Y")
 *   (_date_)               当前时间：date("Y-m-d H:i:s")
 *   (_package_name_)       文件包名，只在创建 module 和 controller 的时候有效
 *   (_class_name_)         模块名称，只在创建 module 和 controller 的时候有效
 *   (_doc_comment_)        文档注释，也就是 VEE_TPL_DOCCOMMENT 中定义的格式
 */
define('VEE_TPL_DOCCOMMENT', <<<EOS
/**
 * 在这里写上你自己的注释
 *
 * @package (_name_)\(_package_name_)
 * @copyright Copyright (c) (_year_) (_organization_name_). All rights reserved.
 * @author (_username_)
 */
EOS
);
