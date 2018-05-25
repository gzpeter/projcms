<?php

namespace App\Http\Controllers;

use App\MedicalTag;

class CeshiController extends Controller
{
    public function ceshi()
    {
        $lists = MedicalTag::select(['id','parent_id','name','is_leaf'])->get();
        $lists = $this->getTree($lists);
        return  $lists;
    }

    public function getTree($data, $pId = 0)
    {
        $tree = [];
        foreach ($data as $k => $v) {
            if ($v['parent_id'] == $pId) {
                //父亲找到儿子
	            $v['children'] = $this->getTree($data, $v['id']);
                $tree[] = $v;
                unset($data[$k]);
            }
        }
        return $tree;
    }
}