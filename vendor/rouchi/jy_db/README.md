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
> - insert(talbe, feilds, data) # 单条写入 data为一维数组， 下标与feilds对应
> - multiInsert(talbe, feilds, data) # 单条写入, data 二维数组， 下标与feilds对应
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

    $feilds = ['password', 'name', 'mobile'];

    # 单条
    $data = ["password" => 'pwd123', "name" => 'name12234', "mobile" => 'mb12345'];
    DB::insert('user', $feilds, $data);

    #批量
    $data = [
        ["password" => 'pwd123', "name" => 'name12234', "mobile" => 'mb12345'],
        ["password" => 'pwd456', "name" => 'name789', "mobile" => 'mb678'],
    ];
    DB::multiInsert('user', $feilds, $data);

    # 查询
    DB::findAll($sql, []);
    DB::findOne($sql);

    # 事务
    DB::beginTransaction();
```
