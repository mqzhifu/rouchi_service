<?php
namespace Jy\Common\MsgQueue\Contract;

interface AmqpBaseInterface{
    function getChannel();
    function confirmSelectMode();//切换确认模式
    function txSelect();//切换事务模式
    function txCommit();//事务提交
    function txRollback();//事务回滚
    function setQueue($queueName,$arguments = null);//创建队列/设置队列
    function setExchange($exchangeName,$type,$arguments = null);//创建交换器/设置交换器
    function deleteExchange($exchangeName);//删除交换器
    function deleteQueue($queueName);//删除队列
    function bindQueue($queueName,$exchangeName,$routingKey = '',$header = null);//交换器绑定队列
    function unbindExchangeQueue($exchangeName,$queueName,$routingKey = "",$arguments = null);//队列解绑交换器
}
