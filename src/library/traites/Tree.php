<?php

namespace cigoadmin\library\traites;

/**
 * Trait Tree
 * @package cigoadmin\library\traites
 * @summary 树形结构处理类
 */
trait Tree
{
    protected function convertToTree(&$srcDataList = array(), &$treeList = array(), $pid = 0, $pidKey = 'pid', $checkGroup = true)
    {
        $groupName = '';
        $groupItemIndex = -1;
        foreach ($srcDataList as $key => $item) {
            //判断当前层级
            if (isset($item[$pidKey]) && $item[$pidKey] == $pid) {
                //处理分组
                if ($checkGroup && isset($item['group']) && !empty($item['group'])) {
                    if ($groupName != $item['group']) {
                        $groupItemIndex = count($treeList);
                        $treeList[] = array(
                            'group_flag' => true,
                            'title' => $item['group'],
                            'subItemNum' => 1,
                            'subItemEnableNum' => $item['status'] == 1 ? 1 : 0,
                        );
                        $groupName = $item['group'];
                    } else {
                        $treeList[$groupItemIndex]['subItemNum']++;
                        $treeList[$groupItemIndex]['subItemEnableNum'] += $item['status'] == 1 ? 1 : 0;
                    }
                }
                // 处理当前项
                $subList = array();
                $this->convertToTree($srcDataList, $subList, $item['id'], $pidKey, $checkGroup);
                if (!empty($subList)) {
                    $item['sub_list'] = $subList;
                }

                $treeList[] = $item;
                unset($srcDataList[$key]);
            }
        }
    }
}

