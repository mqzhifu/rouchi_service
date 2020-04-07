<?php

namespace App\Services\V2;

use App\Services\V2\CpService;
use App\Services\V2\CpuCouponService;

use App\Models\V1\OrderCouponModel;


class CpuCouponService implements \App\Interfaces\V2\CpuConpon {
    const STATUS_ON_SHELVES = 1;
    const STATUS_OFF_SHELVES = 2;
    const CPU_STATUS = [
        self::CPU_ON_SHELVES => '上架',
        self::CPU_OFF_SHELVES => '下架',
    ];

    const STUDENT_TYPE_OLD = 1;
    const STUDENT_TYPE_NEW = 2;
    const STUDENT_TYPE_DESC = [STUDENT_TYPE_NEW=>'新学员',STUDENT_TYPE_OLD=>'老学员'];


    private $cpService = null;
    private $cpuService = null;

    function __construct(CpService $cpService,CpuCouponService $cpuCouponService){
        $this->cpService = $cpService;
        $this->cpuService = $cpuCouponService;
    }

    function getListByCondition(array $condition,array $pageInfo,$isCnt = 0 ){
        $where = " active = 1 ";
        $list = null;
        if(arrKeyExist($condition['price'])){
            $couponMin = intval($condition['price']['min']);
            $couponMax = intval($condition['price']['max']);
            if ($couponMin < 0) {
                $couponMin = 0;
            }
            if ($couponMax < 0) {
                $couponMax = 0;
            }
            if ($couponMin && $couponMax ) {
                if($couponMin > $couponMax){
                    throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['couponMin > couponMax']);
                }

                $where .= " and price >= $couponMin and price <= $couponMax";
            }elseif($couponMin){

            }
            if ($couponMin) {
                $where .= " and price >= $couponMin";
            } else {
                $where .= " and price <= $couponMax";
            }
        }

        if(arrKeyExist($condition['status'])){
            $where .= " status  = ".$condition['status'];
        }

        if(arrKeyExist($condition['cpuId'])){
            $where .= " cpu_id  = ".$condition['cpu_id'];
        }

        if($isCnt){
            $list = OrderCouponModel::where($where)->count()->toArray ();
        }else{
            $list = OrderCouponModel::where($where)->get()->toArray ();
        }

        return $list;
    }

    public function getById(int $id){
        $info = OrderCouponModel::getById($id);
        return $info;
    }

    public function getListByCpuId(int $cpuId){
        $list = OrderCouponModel::whele("cpu_id = $cpuId");
        return $list;
    }

    public function getListByNo(string $no){
        $list = OrderCouponModel::whele(" no  = '$no'");
        return $list;
    }
//添加一张 优惠卷 根据CPUID
    function addOneByCpuId(int $cpuId,array $data){
        $cpu = $this->cpuService->getById($cpuId);
        if(!$cpu){

        }

        $time = time();

        $data = array(
            'no'=>$a,
            'min_required_consumption'=>$a,
            'price'=>$a,
            'status'=>self::STATUS_OFF_SHELVES,
            'created_at'=>$time,
            'updated_at'=>$time,
            'active'=>$a,
            'cpu_id'=>$cpuId,

        );

        OrderCouponModel::create($data);
    }
    //删除一张优惠卷
    function delOneById($id){
        $coupon = $this->getById($id);
        if(!$coupon){

        }

        OrderCouponModel::deleteById($id);
    }
    //编辑 - 若干 优惠卷
    function editSomeByIds(array $ids,array $data){

    }
    //记录变更日志
    function record(array $data){
        OrderCouponModel::create($data);
    }
}