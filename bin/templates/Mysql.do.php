<?php
/**
 * 导出MySQL数据库的表结构到ORM Mapping文件中
 * @package tools\orm\mysql
 * @copyright Copyright (c) 2005-2079 Cator Vee
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class tools_orm_Mysql extends Controller {
    
	function doDefault() {
	    $response = V::response('vee');
		$msg = array();
		if (!isset($_POST['db'])) {
		    $_POST['db'] = '0';
		}
		if ($_POST['db'] != '-1') {
			$query = V::db($_POST['db'])->query();
			$tables = $query->getColumn('show tables');
			$response->value('tables', $tables);
			if (isset($_POST['tables']) && isset($_POST['make'])) {
				foreach ($_POST['tables'] as $table) {
					$msg[] = $this->makeMapping($_POST['db'], $table);
				}
			}
		}
		if (isset($_POST['flushCache'])) {
		    V::cache()->flush(Config::ORM_CACHE_PREFIX);
		    $msg[] = 'ORM对象缓存(文件)保存在"' 
		              . str_replace(
		                      array('/', '\\'),
		                      array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR),
		                      PATH_CACHE . Config::ORM_CACHE_PREFIX)
		              . '", 现在已经被全部清空.';
		}
		$response->value('msg', $msg);
	}
	
	private function makeMapping($db, $table) {
		$query = V::db($_POST['db'])->query();
		$fields = $query->getList('show full columns from ' . $table);
		$_key = '';
		$_cache = !(isset($_POST[$table . 'DisableCache']) && $_POST[$table . 'DisableCache'] == '1');
		$_class = StringHelper::camelEncode($table, true) . 'Entity';
		$_table = $table;
		$_fields = array();
		$class = "<?php\nclass {$_class} extends DbEntity {\n";
		foreach ($fields as $field) {
			$property = $field['Field'];
			$_fields[$field['Field']] = array(
//					'property'		=> $property,
					'type'			=> $field['Type'],
//					'min'			=> null,
//					'max'			=> null,
//					'null'			=> ($field['Null'] != 'NO'),
					'default'		=> $field['Default'],
			        'value'         => null,
//					'autoIncrement'	=> ($field['Extra'] == 'auto_increment'),
					);
			if ($field['Key'] == 'PRI' && empty($_key)) {
				$_key = $field['Field'];
			}
			$field['Type'] = strtolower($field['Type']);
			$pos = strpos($field['Type'], '(');
			if ($pos === false) {
				$type = $field['Type'];
				$length = null;
				$typeExtra = null;
			} else {
				list($type, $length) = explode('(', $field['Type'], 2);
				list($length, $typeExtra) = explode(')', $length, 2);
			}
			switch ($type) {
				case 'tinyint':
				case 'bit':
				case 'bool':
					$_fields[$field['Field']]['type'] = Db::DT_INT;
//					if (strpos($typeExtra, 'unsigned') === false) { // 有符号整数 
//						$_fields[$field['Field']]['min'] = -128;
//						$_fields[$field['Field']]['max'] = 127;
//					} else { // 无符号整数 
//						$_fields[$field['Field']]['min'] = 0;
//						$_fields[$field['Field']]['max'] = 255;
//					}
					break;
				case 'smallint';
					$_fields[$field['Field']]['type'] = Db::DT_INT;
//					if (strpos($typeExtra, 'unsigned') === false) { // 有符号整数 
//						$_fields[$field['Field']]['min'] = -32768;
//						$_fields[$field['Field']]['max'] = 32767;
//					} else { // 无符号整数 
//						$_fields[$field['Field']]['min'] = 0;
//						$_fields[$field['Field']]['max'] = 65535;
//					}
					break;
				case 'mediumint';
					$_fields[$field['Field']]['type'] = Db::DT_INT;
//					if (strpos($typeExtra, 'unsigned') === false) { // 有符号整数 
//						$_fields[$field['Field']]['min'] = -8388608;
//						$_fields[$field['Field']]['max'] = 8388607;
//					} else { // 无符号整数 
//						$_fields[$field['Field']]['min'] = 0;
//						$_fields[$field['Field']]['max'] = 16777215;
//					}
					break;
				case 'int';
				case 'integer';
					$_fields[$field['Field']]['type'] = Db::DT_INT;
//					if (strpos($typeExtra, 'unsigned') === false) { // 有符号整数 
//						$_fields[$field['Field']]['min'] = -2147483648;
//						$_fields[$field['Field']]['max'] = 2147483647;
//					} else { // 无符号整数 
//						$_fields[$field['Field']]['min'] = 0;
//						$_fields[$field['Field']]['max'] = 4294967295;
//					}
					break;
				case 'bigint';
					$_fields[$field['Field']]['type'] = Db::DT_INT;
//					if (strpos($typeExtra, 'unsigned') === false) { // 有符号整数 
//						$_fields[$field['Field']]['min'] = -9223372036854775808;
//						$_fields[$field['Field']]['max'] = 9223372036854775807;
//					} else { // 无符号整数 
//						$_fields[$field['Field']]['min'] = 0;
//						$_fields[$field['Field']]['max'] = 18446744073709551615;
//					}
					break;
				case 'year';
					$_fields[$field['Field']]['type'] = Db::DT_INT;
//					if ($length == 2) { // 两位数年份 
//						$_fields[$field['Field']]['min'] = 0;
//						$_fields[$field['Field']]['max'] = 99;
//					} else { // 四位数年份 
//						$_fields[$field['Field']]['min'] = 0;//1901;
//						$_fields[$field['Field']]['max'] = 2155;
//					}
					break;
				case 'float':
				case 'double':
				case 'double precision':
				case 'real':
				case 'decimal':
				case 'dec':
				case 'numeric':
					$_fields[$field['Field']]['type'] = Db::DT_DOUBLE;
					break;
				case 'varchar':
				case 'char';
				case 'national varchar':
				case 'national char':
				case 'nchar':
					$_fields[$field['Field']]['type'] = Db::DT_VARCHAR;
//					$_fields[$field['Field']]['min'] = 0;
//					$_fields[$field['Field']]['max'] = empty($length) ? 1 : intval($length);
					break;
				case 'tinyblob':
				case 'tinytext':
					$_fields[$field['Field']]['type'] = Db::DT_VARCHAR;
//					$_fields[$field['Field']]['min'] = 0;
//					$_fields[$field['Field']]['max'] = 255;
					break;
				case 'blob':
				case 'text':
					$_fields[$field['Field']]['type'] = Db::DT_VARCHAR;
//					$_fields[$field['Field']]['min'] = 0;
//					$_fields[$field['Field']]['max'] = 65535;
					break;
				case 'mediumblob':
				case 'mediumtext':
					$_fields[$field['Field']]['type'] = Db::DT_VARCHAR;
//					$_fields[$field['Field']]['min'] = 0;
//					$_fields[$field['Field']]['max'] = 16777215;
					break;
				case 'longblob':
				case 'longtext':
					$_fields[$field['Field']]['type'] = Db::DT_VARCHAR;
//					$_fields[$field['Field']]['min'] = 0;
//					$_fields[$field['Field']]['max'] = 4294967295;
					break;
				case 'datetime';
				    $_fields[$field['Field']]['type'] = Db::DT_DATETIME;
				    break;
				case 'date';
				    $_fields[$field['Field']]['type'] = Db::DT_DATE;
				    break;
				default: //enum,set,date,datetime,timestamp,time
					$_fields[$field['Field']]['type'] = Db::DT_VARCHAR;
					break;
			}
			
			$class .= "    /**\n";
			if ($field['Comment']) {
				$class .= "     * " . $field['Comment'] . " <br/>";
			} else {
			    $class .= "     *";
			}
			$class .= " 对应数据表字段: " . $field['Field'] . ' - ' . $field['Type']
			                         . ($field['Key'] ? ' (' . $field['Key'] . ')' : '') . "\n"
//                    . "     *   值范围: " . $_fields[$field['Field']]['min'] . "~" 
//                                         . $_fields[$field['Field']]['max'] . "\n"
			        . "     * 默认值: " . var_export($_fields[$field['Field']]['default'], true);
//			if (!$_fields[$field['Field']]['null']) {
			if ($field['Null'] == 'NO') {
			    $class .= " (不允许为空)\n";
			} else {
			    $class .= " (允许为空)\n";
			}
			if ($field['Collation'] && $field['Collation'] != 'NULL') {
			    $class .= "     * 字符集: {$field['Collation']}\n";
			}
//			if ($_fields[$field['Field']]['autoIncrement']) {
			if ($field['Extra'] == 'auto_increment') {
			    $class .= "     * 该字段值自动递增\n";
			}
			$class .= "     * @var " . $_fields[$field['Field']]['type'] . "\n";
			$class .= "     */\n";
			$class .= "    public \${$property} = null;\n";
		}
//		$class .= "    protected \$_class = '{$_class}';\n";
		$class .= "\n    protected \$_cache = " . ($_cache ? 'true' : 'false') . ";\n";
		$class .= "    protected \$_db = '{$_POST['db']}';\n";
		$class .= "    protected \$_table = '{$_table}';\n";
		$class .= "    protected \$_key = '{$_key}';\n";
		$class .= "    protected \$_fields = " 
		        . str_replace(array("=> \n ", "\n"),
		                      array("=>",     "\n    "), 
		                      var_export($_fields, true))
		        .  ";\n";
		$class .= "}\n";
		
		$class .= "/* 建表SQL语句: \n";
		$createSql = $query->getList('show create table ' . $table);
		$class .= $createSql[0]['Create Table'];
		$class .= "\n*/";
		
		$file = str_replace( 
                          array('/', '\\'), 
                          array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR),
                          PATH_APP_ENTITIES . $_class . '.class.php');
		file_put_contents($file, $class);
		return "数据表 {$_table} 已经导出成Mapping文件到 {$file}";
	}
}