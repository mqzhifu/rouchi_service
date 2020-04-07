<?php

namespace App\Services\V2;

use App\Models\V1\CourseProductUnitModel;
use App\Models\V1\CourseProductUnitRecordModel;
use App\Services\V2\CpService;

class CpuService implements \App\Interfaces\V2\Cpu {
    const STATUS_ON_SHELVES = 1;
    const STATUS_OFF_SHELVES = 2;
    const CPU_STATUS = [
        self::CPU_ON_SHELVES => '上架',
        self::CPU_OFF_SHELVES => '下架',
    ];

    const APP_ID_XIAOE = 1;
    const APP_IDS = [
        self::APP_ID_XIAOE => '小鹅通',
    ];

    public $cpService = null;

    function __construct(CpService $cpService){
        $this->cpService = $cpService;
    }

    function getOneById(int $id ){
        $row = CourseProductUnitModel::getById($id);
        return $row;
    }
    function getSomeByIds(string $ids){
        $ids = explode(",",$ids);
        foreach ($ids as $k=>$id) {
            $id = (int)$ids;
            if(!$id || $id <=0){
                exit("ids some on is null");
            }
        }

//        $ids = implode($ids,",");
        return CourseProductUnitModel::whereIn("id",$ids)->get()->toArray ();
    }

    function delOneById(int $id){

    }

    function getSomeByNo(string $no){
//        if (!$request->no) {
//            return $this->errorResponse('no is required');
//        }
        return CourseProductUnitModel::where("no",$no)->get()->toArray ();
    }
    function getListByCondition(array $condition = [] ,array $pageInfo){
        //参数校验
//        $validate = Validator::make($request->post(), [
//            'courseType' => 'bail|numeric',
//            'status' => ['bail', 'numeric', Rule::in(array_keys(self::CPU_STATUS))],
//            'courseTextbookType' => 'bail|numeric',
//            'keyword' => 'bail|string',
//            'couponMin' => 'bail|numeric',
//            'couponMax' => 'bail|numeric',
//            'page' => 'bail|numeric',
//            'limit' => 'bail|numeric',
//        ]);
//        if ($validate->fails()) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, $validate->errors()->all());
//        }

//        $page = intval($request->post('page'));
//        if ($page < 1) {
//            $page = 1;
//        }
//        $limit = intval($request->post('limit'));
//        if ($limit < 10 || $limit > 100) {
//            $limit = 10;
//        }

        $where = " active = 1 ";
        if($condition){
//            $whereFun = function ($query) use ($keyword) {
//                $query->where('course_product_unit.name_CN', 'like', '%'.$keyword.'%')
//                ->orWhere(function ($query) use ($keyword) {
//                    $query->where('course_product_unit.name_EN', 'like', '%'.$keyword.'%');
//                })
//                ->orWhere(function ($query) use ($keyword) {
//                    $query->where('course_product_unit.no', 'like', '%'.$keyword.'%');
//                })
//                ->orWhere(function ($query) use ($keyword) {
//                    $query->where('course_product.name_CN', 'like', '%'.$keyword.'%');
//                })
//                ->orWhere(function ($query) use ($keyword) {
//                    $query->where('course_product.name_EN', 'like', '%'.$keyword.'%');
//                })
//                ->orWhere(function ($query) use ($keyword) {
//                    $query->where('course_product.no', 'like', '%'.$keyword.'%');
//                });
//            };

            if(arrKeyExist($condition,'course_type')){
                $where .= " and course_type = '{$condition['course_type']}";
            }

            if(arrKeyExist($condition,'status')){
                $where .= " and status = '{$condition['status']}";
            }

            if(arrKeyExist($condition,'coupon')){

            }

            if(arrKeyExist($condition,'courseCtextbookType')){

            }
        }


//        $offset = ($page - 1) * $limit;
//        $total =  $this->courseProductUnitService->getCpuCount($where, $whereFun, $whereIn, $whereNotIn);
//        $list = $this->courseProductUnitService->getCpuList($where, $whereFun, $whereIn, $whereNotIn, $offset, $limit);
//        if ($list) {
//            $ids = [];
//            foreach ($list as $val) {
//                $ids[] = $val['id'];
//            }
//            $couponNum = $this->courseProductUnitService->getCouponNumByCpuIds($ids);
//            $couponNum = array_column($couponNum, null, 'cpu_id');
//            foreach ($list as $key => $val) {
//                $list[$key]['couponCount'] = isset($couponNum[$val['id']]) ? $couponNum[$val['id']]['num'] : 0;
//            }
//        }
//
//        $data = [
//            'items' => $list,
//            'page' => $page,
//            'limit' => $limit,
//            'total' => $total,
//        ];
//        return $this->successResponse('success', $data);




    }

    function editOneStatusById(int $id ,int $status,int $adminId){
//            'id' => 'bail|required|numeric',
//            'status' => ['bail', 'required', 'numeric', Rule::in(array_keys(self::CPU_STATUS))],
//            'adminId' => 'bail|required|numeric',

        //        //参数校验
//        $validate = Validator::make($request->post(), [
//            'id' => 'bail|required|numeric',
//            'status' => ['bail', 'required', 'numeric', Rule::in(array_keys(self::CPU_STATUS))],
//            'adminId' => 'bail|required|numeric',
//        ]);
//        if ($validate->fails()) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, $validate->errors()->all());
//        }


        $cpu = $this->getOneById($id);
        if (!$cpu) {
            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['id']);
        }

        $status = intval($status);
        if ($cpu['status'] == $status) {
            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['status']);
        }
        $data['status'] = $status;

        CourseProductUnitModel::updateById($id, $data);
        $recordData = [
            'cpu_id' => $id,
            'editor_id' => $adminId,
            'edited_at' => date('Y-m-d H:i:s'),
            'active' => 1,
            'memo' => '设置CPU的状态：' . self::CPU_STATUS[$status],
        ];
        $this->record($recordData);
        return $this->successResponse('success');

    }
    function editOnePriceById(int $id ,array $price ,int $adminId){
//        //参数校验
//        $validate = Validator::make($request->post(), [
//            'id' => 'bail|required|numeric',
//            'originalPrice' => 'bail|required|numeric',
//            'salePrice' => 'bail|required|numeric',
//            'minimumPrice' => 'bail|required|numeric',
//            'adminId' => 'bail|required|numeric',
//        ]);
//        if ($validate->fails()) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, $validate->errors()->all());
//        }
        $cpu = $this->getOneById($id);
        if ($cpu) {
            $originalPrice = intval(formatPriceToFen(floatval($price['originalPrice'])));
            $originalSingleSessionPrice = intval($originalPrice / $cpu['session_times']);
            $salePrice = intval(formatPriceToFen(floatval($price['salePrice'])));
            $saleSingleSessionPrice = intval($salePrice / $cpu['session_times']);
            $minimumPrice = intval(formatPriceToFen(floatval($price['minimumPrice'])));
            $minimumSingleSessionPrice = intval($minimumPrice / $cpu['session_times']);
            $data = [
                'original_price' => $originalPrice,
                'original_single_session_price' => $originalSingleSessionPrice,
                'sale_price' => $salePrice,
                'sale_single_session_price' => $saleSingleSessionPrice,
                'minimum_price' => $minimumPrice,
                'minimum_single_session_price' => $minimumSingleSessionPrice,
            ];
            CourseProductUnitModel::updateById($id, $data);
            $memo = <<<memo
原: 标价={$cpu['original_price']}, 标单价={$cpu['original_single_session_price']}, 售价={$cpu['sale_price']}, 售单价={$cpu['sale_single_session_price']}, 减免价={$cpu['minimum_price']}, 减免单价={$cpu['minimum_single_session_price']}, 成本={$cpu['mini_real_price']}, 成本单价={$cpu['mini_real_single_session_price']};新: 标价={$originalPrice}, 标单价={$originalSingleSessionPrice}, 售价={$salePrice}, 售单价={$saleSingleSessionPrice}, 减免价={$minimumPrice}, 减免单价={$minimumSingleSessionPrice}, 成本={$cpu['mini_real_price']}, 成本单价={$cpu['mini_real_single_session_price']}.
memo;
            $data = [
                'cpu_id' => $id,
                'editor_id' => $adminId,
                'edited_at' => date('Y-m-d H:i:s'),
                'active' => 1,
                'memo' => $memo,
            ];
            $this->record($data);
        } else {
            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['id']);
        }
    }

    function addOneByCpId(int $cpId , array $data){
        $cp = $this->cpService->getOneById($cpId);
        if (!$cp) {
            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['id']);
        }

        CourseProductUnitModel::create($data);
    }

    function record(array $data){
        CourseProductUnitRecordModel::create($data);
    }

    function getListByKeyword($keyword){

    }
}


//    public function list(Request $request)
//    {
//        $whereIn = [];
//        $whereNotIn = [];
//        $couponMin = $request->post('couponMin');
//        $couponMax = $request->post('couponMax');
//        if ($couponMin !== NULL || $couponMax !== NULL) {
//            $couponMin = intval($couponMin);
//            $couponMax = intval($couponMax);
//            if ($couponMin < 0) {
//                $couponMin = 0;
//            }
//            if ($couponMax < 0) {
//                $couponMax = 0;
//            }
//            if ($couponMin && $couponMax && $couponMin > $couponMax) {
//                throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['couponMin > couponMax']);
//            }
//            if ($couponMin) {
//                $couponNum =  $this->courseProductUnitService->getCouponNumByMinAndMax($couponMin, $couponMax);
//                $couponNum = array_column($couponNum, null, 'cpu_id');
//                if ($couponNum) {
//                    $whereIn['course_product_unit.id'] = array_keys($couponNum);
//                } else {
//                    $data = [
//                        'items' => [],
//                        'page' => $page,
//                        'limit' => $limit,
//                        'total' => 0,
//                    ];
//                    return $this->successResponse('success', $data);
//                }
//            } else {
//                $couponNum =  $this->courseProductUnitService->getNotCouponNumByMinAndMax($couponMin, $couponMax);
//                $couponNum = array_column($couponNum, null, 'cpu_id');
//                if ($couponNum) {
//                    $whereNotIn['course_product_unit.id'] = array_keys($couponNum);
//                }
//            }
//        }
//
//        $where = [];
//        $courseType = $request->post('courseType');
//        if ($courseType !== NULL) {
//            $courseType = intval($courseType);
//            $where['course_product_unit.course_type'] = $courseType;
//        }
//        $status = $request->post('status');
//        if ($status !== NULL) {
//            $status = intval($status);
//            $where['course_product_unit.status'] = $status;
//        }
//        $courseTextbookType = $request->post('courseTextbookType');
//        if ($courseTextbookType !== NULL) {
//            $courseTextbookType = intval($courseTextbookType);
//            $where['course_product_unit.course_textbook_type'] = $courseTextbookType;
//        }
//        $keyword = $request->post('keyword');
//        if ($keyword !== NULL) {
//            $whereFun = function ($query) use ($keyword) {
//                $query->where('course_product_unit.name_CN', 'like', '%'.$keyword.'%')
//                ->orWhere(function ($query) use ($keyword) {
//                    $query->where('course_product_unit.name_EN', 'like', '%'.$keyword.'%');
//                })
//                ->orWhere(function ($query) use ($keyword) {
//                    $query->where('course_product_unit.no', 'like', '%'.$keyword.'%');
//                })
//                ->orWhere(function ($query) use ($keyword) {
//                    $query->where('course_product.name_CN', 'like', '%'.$keyword.'%');
//                })
//                ->orWhere(function ($query) use ($keyword) {
//                    $query->where('course_product.name_EN', 'like', '%'.$keyword.'%');
//                })
//                ->orWhere(function ($query) use ($keyword) {
//                    $query->where('course_product.no', 'like', '%'.$keyword.'%');
//                });
//            };
//        } else {
//            $whereFun = null;
//        }
//        $where['course_product_unit.active'] = 1;
//
//        $offset = ($page - 1) * $limit;
//        $total =  $this->courseProductUnitService->getCpuCount($where, $whereFun, $whereIn, $whereNotIn);
//        $list = $this->courseProductUnitService->getCpuList($where, $whereFun, $whereIn, $whereNotIn, $offset, $limit);
//        if ($list) {
//            $ids = [];
//            foreach ($list as $val) {
//                $ids[] = $val['id'];
//            }
//            $couponNum = $this->courseProductUnitService->getCouponNumByCpuIds($ids);
//            $couponNum = array_column($couponNum, null, 'cpu_id');
//            foreach ($list as $key => $val) {
//                $list[$key]['couponCount'] = isset($couponNum[$val['id']]) ? $couponNum[$val['id']]['num'] : 0;
//            }
//        }
//
//        $data = [
//            'items' => $list,
//            'page' => $page,
//            'limit' => $limit,
//            'total' => $total,
//        ];
//        return $this->successResponse('success', $data);
//    }
//
//
//    public function createExternGoodsCpuRel(Request $request)
//    {
//        //参数校验
//        $validate = Validator::make($request->post(), [
//            'appId' => ['bail', 'required', 'numeric', Rule::in(array_keys(self::APP_IDS))],
//            'cpuNo' => 'bail|required|string|max:19',
//            'goodsId' => 'bail|required|string|max:32',
//        ]);
//        if ($validate->fails()) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, $validate->errors()->all());
//        }
//        $cpuNo = $request->post('cpuNo');
//        $cpu = $this->courseProductUnitService->getByWhere(['no' => $cpuNo], ['id', 'status']);
//        if (!empty($cpu[0])) {
//            $cpuId = $cpu[0]['id'];
//            if ($cpu[0]['status'] != self::CPU_ON_SHELVES) {
//                throw new BusinessException([404, '课时包已下架']);
//            }
//            $appId = intval($request->post('appId'));
//            $goodsId = $request->post('goodsId');
//            $externGoodsCpuRel = $this->courseProductUnitService->getExternGoodsCpuRel([
//                'app_id' => $appId,
//                'goods_id' => $goodsId,
//            ]);
//            if ($externGoodsCpuRel) {
//                throw new BusinessException([400, '第三方商品和课时包关联记录已存在']);
//            }
//            $externGoodsCpuRel = $this->courseProductUnitService->getExternGoodsCpuRel([
//                'app_id' => $appId,
//                'cpu_id' => $cpuId,
//            ]);
//            if ($externGoodsCpuRel) {
//                throw new BusinessException([400, '课时包和第三方商品关联记录已存在']);
//            }
//            $this->courseProductUnitService->createExternGoodsCpuRel([
//                'app_id' => $appId,
//                'cpu_id' => $cpuId,
//                'goods_id' => $goodsId,
//            ]);
//            return $this->successResponse('success');
//        } else {
//            throw new BusinessException([404, '课时包不存在']);
//        }
//    }
//
//    public function getCpuByExternGoodsId(Request $request)
//    {
//        //参数校验
//        $validate = Validator::make($request->post(), [
//            'appId' => ['bail', 'required', 'numeric', Rule::in(array_keys(self::APP_IDS))],
//            'goodsId' => 'bail|required|string|max:32',
//        ]);
//        if ($validate->fails()) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, $validate->errors()->all());
//        }
//
//        $appId = intval($request->post('appId'));
//        $goodsId = $request->post('goodsId');
//        $externGoodsCpuRel = $this->courseProductUnitService->getExternGoodsCpuRel([
//            'app_id' => $appId,
//            'goods_id' => $goodsId,
//        ]);
//        if (!$externGoodsCpuRel) {
//            throw new BusinessException([404, '第三方商品和课时包关联记录不存在']);
//        }
//        $cpu = $this->courseProductUnitService->getById($externGoodsCpuRel[0]['cpu_id'], [
//            'id', 'no', 'name_CN', 'status', 'session_times', 'duration', 'capacity',
//            'original_price', 'sale_price', 'minimum_price',
//        ]);
//        if (empty($cpu)) {
//            throw new BusinessException([404, '课时包不存在']);
//        }
//        if ($cpu['status'] != self::CPU_ON_SHELVES) {
//            throw new BusinessException([404, '课时包已下架']);
//        }
//        return $this->successResponse('success', $cpu);
//    }
//

//
//    public function isMajorCourseWithNo(Request $request)
//    {
//        if (!$request->no) {
//            return $this->errorResponse('no is required');
//        }
//        try {
//            $r = $this->courseProductUnitService->isMajorCourseWithNo($request->no);
//        } catch (\Exception $e) {
//            return $this->errorResponse($e->getMessage());
//        }
//        return $this->successResponse('success', $r);
//    }
//}
