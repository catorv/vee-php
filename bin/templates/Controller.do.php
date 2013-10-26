<?php
{_doc_comment_}
class {_class_name_} extends Controller {
    // 默认 Action
    public function doDefault() {
        // hello
    }
    
    /**
     * Action被执行前触发的事件
     * @param string $name Action名称
     * @param array $args 参数列表
     * @return boolean 如果返回false，则中止程序执行。
     */
    protected function onBeforeAction($name, $args = null) {
        return true;
    }
    
    /**
     * Action被执行后触发的事件
     * @param string $name Action名称
     * @param array $args 参数列表
     * @return boolean 如果返回false，则中止程序执行。
     */
    protected function onAfterAction($name, $args = null) {
        return true;
    }
}