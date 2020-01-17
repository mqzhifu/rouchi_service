## config组件的文档

#### 安装组件
```
    composer require rouchi/jy_config
```

#### 使用说明
- 已经在jy_core中封装好简易的使用方法，需要确保已经安装rouchi/jy_core组件

- 项目代码中引用
```
    use Jy\Facade\Config;
```

#### 注意事项
- 需要设置配置文件的目录常量： ROUCHI_CONF_PATH

#### 使用案例
```
    use Jy\Facade\Config;
   
   ...
   
   # file 为 ROUCHI_CONF_PATH 下的文件名
   Config::get(file, item); # item也可以指定深维度的数据， item.sub_item
```
