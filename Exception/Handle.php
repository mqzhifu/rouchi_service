<?php

namespace Rouchi\Exception;

use Jy\Contract\Exception\JyExceptionAbstract;

class Handle extends JyExceptionAbstract
{
	// 排除上报的错误(Exception)类型  
	protected $dontReport = [
		// namespace::class
	];

	/**
	 * 用于需要上报的操作, 底层会自动排除$dontReport中设置的exception类型
	 * @Author   jingzhiheng
	 * @DateTime 2020-02-04T17:23:30+0800
	 * @param  $exception
	 */
	public function report($exception)
	{
		// sentry
		// (new sentry)->captureException($exception);
	}

	/**
	 * deal the exception
	 * 
	 * @Author   jingzhiheng
	 * @DateTime 2020-02-04T17:23:30+0800
	 * @param  obj  $exception
	 * @return array  [$code, $message, $data]  type : [int, string, array] 自定义返回值，覆盖系统默认的返回值
	 */
	public function handle($exception):array
	{
		//..

		return [500, 'error', ['error_message' => $exception->getMessage()]];
	}
}