## DB组件的文档

#### 安装组件
```
    composer require rouchi/jy_db
```

#### 使用说明
- 已经在jy_core中封装好简易的使用方法，需要确保已经安装rouchi/jy_core组件

- 项目代码中引用
```
    use Jy\Facade\DB;
```
- 支持的方法
> - 目前仅支持： 单条|批量insert,update, 单条|多条查询
> - insert(talbe, data) # 单条写入
> - multiInsert(talbe, data) # 单条写入
> - update(sql, param) # update
> - findAll(sql, param) #
> - findOne(sql, param) #
> - beginTransaction()
> - commit()
> - rollBack()

#### 使用案例
```
    use Jy\Facade\DB;

    ....

    # 单条
    $data = ["password" => 'pwd123', "name" => 'name12234', "mobile" => 'mb12345'];
    DB::insert('user', $data);

    #批量
    $data = [
        ["password" => 'pwd123', "name" => 'name12234', "mobile" => 'mb12345'],
        ["password" => 'pwd456', "name" => 'name789', "mobile" => 'mb678'],
    ];
    DB::multiInsert('user', $data);

    # 查询
    DB::findAll($sql, []);
    DB::findOne($sql);

    # 事务
    DB::beginTransaction();

    # 如果同一个项目用到了多实例，则：
    DB::getInstance('mysql2', 'read', 'file')->insert(...);
    # mysql 为配置文件中的配置项，参考lumen。 read则为读写配置,默认根据会根据具体的方法(insert)判定，file则可以指定具体的配置文件名，默认是database

    # 也可以直接：DB::insert($sql, $param, 'mysql2', 'write', 'filename'); 后三项同上，默认，database里面的mysql的实例配置
```

#### 关于数据库配置
> 框架会读取外部的rouchi_conf的对应的项目的配置：
> DB则会默认读取database.php 的connects 里面的mysql的配置：配置形式可以参考lumen
> 如果要使用多个数据库链接，除了mysql那一项的配置，其他的链接配置的DB实例需要自己封装一下
>  比如： DB底层获取实例的方法的参数有3个：
>  >    public static function getInstance($name = '', $type = "write", $model = '')
>  >    model即配置文件夹的名称：比如database, $name即connects里面的链接配置项：比如mysql, $type：则为指定读库或者是写库
>  >    每个操作都可以指定不同的链接，当然，不指定就是默认的：mysql

##### 关于数据库使用规范
> - 不建议使用ORM
> - 建议所有的查询保持单表查询，高效
> - 建议每次的查询：写成sql+bindParam的形式，也就是我们提供的那种，接近原生PDO的形式，简单
