<?php

namespace App\Services\V2;

use App\Services\V1\CourseProductUnitService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\V1\CourseProductModel;
use App\Models\V1\CourseProductRecordModel;
use App\Models\V1\CpPicIntroductionModel;

class CpService implements \App\Interfaces\V2\Cp
{
    const STATUS_ON_SHELVES = 1;
    const STATUS_OFF_SHELVES = 2;
    const CPU_STATUS = [
        self::CPU_ON_SHELVES => '上架',
        self::CPU_OFF_SHELVES => '下架',
    ];

    //CP适用学员类型
    const ADAPTIVE_TYPE_ALL = 0;
    const ADAPTIVE_TYPE_EXCLUSIVE = 1;
    const ADAPTIVE_TYPE = [
        self::ADAPTIVE_TYPE_ALL => '全部学员',
        self::ADAPTIVE_TYPE_EXCLUSIVE => '专属学员',
    ];

    private $cpuService = null;

    private $appId = null;
    private $token = null;

    function __construct(CpuService $CpuService)
    {
        $this->cpuService = $CpuService;
    }

    //获取一条记录
    function getOneById(int $id,int $isIncludeCpu = 0)
    {
        $cpu = CourseProductModel::getById($id);
        if (!$cpu) {
            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['id']);
        }

        if (!$isIncludeCpu) {
            return $cpu;
        } else {
            $cpu = $this->cpuService->getListByCpuId();
            $cpu['cpus'] = $cpu;
        }
    }

    //获取CPU列表整个列表 - 可设置筛选条件
    function getListByCondition(array $condition,array $pageInfo = null){
                //参数校验
//        $validate = Validator::make($request->post(), [
//            'courseType' => 'bail|numeric',
//            'status' => ['bail', 'numeric', Rule::in(array_keys(self::CP_STATUS))],
//            'courseTextbookType' => 'bail|numeric',
//            'keyword' => 'bail|string',
//            'page' => 'bail|numeric',
//            'limit' => 'bail|numeric',
//        ]);
//        if ($validate->fails()) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, $validate->errors()->all());
//        }

        $where = [];

        if(arrKeyExist($condition,'courseType')){
            $where[] = " course_type = ".$condition['courseType'];
        }

        if(arrKeyExist($condition,'status')){
            $where[] = " status = ".$condition['status'];
        }

        if(arrKeyExist($condition,'courseTextbookType')){
            $where[] = " course_textbook_type = ".$condition['courseTextbookType'];
        }

        if(arrKeyExist($condition,'keyword')){
            $keyword = $condition['keyword'];

            $whereLike = " ( name_CN like %$keyword% or name_EN like %$keyword% or no like %$keyword% ) ";



            $whereLike = " ( name_CN like %$keyword% or name_EN like %$keyword% or no like %$keyword% ) ";

            $this->cpuService->getListByKeyword($keyword);
        }

//            ->orWhere(function ($query) use ($keyword) {
//                $query->whereIn('id', function ($query) use($keyword){
//                    $query->select('cp_id')->from('course_product_unit')->where('active', 1)->where(
//                        function ($query) use ($keyword) {
//                            $query->where('name_CN', 'like', '%'.$keyword.'%')
//                            ->orWhere(function ($query) use ($keyword) {
//                                $query->where('name_EN', 'like', '%'.$keyword.'%');
//                            })
//                            ->orWhere(function ($query) use ($keyword) {
//                                $query->where('no', 'like', '%'.$keyword.'%');
//                            });
//                        }
//                    );
//                });
        $where['active'] = 1;

        $page = intval($request->post('page'));
        if ($page < 1) {
            $page = 1;
        }
        $limit = intval($request->post('limit'));
        if ($limit < 10 || $limit > 100) {
            $limit = 10;
        }
        $offset = ($page - 1) * $limit;
        $total =  $this->courseProductService->getCount($where, $whereFun);
        $list = $this->courseProductService->getList($where, $whereFun, [], $offset, $limit);
        $data = [
            'items' => $list,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
        ];
        return $this->successResponse('success', $data);

    }

    //添加一条记录
    function addOne(array $data){
    //参数校验
//        $validate = Validator::make($request->post(), [
//            'nameCN' => 'bail|required|string',
//            'nameEN' => 'bail|required|string',
//            'courseType' => 'bail|required|numeric',
//            'courseTextbookType' => 'bail|required|numeric',
//            'adaptiveType' => 'bail|required|numeric',
//            'adminId' => 'bail|required|numeric',
//        ]);
//        if ($validate->fails()) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, $validate->errors()->all());
//        }
        if ($this->isExistRepeatByName("name_CN",$data['nameCN'])){
            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['套餐英文名称已存在']);
        }

        if ($this->isExistRepeatByName("name_EN",$data['nameEN'])){
            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['套餐中文名称已存在']);
        }


        $user = [];
        if ($data['adaptiveType'] == self::ADAPTIVE_TYPE_EXCLUSIVE) {
            $exclusiveMobile = $data['exclusiveMobile'];
            if (!$exclusiveMobile) {
                throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['exclusiveMobile']);
            }
            $ucenter = new \php_base\api\ucenter\user\v2\UcenterService($this->appId, $this->token);
            $user = $ucenter->getParentByMobile(['mobile' => $exclusiveMobile]);
            if (!$user) {
                throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['UcenterService getParentByMobile error.']);
            } else {
                $user = json_decode($user, true);
                if ($user['code'] != 200 || !is_array($user['data']) || empty($user['data'])) {
                    throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['UcenterService getParentByMobile callback error.']);
                } else {
                    $user = $user['data'];
                }
            }
        }


        $adminId = $data['adminId'];

        $time = date('Y-m-d H:i:s');
        $no = generateNo();
        $no = 'cp' . $no;
        $data = [
            'name_CN' => $data['nameCN'],
            'name_EN' => $data['nameEN'],
            'no' => $no,
            'course_type' =>$data['courseType'] ,
            'course_level_from' => 0,
            'course_level_to' => 9999,
            'type' => 0,
            'status' => self::STATUS_OFF_SHELVES,
            'created_at' => $time,
            'updated_at' => $time,
            'creator_id' => $adminId,
            'active' => 1,
            'priority' => 0,
            'adaptive_type' => $adaptiveType,
            'course_textbook_type' => $courseTextbookType,
        ];
        if ($user) {
            $data['exclusive_id'] = $user['id'];
        }
        $id = CourseProductModel::create($data);

        if (is_array($cpuCreate)) {
            $courseProductUnitService = new CourseProductUnitService();
            foreach ($cpuCreate as $cpu) {
                $no = generateNo();
                $no = 'cpu' . $no;
                $data = [
                    'cp_id' => $id,
                    'no' => $no,
                    'type' => $cpu['type'],
                    'status' => $cpu['status'],
                    'original_price' => 0,
                    'actual_price' => 0,
                    'original_single_session_price' => 0,
                    'actual_single_session_price' => 0,
                    'session_times' => intval($cpu['session_times']),
                    'capacity' => intval($cpu['capacity']),
                    'course_type' => $courseType,
                    'course_level_from' => 0,
                    'course_level_to' => 9999,
                    'created_at' => $time,
                    'updated_at' => $time,
                    'active' => 1,
                    'creator_id' => $adminId,
                    'duration' => intval($cpu['duration']),
                    'mini_real_price' => 0,
                    'mini_real_single_session_price' => 0,
                    'minimum_price' => 0,
                    'minimum_single_session_price' => 0,
                    'name_CN' => $cpu['name_CN'],
                    'sale_price' => 0,
                    'sale_single_session_price' => 0,
                    'course_textbook_type' => $courseTextbookType,
                ];
                $this->cpuService->addOneByCpuId($id);
            }
        }

    }

    function isExistRepeatByName($fieldName,$compareName){

    }

    function editOneById($id,$data){
        //        //参数校验
//        $validate = Validator::make($request->post(), [
//            'id' => 'bail|required|numeric',
//            'nameCN' => 'bail|required|string',
//            'nameEN' => 'bail|required|string',
//            'courseType' => 'bail|required|numeric',
//            'courseTextbookType' => 'bail|required|numeric',
//            'adaptiveType' => 'bail|required|numeric',
//            'adminId' => 'bail|required|numeric',
//        ]);
//        if ($validate->fails()) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, $validate->errors()->all());
//        }
//
//        $id = intval($request->post('id'));
//        if (empty($id)) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['id']);
//        }
//        $cp = $this->courseProductService->getById($id);
//        if (!$cp) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['id']);
//        }
//
//        $nameCN = $request->post('nameCN');
//        $res = $this->courseProductService->getList([['id', '!=', $id], 'name_CN' => $nameCN], null, [], 0, 1);
//        if ($res) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['套餐中文名称已存在']);
//        }
//
//        $user = [];
//        $adaptiveType = $request->post('adaptiveType');
//        if ($adaptiveType == self::CP_ADAPTIVE_TYPE_EXCLUSIVE) {
//            $exclusiveMobile = $request->post('exclusiveMobile');
//            if (!$exclusiveMobile) {
//                throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['exclusiveMobile']);
//            }
//            $ucenter = new \php_base\api\ucenter\user\v2\UcenterService(self::$appId, self::$token);
//            $user = null;
//            $i = 1;
//            //接口最多重试两次
//            while (!$user && $i <= 2) {
//                $user = $ucenter->getParentByMobile(['mobile' => $exclusiveMobile]);
//                $i++;
//            }
//            if (!$user) {
//                throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['UcenterService getParentByMobile error.']);
//            } else {
//                $user = json_decode($user, true);
//                if ($user['code'] != 200 || !is_array($user['data'])) {
//                    throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['UcenterService getParentByMobile callback error.']);
//                } else {
//                    $user = $user['data'];
//                }
//            }
//        }
//
//        $nameEN = $request->post('nameEN');
//        $courseType = $request->post('courseType');
//        $courseTextbookType = $request->post('courseTextbookType');
//        $cpuCreate = $request->post('cpuCreate');
//        $cpuEdit = $request->post('cpuEdit');
//        $adminId = intval($request->post('adminId'));
//        $time = date('Y-m-d H:i:s');
//
//        $data = [
//            'name_CN' => $nameCN,
//            'name_EN' => $nameEN,
//            'course_type' => $courseType,
//            'updated_at' => $time,
//            'adaptive_type' => $adaptiveType,
//            'course_textbook_type' => $courseTextbookType,
//        ];
//        if ($user) {
//            $data['exclusive_id'] = $user['id'];
//        }
//        $this->courseProductService->updateById($id, $data);
//        $this->courseProductService->createCourseProductRecord([
//            'cp_id' => $id,
//            'editor_id' => $adminId,
//            'edited_at' => $time,
//            'active' => 1,
//            'memo' => '修改商品',
//        ]);
//
//        $courseProductUnitService = new CourseProductUnitService();
//        if (is_array($cpuCreate)) {
//            foreach ($cpuCreate as $cpu) {
//                if (isset($cpu['id'])) {
//                    continue;
//                }
//
//                $no = generateNo();
//                $no = 'cpu' . $no;
//                $data = [
//                    'cp_id' => $id,
//                    'no' => $no,
//                    'type' => $cpu['type'],
//                    'status' => $cpu['status'],
//                    'original_price' => 0,
//                    'actual_price' => 0,
//                    'original_single_session_price' => 0,
//                    'actual_single_session_price' => 0,
//                    'session_times' => $cpu['session_times'],
//                    'capacity' => $cpu['capacity'],
//                    'course_type' => $courseType,
//                    'course_level_from' => 0,
//                    'course_level_to' => 9999,
//                    'created_at' => $time,
//                    'updated_at' => $time,
//                    'active' => 1,
//                    'creator_id' => $adminId,
//                    'duration' => $cpu['duration'],
//                    'mini_real_price' => 0,
//                    'mini_real_single_session_price' => 0,
//                    'minimum_price' => 0,
//                    'minimum_single_session_price' => 0,
//                    'name_CN' => $cpu['name_CN'],
//                    'sale_price' => 0,
//                    'sale_single_session_price' => 0,
//                    'course_textbook_type' => $courseTextbookType,
//                ];
//                $courseProductUnitService->create($data);
//            }
//        }
//
//        if (is_array($cpuEdit)) {
//            foreach ($cpuEdit as $cpu) {
//                if (!isset($cpu['id'])) {
//                    continue;
//                }
//                $dbCpu = $courseProductUnitService->getById($cpu['id']);
//                if (!$dbCpu) {
//                    continue;
//                }
//                if ($cpu['session_times'] < 1) {
//                    continue;
//                }
//                $data = [
//                    'type' => $cpu['type'],
//                    'status' => $cpu['status'],
//                    'session_times' => $cpu['session_times'],
//                    'capacity' => $cpu['capacity'],
//                    'course_type' => $courseType,
//                    'updated_at' => $time,
//                    'duration' => $cpu['duration'],
//                    'name_CN' => $cpu['name_CN'],
//                    'course_textbook_type' => $courseTextbookType,
//                ];
//                if ($cpu['session_times'] != $dbCpu['session_times']) {
//                    if ($dbCpu['original_price'] || $dbCpu['sale_price'] || $dbCpu['minimum_price'] || $dbCpu['mini_real_price']) {
//                        $data['original_single_session_price'] = intval($dbCpu['original_price'] / $cpu['session_times']);
//                        $data['sale_single_session_price'] = intval($dbCpu['sale_price'] / $cpu['session_times']);
//                        $data['minimum_single_session_price'] = intval($dbCpu['minimum_price'] / $cpu['session_times']);
//                        $data['mini_real_single_session_price'] = intval($dbCpu['mini_real_price'] / $cpu['session_times']);
//
//                        $memo = <<<memo
//原: 标价={$dbCpu['original_price']}, 标单价={$dbCpu['original_single_session_price']}, 售价={$dbCpu['sale_price']}, 售单价={$dbCpu['sale_single_session_price']}, 减免价={$dbCpu['minimum_price']}, 减免单价={$dbCpu['minimum_single_session_price']}, 成本={$dbCpu['mini_real_price']}, 成本单价={$dbCpu['mini_real_single_session_price']};新: 标价={$dbCpu['original_price']}, 标单价={$data['original_single_session_price']}, 售价={$dbCpu['sale_price']}, 售单价={$data['sale_single_session_price']}, 减免价={$dbCpu['minimum_price']}, 减免单价={$data['minimum_single_session_price']}, 成本={$dbCpu['mini_real_price']}, 成本单价={$data['mini_real_single_session_price']}.
//memo;
//                        $this->courseProductUnitService->createCourseProductUnitRecord([
//                            'cpu_id' => $cpu['id'],
//                            'editor_id' => $adminId,
//                            'edited_at' => $time,
//                            'active' => 1,
//                            'memo' => $memo,
//                        ]);
//                    }
//                }
//
//                $courseProductUnitService->updateById($cpu['id'], $data);
//                $courseProductUnitService->createCourseProductUnitRecord([
//                    'cpu_id' => $cpu['id'],
//                    'editor_id' => $adminId,
//                    'edited_at' => $time,
//                    'active' => 1,
//                    'memo' => "修改CPU, capacity:{$dbCpu['capacity']}->{$cpu['capacity']}, session_times:{$dbCpu['session_times']}->{$cpu['session_times']}",
//                ]);
//            }
//
//            $cpuDelete = $request->post('cpuDelete');
//            if (is_array($cpuDelete)) {
//                foreach ($cpuDelete as $cpuId) {
//                    $cpu = $courseProductUnitService->getById($cpuId);
//                    if (!$cpu) {
//                        continue;
//                    }
//                    $courseProductUnitService->updateById($cpuId, ['active' => 0, 'updated_at' => $time]);
//                    $courseProductUnitService->createCourseProductUnitRecord([
//                        'cpu_id' => $cpuId,
//                        'editor_id' => $adminId,
//                        'edited_at' => $time,
//                        'active' => 1,
//                        'memo' => '删除CPU',
//                    ]);
//                }
//            }
//        }
//        return $this->successResponse('success');
    }

    //编辑上下架状态
    function editOneStatusById($id,$status,$adminId){
        //参数校验
//        $validate = Validator::make($request->post(), [
//            'id' => 'bail|required|numeric',
//            'status' => ['bail', 'required', 'numeric', Rule::in(array_keys(self::CP_STATUS))],
//            'adminId' => 'bail|required|numeric',
//        ]);
//        if ($validate->fails()) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, $validate->errors()->all());
//        }
//        $id = intval($request->post('id'));
        $cp = $this->getOneById($id);
        if ($cp) {
            $status = intval($status);
            if ($cp['status'] == $status) {
                throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['status']);
            }
            $data['status'] = $status;
            CourseProductModel::updateById($id, $data);
            $data = [
                'cp_id' => $id,
                'editor_id' => $adminId,
                'edited_at' => date('Y-m-d H:i:s'),
                'active' => 1,
                'memo' => '设置商品的状态：' . self::CP_STATUS[$status],
            ];
            $this->record($data);
        } else {
            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['id']);
        }
    }
    //编辑 记录详细数据
    function editOneDetailById($id,$data){
//        //参数校验
//        $validate = Validator::make($request->post(), [
//            'id' => 'bail|required|numeric',
//            'nameCN' => 'bail|required|string',
//            'nameEN' => 'bail|required|string',
//            'thumbnail' => 'bail|required|string',
//            'description' => 'bail|string',
//            'notice' => 'bail|string',
//        ]);
//        if ($validate->fails()) {
//            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, $validate->errors()->all());
//        }
        $id = intval($id);
        $cp = $this->getById($id);
        if ($cp) {
            $data = [
                'name_CN' => $data['nameCN'],
                'name_EN' =>  $data['nameEN'],
                'thumbnail' => $thumbnail,
                'description' => $description,
                'notice' => $notice,
            ];
            CourseProductModel::updateById($id, $data);
            $picDeleteList = $request->post('picDeleteList');
            if (is_array($picDeleteList)) {
                foreach ($picDeleteList as $id) {
                    $res = CpPicIntroductionModel::getById($id);
                    if ($res) {
                        CpPicIntroductionModel::deleteById($id);
                    }
                }
            }

            $time = date('Y-m-d H:i:s');
            $picNewList = $request->post('picNewList');
            if (is_array($picNewList)) {
                foreach ($picNewList as $val) {
                    $data = [
                        'cp_id' => $id,
                        'order' => $val['order'],
                        'key' => $val['key'],
                        'hash' => $val['hash'],
                        'created_at' => $time,
                        'active' => 1
                    ];
                    CpPicIntroductionModel::create($data);
                }
            }

            return $this->successResponse('success');
        } else {
            throw new BusinessException(ILLEGAL_PARAMETERS_ERROR, ['id']);
        }
    }
    //记录变更状态
    function record(array $data){
        CourseProductRecordModel::create($data);
    }

}

//    public function update(Request $request)
//    {

//    }
//
//
