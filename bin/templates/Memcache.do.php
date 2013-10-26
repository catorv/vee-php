<?php
/**
 * 遍历和管理memcache数据
 * @package tools\memcache
 * @copyright Copyright (c) 2005-2079 Cator Vee
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class tools_Memcache extends Controller {
    public function doDefault($name = 0) {
        if (isset($_POST['action']) && isset($_POST['items'])) {
            $this->deleteItems($name, $_POST['items']);
        }

        V::response()->value(array(
                'name' => $name,
                'data' => array(),
                ));
        if (isset(Config::$cache[$name])) {
            $memcache = new Memcache();
            $result = @$memcache->connect(Config::$cache[$name]['host'], Config::$cache[$name]['port'], Config::$cache[$name]['timeout']);
            if ($result) {
                $result = $memcache->getStats('items');
                $items = array();
                if (is_array($result)) {
                    foreach ($result['items'] as $slibId => $data) {
                        if ($data['number'] > 0) {
                            $items = array_merge($items, $memcache->getStats('cachedump', $slibId, 0));
                        }
                    }
                }
                $memcache->close();

                V::response()->value('total', count($items));

                ksort($items);
            } else {
                $items = '连接服务器失败';
            }
            V::response()->value('data', $items);
        }
    }

    public function doView() {
        $key = V::get('key');
        $name = V::get('name');
        if (null !== $key) {
            $memcache = new Memcache();
            $result = @$memcache->connect(Config::$cache[$name]['host'], Config::$cache[$name]['port'], Config::$cache[$name]['timeout']);
            if ($result) {
                echo str_replace('<span style="color: #0000BB">&lt;?php<br /></span>',
                                 '',
                                 highlight_string("<?php\n" . var_export($memcache->get($key),
                                                                         true),
                                                  true));
                $memcache->close();
            }
        }
        exit;
    }

    public function doFlush($name = 0){
        if (Config::$cache[$name]['engine'] == 'memcache') {
            $this->memcachedClean($name);
        } else {
            v::cache($name)->flush();
        }
        V::redirect('/tools_memcache/' . $name);
    }

    private function memcachedClean($name) {
        if (isset(Config::$cache[$name])) {
            $memcache = new Memcache();
            $result = @$memcache->connect(Config::$cache[$name]['host'], Config::$cache[$name]['port'], Config::$cache[$name]['timeout']);
            if ($result) {
                $result = $memcache->getStats('items');
                if (is_array($result)) {
                    foreach ($result['items'] as $slibId => $data) {
                        if ($data['number'] > 0) {
                            $items = $memcache->getStats('cachedump', $slibId, 0);
                            foreach ($items as $mkey => $item) {
                                $memcache->delete($mkey, 0);
                            }
                        }
                    }
                }
                $memcache->close();
            }
        }
    }

    private function deleteItems($name, $items) {
        if (isset(Config::$cache[$name])) {
            $memcache = new Memcache();
            $result = @$memcache->connect(Config::$cache[$name]['host'], Config::$cache[$name]['port'], Config::$cache[$name]['timeout']);
            if ($result) {
                foreach ($items as $item) {
                    $memcache->delete($item);
                }
                $memcache->close();
            }
        }
    }
}