## Redis组件的文档

#### 安装组件
```
    composer require rouchi/jy_redis
```

#### 使用说明
- 已经在jy_core中封装好简易的使用方法，需要确保已经安装rouchi/jy_core组件

- 项目代码中引用
```
    use Jy\Facade\Redis;
```

#### 注意事项
- 需要设置配置文件的目录常量： ROUCHI_CONF_PATH

#### 使用案例
```
    use Jy\Facade\Redis;
   
   ...
   
   
   # 目前redis组件使用的是原生的redis的命令，使用时需要注意
   Redis::get(key, value);
```

#### 注意事项
> - 像Redis::set(key, val); 这种直接静态调用的写法，只支持redis的默认配置的实例，也就是conf下的redis.default配置的实例
> - 暂不支持多个redis实例的直接静态调用，仅支持配置中的默认的实例，其他实例的直接静态调用陆续开发中
> - 如果需要多个实例的调用，其他的非默认的实例，需要自己在项目封装。具体如下：
> > 底层支持获取不同配置的redis实例方法：
> > > public static function getInstance($model = '', $name = ''): model 即项中的redis配置文件名，默认是redis, name则是配置中的具体的配置项（下标），默认是redis
> > > 调用该方法会返回相应的实例，比如：redis2，然后直接：$redis2->set(key, val) 也即：
> > > Redis::getInstance($fileName, $name)->set($key, $val);
