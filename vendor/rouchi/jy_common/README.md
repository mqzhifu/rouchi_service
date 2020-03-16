数据验证
=============
1. 格式验证，如：范围1-100 、长度：1-20、手机号、邮箱
2. 数据类型验证：如：int string bool

输写验证格式 
---------------
key : [验证类型1|验证类2|验证类型3...]  

如下DEMO:  
"cnt": "int|numberMax:10|numberRange:2,15"  
>cnt就是key ，后面的部分 就是你想要验证的类型
 
验证类型：标量与复杂
----------------  

标量  
>包括：整形、布尔、字符串等。还可以验证：长度、范围、邮箱等。   

复杂 
>复杂包括：数组（多维，递归），对象。  
>具体支持类型，可参考Jy\Common\Valid\Valid\filter  


例子：
-----------------------------

demo1
>参数名为 cnt ，规则为：整形   不能大于10   数字范围为2-15  
```java
"cnt": "int|numberMax:10|numberRange:2,15"
```
demo2  
>定义 KEY为数数组类型，且数组里面都是int|必填写  
```java
   {"int|require"}
```

demo3  
>定义 KEY为字符串(hashTable) 的数组  
```java
	{"title":{"int|require"},"id"{{"int|require"}}}
```


详细使用规则如下：
---------------------------
```java
$rule = '{
{
	"cnt": "int|numberMax:10|numberRange:2,15",
	"name": "require|string|lengthMin:10",
	"price": "require|float",
	"myOb": "require|object",
	"email": "email|lengthRange:10,20",
	"dataArrOneNumRequire": ["int|require"],
	"dataArrOneNum": ["int"],
	"dataArrTwoNum": [
		["int"]
	],
	"dataArrTwoNumRequire": [
		["require|int"]
	],
	"dataArrThreeNum": [
		[
			["int"]
		]
	],
	"dataArrOneStr": {
		"title": "string",
		"id": "int"
	},
	"dataArrOneStrRequire1": {
		"title": "string|require",
		"id": "int"
	},
	"dataArrOneStrRequire2": {
		"title": "string|require",
		"id": "int|require"
	},
	"dataArrTwoStr": {
		"company": {
			"name": "string",
			"age": "require|int"
		},
		"id": "require|int"
	},
	"dataArrTwoStrRequire": {
		"company": {
			"name": "require|string",
			"age": "require|int"
		},
		"id": "require|int"
	},
	"dataArrOneNumberOneStr": [{
		"school": "string|require",
		"class": "int|require"
	}],
	"dataArrOneStrOneNumber": {
		"range": ["require|int"],
		"id": "require|int"
	}
}
//echo json_encode($rule);
//exit;

class MyOb{

}

$myOb = new MyOb();
//array('int','string','float','bool');
$data = array(
    'cnt'=>2,
    'name'=>'aaaaaaaaaaa',
    'price'=>1.02,
    'isLogin'=>false,
    'myOb'=>$myOb,
    'email'=>'mqzhifu@sina.com',
    'stream'=>2222,
    'dataArrOneNum'=>array(1,6,9,10),
    'dataArrTwoNum'=>array(
        array(1,6,9,10),
        array(2,4,6,8),
    ),
    'dataArrOneStr'=>array("aaaa"=>1,'id'=>2,'title'=>'last'),
    'dataArrTwoStr'=>array(
        "company"=>array("name"=>'z','age'=>12),
        'id'=>2),
    'dataArrOneNumberOneStr'=>array(
        array('class'=>1,'school'=>'Oxford'),
        array('class'=>2,'school'=>'Harvard'),
    ),
    'dataArrOneStrOneNumber'=>array(
        'id'=>99,
        'range'=>array(1,2,3,4,)
    ),
);

Valid::match($data,$rule);
```

rabbitMq 队列
============
准备工作：
>依赖包 依赖 "php-amqplib/php-amqplib" &&  rouchi/Jy_config" composer update  
>配置文件：需要提前写好，config 包 会调用 ,DEMO如下：  
```javascript
rabbitmq.php

return [
    'rabbitmq'=>[
        'host' => '127.0.0.1',
        'port' => 5672,
        'user' => 'root',
        'pwd' => 'root',
        'vhost' => '/',
    ]
] ;


```

具体代码逻辑 可参考TEST目录下的client.php server.php  
建议：在本地安装个rabbitmq，自带可视化工具，方便测试  

角色描述
--------------
product:生产者，用于定义消息内容，及发送消息  
consumer:消费者，拿到生产者的消费，进行逻辑处理。  
rabbitmq-server:接收 product 发送的消息 , push 给consumer  

生产者-基础流程
--------------
1. 先定义一个生产类(如：OrderBean)，只需要继承一个基类(MessageQueue)即可（类名随意）
```java

use Jy\Common\MsgQueue\MsgQueue\MessageQueue;
class OrderBean extens MessageQueue{
    public $_id = 1;
    public $_channel = "";//来源渠道
    public $_price = 0.00;//金额
    public $_num = 0;//购买数量
    public $_uid = 0;//用户ID
    
    ..doing something..
}
```
>生产者的基础定义工作即结束，下面开始代码操作

3. 初始化要发送的数据(使用刚刚定义好的bean类)
```javascript

$OrderBean = new OrderBean();
$OrderBean->_id = 1;
$OrderBean->_channel = "tencent.";
$OrderBean->_price = "1.12";

```

4. 发送一条普通的消息
```javascript
$OrderBean->send();
```

5. 发送一条延迟5秒的消息
```java
//时间单位：微秒
$OrderBean->sendDelay(5000);
```
>注：最小值为1000，也就是1秒;最大值为7天。
>最简单的demo,即完成了.

#如果担心，服务器丢失生产者发送的消息，可使用<确认模式>
```java
class OrderBean extends MessageQueue{
    public $_id = 1;
    public $_price = "";

    function __construct(){
        parent::__construct();
        $this->setMode(1);
        $this->regUserCallbackAck(array($this,'ackHandle'));
    }

    function ackHandle($data){
        echo "OrderBean receive rabbitmq server callback ack info. end<br/>";
    }
}

```
>$this->setMode(1);  开启确认模式  
>regUserCallbackAck; 注册，回调函数




>消息参数除了日常的，还有头部信息：上面有很多扩展字段可以用上。比如：message_id用于可靠性。  
>像： message_id type Timestamp 基类已占用 ，send的时候，由基类自动生成   

#消费者-简单开启
$OrderBean->groupSubscribe($userCallback,"dept_A");
>最简单一的一个消费者  进程 已开启.
>dept_A:是consumerId ，唯一标识，如果是第一次使用，类会帮助你完成：队列创建、绑定等工作  
>业务人员可随意使用，随意创建  
>如果相同 的consumerId ,开启了多个，类似nginx的负载均衡，每个consumer都会hash到一条消息  

#消费者 开启一个consumer. 监听多个event
```javascript
```java

class HandleUserBean{
    function process($data){
//        var_dump($data['body']);
        echo "im in HandleUserBean method: process \n ";
        //也可以自定义返回  ACK
        return array("return"=>"ack");
    }
}

class HandleUserSmsBean{
    function doing($data){
//        var_dump($data['body']);
        echo "im in HandleUserSmsBean method: doing \n ";
        //也可以自定义返回  ACK
        return array("return"=>"ack");
    }
}


class ConsumerSms extends MessageQueue{
    function __construct()
    {
    function __construct(){
        parent::__construct();
    }

    function init(){
        $queueName = "test.header.delay.sms";
        $queueName = "test.header.delay.user";

        //一次最大可接收rabbitmq消息数
        $this->setBasicQos(1);
//        $durable = true;$autoDel = false;
//        $this->createQueue();
        $durable = true;//持久化
        $autoDel = false;//如果没有consumer 消费将自动 删除队列
        $this->createQueue($queueName,null,$durable,$autoDel);

        $ProductSmsBean = new ProductSmsBean();
        $handleSmsBean = array($this,'handleSmsBean');
        $this->setListenerBean($ProductSmsBean->getBeanName(),$handleSmsBean);
        $this->setListenerBean($ProductSmsBean,$handleSmsBean);
        //======================================================


        $ProductUserBean = new ProductUserBean();
        $handleUserBean = array($this,'handleUserBean');
        $this->setListenerBean($ProductUserBean->getBeanName(),$handleUserBean);

        $HandleUserBeanClass =  new HandleUserBean();
        $handleUserBean = array($HandleUserBeanClass,'process');
        $this->setListenerBean($ProductUserBean,$handleUserBean);


        $HandleUserSmsBean =  new HandleUserSmsBean();
        $handleUserBean = array($HandleUserSmsBean,'doing');
        $this->setListenerBean($ProductUserBean,$handleUserBean);

        //=================================================
        $ProductUserBean = new ProductOrderBean();
        $handleUserBean = array($this,'handleOrderBean');
        $this->setListenerBean($ProductUserBean,$handleUserBean);

        $this->subscribe($queueName,null);
    }

    function handleSmsBean($data){
        var_dump($data['body']);
        echo "im sms bean handle \n ";
        //什么都不返回，默认情况，框架会自动 ACK
    }

    function handleUserBean($data){
        var_dump($data['body']);
        echo "im user bean handle \n ";
        //也可以自定义返回  ACK
        return array("return"=>"ack");
    }

    function handleOrderBean($data){
        var_dump($data['body']);
        echo "im order bean handle \n ";
        //这里是，假设：发现数据不对，想将此条消息打回，有2种选择
        //1   reject 配合requeue :true 不要再重试了，直接丢弃。  false:等待固定时间，想再重试一下
        //2   直接抛出异常  ,框架会 给3次重试机会，如果还是一直失败，则抛弃
        return array("return"=>"reject",'requeue'=>false);
    }

}
```
1. 定义一个新类(ConsumerSms)，继承基类(MessageQueue)
2. 找到你关心的消息类（event），也就是生产者定义的bean
3. 将N个bean 绑定到基类上，并设置回调处理函数  ( setListenerBean 方法 )
4. 启动订阅

$lib = new ConsumerSms();
$lib->init();

```
1. 定义一个新类(ConsumerSms)，继承基类(MessageQueue)  
2. 找到你关心的消息类（event），也就是生产者定义的bean  
3. 将N个bean 绑定到基类上，并设置回调处理函数  ( setListenerBean 方法 )。了个bean也可以同时绑定多个handle  
4. 启动订阅  


#编程模式
>rabbitmq 是基于erlang模式，全并发模式(channel)。也就是全异步模式(基类里我规避了这种方式，但牺牲部分性能)  
>大部分你的操作，比如：send 实际上并不是以同步方式拿到mq返回的值。  
>很多业务都是基于callback function 方式，所以使用时请注意下.  


#测试用例
>test目录下有 client.php server.php 简易测试用例，再参考该文档即可。
>最好在本地安装个rabbitmq，自带可视化工具，方便测试  

#消息可靠性
>业务人员在投递/消费时，最好借助三方软件，如：redis|mysql ，持久化该消息状态。避免丢消息或重复消费，也方便跟踪  
>理论上：事务模式更靠谱，但是跟确认模式差了10倍左右（官方给的是100倍左右）。  
>建议：对一致性可靠性要求比较高的业务，如：订单业务考虑事务。次级重要的用确认模式，不是太重要的可以正常发送即可。  
>注：事务模式与确认互斥

#简单压侧效果
>win7 PHP单进程 循环给rebbitmq发消息  
>普通模式： 10000条，时间：0.32-0.4     100000条：4.1-3.59 . 官方说每秒10万条，可能LINUX下更快  
>确认模式： 10000条，时间：0.42-0.5    100000条：5  
>事务模式： 10000条，时间：2.4-2.7    100000条：好吧，我放弃了。。。超时状态  

#重试机制
>当一条消息处理过程中发生异常，会被重新发送到队列，以阶梯的形式，可再次读取  
>如：第一次发生异常该方向会重新回到到队列中但是会在5秒之后才出现，第2次是10秒，第3次是30....     
>具体阶梯的时间可设置，具体重试次数可设置。能很好的防止网络抖动或者LINUX假死  

#各种配置注意：
>正常新开一个队列，系统默认最大值为10W条，超出即会丢掉（如配置死信队列会进到死信队列中）  
>正常新建队列不建议使用定义化参数，比如：开启自动ACK、autoDel(自动删除)、  
>正常新建队列最好都设置持久化属性，投递消息也是。至少MQ挂了重启，还可以找回数据  
>设置消息的TTL时，尽量要考虑一但消息堆积过多，还没处理过，部分数据就失效且丢失了  
>编写consumer一定要做好异常捕获，不然进程一但挂了，消息可是无止境堆积。  

#追踪
>mq不提供太多可追踪的工具，可以使用后台管理系统。但做不到100%  
>建议，业务方，最好把发送的一些消息在自己业务上做持久化。  

#集群
>目前暂时没有集群，到达量后，会开启镜像模式集群，防止单点故障  

#分布式
>暂不支持，到达量后，可配合集群一起使用  

#延迟队列插件  
>由erlang编写，实际流程为 用户发送一条延迟消息 插件捕获，存于MnesiaDB，到时间后(erlang+timer)再投递到队列中  
>rabbitmq server 意外中止，或者手动停止，消息依然存在，且重启后 继续有效。  
>如果手动禁止该插件，数据会丢失 rabbitmq-plugins disable rabbitmq_delayed_message_exchange  
>暂不支持  <撤消>功能，业务人员可自行实现，每条send后，SERVER会返回msgId,业务人员可自行判定处理  

#编写consuer注意事项
>一定要做好 异常 捕获，否则会导致进程意外退出，消息积压。  
>进程以守护方式启动，很容易产生内存泄漏或未知异常，建议参考apache的多进程+多线程的方式，当处理了N条消息后，自动重启.  

#异常错误码
>参照 rabbitmqBean 基类里的 描述文件  

