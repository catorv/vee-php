<!DOCTYPE script PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Memcache - VEE-PHP</title>
</head>
<body>
    <script>
    function memcachedDump(name) {
        if (name != 'none') {
            location = '<?php echo APP_URL_BASE?>tools_memcache/' + name;
        }
    }

    function deleteItems() {
        document.getElementById('action').value = 'delete';
        document.getElementById('form1').submit();
        return false;
    }
    </script>
    <select onchange="memcachedDump(this.value)">
        <option value="none">遍历Memcached</option>
    <?php foreach (Config::$cache as $k => $cache) :?>
        <?php if ($cache['engine'] == 'memcache') :?>
        <option value="<?php echo $k; ?>" <?php if ($k == $name):?>selected="selected"<?php endif;?>><?php echo json_encode($cache); ?></option>
        <?php endif ?>
    <?php endforeach ?>
    </select>
    <a href="<?php echo APP_URL_BASE?>tools_memcache/flush/<?php echo $name; ?>">清空缓存</a>
    <a href="<?php echo APP_URL_BASE?>tools_memcache/<?php echo $name; ?>">刷新</a>
    <hr />
    <?php if (is_array($data)): ?>
    <form name="form1" id="form1" method="post">
        <table>
        <?php $i = 0; ?>
        <?php foreach ($data as $key => $item):?>
            <?php list($bytes, $time) = $item; ++$i; ?>
            <tr onmouseover="this.style.background='#dfd'" onmouseout="this.style.background='';">
                <td align="right"><?php echo $i; ?>.</td>
                <td style="padding-right:24px;"><a href="<?php echo APP_URL_BASE?>tools_memcache/view?name=<?php echo urlencode($name); ?>&key=<?php echo urlencode($key); ?>"><?php echo $key; ?></a></td>
                <td align="right"><?php echo $bytes; ?> Bytes</td>
                <td>
                    <input id="action" name="action" type="hidden"/>
                    <input name="items[]" type="checkbox" value="<?php echo htmlspecialchars($key); ?>"/>
                </td>
                <td><?php if ($i % 10 == 0):?><a href="#" onclick="return deleteItems();">删除选中</a><?php endif;?></td>
            </tr>
        <?php endforeach; ?>
        </table>
    </form>
    <?php else: ?>
        <?php echo $data; ?>
    <?php endif;?>
</body>
</html>