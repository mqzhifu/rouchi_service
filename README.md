### 框架使用文档


#### 安装框架模板
```SSH
 1,   git clone ssh://git@gitlab.rouchi.com:10022/php/rouchi_service_template.git
```
```SSH
 2,   cd .. && mv rouchi_service_template "your app name"  # 修改为项目名称
```
```shell
3.    cd "your app name" && rm -rf .git ## 删除git数据
```
```shell
4 .   初始化项目 git init ....
```
#### 添加依赖的组件
```
    composer.json文件不包含在项目里面，有管理员统一管理，需要索要该文件。
```

#### 安装组件
```
    安装或者升级组件
    composer install | update

    如果安装失败：可以先执行下： composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
```


#### 注意事项
- 不能将项目根目录下的vendor文件夹纳入版本控制里面，（每次发版运维会自动检测组件是否需要更新，会覆旧的）
- 控制器需要继承 Jy\Controller基类;
- 返回数据 ： 直接在控制器中：
```
    return $this->json($data);
```
- 获取接口的请求数据 ：直接在控制器中
```
    $data = $this->data;
```
- 获取header头：
```
	$header = $this->request->getHeader('header', 'default');
```

- 注意：代码中谨慎使用：$_GET,$_POST,$_SERVER 等$_ 相关的php的超全局变量 

- 接口的参数验证，通过注释的形式，写在每个action的上面，校验格式为json, 注释的关键字为：valid
```

	// index  action 
	/*
	 *
	 * @valid {'id':'require|int'} 
	 *
	 */
	public function index()
	{
		$data = $this->data;
		return $this->json($data);
	}

```
- 接口的中间件配置，通过注释的形式，写在每个action的上面，校验格式为: namespace::class, 注释的关键字为： middleware 。有多个中间件的话，可以写多个。按照顺序，挨个执行。
- 另外，还有一个全局的中间件配置，在app里面的Middleware的参数里面，以数组的形式配置，按照顺序，挨个执行。
```

	// index  action 
	/*
	 *
	 * @middleware \Rouchi\Middlewres\tokenMiddlware::class 
	 $ @middleware \Rouchi\Middlewres\signMiddlware::class
	 *
	 */
	public function index()
	{
		$data = $this->data;
		return $this->json($data);
	}

```

#### 路由规则
- 通过uri解析，匹配文件路径
> - 形式为： /v1/path/to/controller/action
> - 第一项是版本号，最后一项是action,则中间部分为控制器部分，即Controller文件加下的路径部分。因此所有的控制器需要写在Controller文件夹下，并且uri中不可包含这个Controller部分

#### console模式
- 路由规则参考fpm模式的url的规则
- console的文件统一存放在Console文件夹下，以与Controller文件区分开来
- 调用方式： ./Jy /path/to/controller/action ...params    比如： ./Jy /v1/index/index 123
- 命令行下的参数，目前仅支持通过顺序来获取

#### 可用的组件
###### 可用的版本号可到具体项目中查看版本历史(tag或者release)
- [rouchi/jy_core](https://gitlab.rouchi.com/php_sevice_group/jy_core/tags)
- [rouchi/jy_common](https://gitlab.rouchi.com/php_sevice_group/jy_common/tags)
- [rouchi/jy_db](https://gitlab.rouchi.com/php_sevice_group/jy_db/tags)
- [rouchi/jy_config](https://gitlab.rouchi.com/php_sevice_group/jy_config/tags)
- [rouchi/jy_redis](https://gitlab.rouchi.com/php_sevice_group/jy_redis/tags)
- [rouchi/jy_log](https://gitlab.rouchi.com/php_sevice_group/jy_log/tags)
- [rouchi/jy_service_client](https://gitlab.rouchi.com/php_sevice_group/jy_service_client/tags)
