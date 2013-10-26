<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>MySQL ORM Mapping 文件生成器</title>
	<style>
	   #table-list td {
	        border-bottom: 1px dotted #666;
	   }
	</style>
</head>
<body>
<?php 
if (isset($msg) && !empty($msg) && is_array($msg)) {
    foreach ($msg as $_msg) {
        echo '<span style="font-size:12px;color:#999">', $_msg, '</span><br>';
    }
    echo '<hr/>';
} 
?>
	<form name="dbform" method="post">
		<p><select name="db" onchange="document.dbform.submit()">
			<option value="-1">请选择数据库</option>
<?php
foreach (Config::$db as $index => $db) {
	echo '<option value="' . $index . '"';
	if (isset($_POST['db']) && $index == $_POST['db']) {
		echo ' selected="1"';
	}
	echo '>';
	if (substr($db['params']['driver'], 0, 3) == 'Pdo') {
		echo '[', $db['params']['driver'], '] ', $db['params']['dsn'],
		     ';user=', $db['params']['user'];
	} else {
		echo '[', $db['params']['driver'],
		     '] host=', $db['params']['host'],
		     ';dbname=', $db['params']['name'],
		     ';user=', $db['params']['user'];
	}
	echo "</option>\n";
}
?>
		</select>
        <input name="make" type="submit" value="导出"/>
        <input name="refresh" type="submit" value="刷新"/>
        <input type="button" value="全选" onclick="selectButtonClick(this)"/>
        <input type="button" value="全部禁用对象缓存" onclick="cacheButtonClick(this)"/>
        <input name="flushCache" type="submit" value="清空对象缓存"/>
	</p>
<?php 
if (isset($tables) && !empty($tables)) {
?>
		<table id="table-list" border="0" width="100%">
<?php
	foreach ($tables as $table) {
?>
		<tr>
		  <td width="180">
		      <input name="tables[]" type="checkbox" value="<?php echo $table ?>" 
<?php
		if (isset($_POST['tables']) && in_array($table, $_POST['tables'])) {
			echo ' checked="checked"';
		}
?>
		      /><?php echo $table ?>
		  </td>
		  <td width="130">
		      <input id="<?php echo $table ?>DisableCache" name="<?php echo $table ?>DisableCache" type="checkbox" value="1"
<?php
		if (isset($_POST[$table . 'DisableCache']) && $_POST[$table . 'DisableCache'] == '1') {
			echo ' checked="checked"';
		}
?>
		      />禁用对象缓存
		  </td>
		  <td>&nbsp;</td>
		</tr>
<?php
	}
?>
		</table>
<?php
}
?>
</form>
<script>
function selectButtonClick(btn) {
	if (btn.value == '全选') {
		allSelect();
		btn.value = '取消全选';
	} else {
		allDisselect();
		btn.value = '全选';
	}
}

function allSelect() {
	var $objs = document.getElementsByName('tables[]');
	for (var $i=0; $i<$objs.length; $i++) {
		var $obj = $objs[$i];
		$obj.checked = true; 
	}
}

function allDisselect() {
	var $objs = document.getElementsByName('tables[]');
	for (var $i=0; $i<$objs.length; $i++) {
		var $obj = $objs[$i];
		$obj.checked = false; 
	}
}

function cacheButtonClick(btn) {
    if (btn.value == '全部禁用对象缓存') {
    	allCacheDisabled();
        btn.value = '全部启用对象缓存';
    } else {
    	allCacheEnabled();
        btn.value = '全部禁用对象缓存';
    }
}

function allCacheEnabled() {
	var $objs = document.getElementsByName('tables[]');
	for (var $i=0; $i<$objs.length; $i++) {
		var $obj = $objs[$i];
		document.getElementById($obj.value + 'DisableCache').checked = false; 
	}
}

function allCacheDisabled() {
	var $objs = document.getElementsByName('tables[]');
	for (var $i=0; $i<$objs.length; $i++) {
		var $obj = $objs[$i];
		document.getElementById($obj.value + 'DisableCache').checked = true; 
	}
}
</script>
</body>
</html>