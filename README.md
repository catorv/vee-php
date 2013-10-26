VEE-PHP
=======

a lightweight, simple, flexible, fast PHP MVC framework.

安装
----
建议将`vee-php`和你的项目安装在同一个目录内，这样可以多个项目共用一个`vee-php`拷贝。如：

    path
      |- your_project_1
      |- your_project_2
      '- vee-php


创建项目
-------

    $ php path/to/vee-php/bin/vee.php -n project_name
    
将自动创建`vee-php`项目默认的目录结构和文件：

    project_name
      |- config                  # 配置文件目录
      |  |- application.cfg.php  # 主配置文件（文件内有详细配置项说明）
      |  '- db.cfg.php           # 数据库配置文件
      |- data                    # 数据存放目录
      |  |- cache                # 缓存文件存放目录
      |  |- logs                 # 日志文件存放目录
      |  |- tmp                  # 临时文件存放目录
      |  '- upload               # 上传文件存放目录（不在WEB文档根下）
      |- helpers                 # 辅助器类存放目录
      |- htdocs                  # WEB文档根目录
      |  |- upload               # 上传文件存放目录（WEB文档根下）
      |  '- index.php            # 主程序文件（WEB文档根下唯一的php文件）
      |- language                # 语言包文件存放目录
      '- mvc                     # 项目的主要逻辑代码存放目录
         |- controllers          # 控制器类文件存放目录
         |  |- Index.do.php      # 默认控制器类
         |  '- tools             # 默认生成的Memcache和Mysql管理工具
         |- models               # 数据模型类
         |  '- entities          # ORM实体对象的mapping文件存放目录
         '- views                # 视图/模板文件存放目录
            '- Index.tpl.php     # 默认模板文件


配置文件
-------

除了主配置文件`applications.cfg.php`外，其他配置文件是可选的。

可以增加自定义配置文件（扩展名为`.cfg.php`），但必须存放在`config`目录下，并通过

    $customCfg = V::loadConfig('custom');
    
载入`config/custom.cfg.php`配置文件内容。

自定义配置文件的写法，请参考`confg/db.cfg.php`。


模型类
-----

    $ php path/to/vee-php/bin/vee.php -m model

将自动生成`mvc/models/Model.class.php`类文件。

模型类生成规则：

1. 文件存放在`mvc/models`及其子目录中。
2. 类名以大写字母开头，如果由多个单词组成，则以驼峰形式书写。如：`ModelName`
3. 文件名与类名相同，以`.class.php`为扩展名。如：`ModelName.class.php`

在代码中可以通过

    V::loadModel('ModelName');
   
或者

    V::loadModel('path/to/ModelName'); // 如果类文件存放在`mvc/model`子目录中的话
    
加载模型类文件。

*注：如果文件直接存放在`mvc/model`下，则可以不调用`V::loadModel`，`vee-php`框架会自动找到对应的文件并载入。但如果你对代码的性能有较苛刻的要求，建议最好每次都手动调用载入操作。*


辅助器类（helper）
----------------

辅助器类是一些与业务逻辑相对独立的工具类/函数库，其规则与模型文件类似，不同的是：

1. 文件存放在`helpers`及其子目录中
2. 建议以类的静态方法的形式组织函数库（详见vee-php源代码中`helpers`目录下的辅助器类）
3. 代码中以`V::loadHelper('HelperName');`的方式加载。跟模型文件一样，如果文件直接存放在`helpers`目录中，`vee-php`可以自动载入类文件。


控制器类
-------

    $ php path/to/vee-php/bin/vee.php -c name

将自动生成对应的控制器类及其模板文件

`vee-php`通过解析URL自动映射到控制器类，格式：

    http://www.domain.com/{controllerName}/{actionName}/arg1/arg2.{responseType}
    
或者 Restful 的形式

    http://www.domain.com/_{controllerName}/arg1/arg2.{responseType}
    
*注：此规则可以被`config/application.cfg.php`中的`Config::$route`列表所影响*

### `{controllerName}`定义控制器类名及文件路径。

举例说明：

1. 带路径的控制：`test_test_test`，表示：
   - 控制器文件存放在`controllers/test/test/Test.class.php`。  
     *注：路径中的最后一项被当做文件名，且首字母会自动被转换为大写字母*
   - 类名为`test_test_Test`。  
     *注：这个类名不需要记忆，绝大部分情况下不需要开发者手动创建这个类的实例，但如果你手动创建控制器文件的话，就需要知道这个规则*
   - 对应的模板文件存放在`views/test/test/Test.tpl.php`。
   
2. 不带路径的控制：`test`，表示：
   - 控制器文件存放在`controllers/Test.class.php`。
   - 类名为`_Test`。  
     *注：类名有个前缀下划线“_”，使控制器类名跟其他类不容易重名*
   - 对应的模板文件存放在`views/test/test/Test.tpl.php`。

### `{actionName}`定义控制器Action方法，之后是Action的参数。

1. Action方法总是以`do`开头。如：`doDefault`。
2. Action方法的参数列表应该总是有默认值，以防用户在输入URL地址时漏了需要的参数而导致执行不正确。
3. `{controllerName}`的默认值为`index`。
4. `{actionName}`的默认值为`default`。
5. `~`占位符代表默认值。如：`http://www.domain.com/~/~/arg1/arg2` 相当于 `http://www.domain.com/index/defalut/arg1/arg2`
6. Restful形式的控制器的Action方法名称就是HTTP的Method名称。如：`doGet`

举例说明：（以下假设`$controller`是控制器`_Test`的实例）

1. `http://www.domain.com/test/say/hello/cator.html`将调用`$controller->doSay('hello', 'cator');`
2. `http://www.domain.com/_test/money.html`（GET方式调用）将调用`$controller->doGet('money');`
3. 以此类推

模板文件
-------

模板文件多以`.tpl.php`为扩展名，取决于对应的`Response`类（详见`vee-php`源代码中`response`目录）。

控制器类通过以下顺序寻找对应的模板文件：（假设模板文件扩展名为`.tpl.php`）

1. 当前目录下的`{controllerName}_{actionName}.tpl.php`文件
2. 当前目录下的`{controllerName}.tpl.php`文件
3. `mvc/views`目录下与`mvc/controllers`目录相对于的目录中的`{controllerName}_{actionName}.tpl.php`文件
4. `mvc/views`目录下与`mvc/controllers`目录相对于的目录中的`{controllerName}.tpl.php`文件
5. 当前目录下的`404.tpl.php`文件
6. `mvc/views`目录下与`mvc/controllers`目录相对于的目录中的`404.tpl.php`文件

如果以上文件都不存在，则抛出错误。


写在最后
-------

`vee-php`是本人多年前写的框架，其目的不是大而全的所谓“框架”。PHP采用一种类似“阅后即焚”的运行机制，每个请求都会重新载入代码，创建实例，然后在使用完后立即销毁，作为一个对代码有“洁癖”的人，是不能接受稍微有点臃肿的框架的。

`vee-php`实现了对逻辑代码文件的灵活组织管理，而且是非常高性能地做到了这点，这就是它的所有价值。

虽然也提供了API文档，但源代码中有详尽的说明，我建议大家去阅读源代码，去感受`vee-php`的设计理念。

- - - - -

若有任何疑问或建议，请随时与我联系！

Don't be shy! Just email me: <catorwei@gmail.com> !









