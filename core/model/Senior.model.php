<?php
/**
 * 高级封装Model
 * @author Ltre
 */
class SeniorModel extends DIModel {

    //高级COUNT方法
    public function seniorCount($args){
        @$data = $this->seniorSelect(array(
            'from' => $args['from'],
            'where' => $args['where'] ?: array(),
            'listable' => false, //是否取列表
            'pageable' => true, //是否分页
        ));
        return $data['count'];
    }
    
    
    /**
     * 高级SELECT查询方法
     * <pre>
     * @param $args 复合参数，包含：
     *      select => SELECT子句，如"user.a, abc, log.id"
     *      form => FROM子句，如"v_user u LEFT JOIN v_fanhe_upload fh ON fh.uid = u.user_id"
     *      where => WHERE条件多维数组，如
     *              array(
     *                  'OR',
     *                  array('u.user_id', '=', '50014545'),
     *                  array(
     *                      'AND',
     *                      array(
     *                          'OR',
     *                          ['u.user_id', 'like', '%1%'],
     *                          ['fh.status', '!=', 2],
     *                      ),
     *                      array(
     *                          'NOT',
     *                          ['u.udb', 'IN', ['weiwei05658', 'luhanbin', 'mmcxz']],
     *                      ),
     *                      array(
     *                          'NOT',
     *                          array(
     *                              'AND',
     *                              ['u.sex', '=', 1],
     *                              ['u.created', '>', 1419943807],
     *                          )
     *                      ),
     *                  ),
     *              ),
     *              以上为复杂的逻辑条件，也支持简单的条件，如: where => ['uidEqual', 'u.user_id', '=', '50014545']
     *      orderBy => ORDER BY子句，如"u.user_id DESC"
     *      groupBy => GROUP BY子句，如"u.role"
     *      limitBy => 分页所需参数，数组元素顺序如下：页码、每页结果集个数上限、可见的页码范围长度。
     *                 如：[5, 10, 10]表示取第5页，显示最多10条数据，假设总页数足够，则可见的页码为[5,6,7,8,9,10,11,12,13,14]
     *      listable => 值true|false，是否获取列表，默认为是
     *      pageable => 值true|false, 是否获取分页，默认为否
     * </pre>
     */
    public function seniorSelect($args){
        if (empty($args['from'])) throw new Exception('args.from is empty!');
    
        @$select = $args['select'] ?: '*';
        @$from = $args['from'] ?: '';
        @$where = $args['where'] ?: array();
        @$orderBy = $args['orderBy'] ?: '';
        @$groupBy = $args['groupBy'] ?: '';
        @$limitBy = $args['limitBy'] ?: array(1, 10, 10);
        @$listable = (! isset($args['listable']) || $args['listable']) ? true : false;
        @$pageable = $args['pageable'] ? true : false;
    
        $page = max(1, (int)$limitBy[0]);
        $limit = $limitBy[1];
        $scope = $limitBy[2];
        $offset = ($page - 1) * $limit;
        $limitSql = $pageable ? "LIMIT {$offset}, {$limit}" : "";
        $orderSql = $orderBy ? "ORDER BY {$orderBy}" : "";
        $groupSql = $groupBy ? "GROUP BY {$groupBy}" : "";
    
        $ret = $this->_seniorWhere($where);
        $whereSql = $ret['whereSql'];
        $tailSql = "FROM {$from} {$whereSql}";
    
        //按需取列表
        if ($listable) {
            $sql = "SELECT {$select} {$tailSql} {$groupSql} {$orderSql} {$limitSql}";
            $list = $this->query($sql, $ret['conds']) ?: array();
        } else {
            $sql = '';
            $list = array();
        }
    
        //按需分页
        if ($pageable) {
            $totalSql = "SELECT COUNT(1) `cnt` {$tailSql}";
            $total = $this->query($totalSql, $ret['conds']) ?: array();
            $count = $total[0]['cnt']?:0;
            $pages = $this->pager($page, $limit, $scope, $count);
        } else {
            $totalSql = '';
            $count = 0;
            $pages = array();
        }
    
        $debug = array(
            'sql' => $sql,
            'totalSql' => $totalSql,
            'conds' => $ret['conds'],
        );
        return array('list' => $list, 'pages' => $pages, 'count' => $count, 'debug' => $debug);
    }
    
    
    protected function _seniorWhere(array $where, $layer = 1){
        if ($layer == 1 && count($where) == 0) { //第一层就是空数组，表示无条件查询
            return array('whereSql' => '1=1', 'conds' => array());
        }
    
        $whereJson = json_encode($where, JSON_UNESCAPED_UNICODE);
        $allIsArray = true;
        $firstIsLogicAndAllOtherIsArray = true;
        foreach ($where as $k => $v) {
            if (! is_array($v)) $allIsArray = false;
            if (! ($k == 0 && in_array($v, ['AND', 'OR', 'NOT']) || $k != 0 && is_array($v))) {
                $firstIsLogicAndAllOtherIsArray = false;
            }
        }
        if ($allIsArray) {  //可能缺少处于第一元素位置的逻辑符[AND|OR|NOT]
            throw new Exception("In layer {$layer}, all elements of \$where are array, maybe you miss the first element with logic value such as 'AND','OR','NOT'! Current \$where is {$whereJson}.");
        }
    
        $whereSql = "";
        $conds = array();
        if ($layer == 1) {
            $whereSql = "WHERE";
        }
    
        $inComplexLayer = $firstIsLogicAndAllOtherIsArray;
        if ($inComplexLayer) { //处于具有复杂逻辑的层，将会递归到下层
            $logic = $where[0];
            $whereSql .= $logic == 'AND' ? ' 1=1' : ($logic == 'OR' ? ' 1<>1' : '');
            if (count($where) >= 2) {//解析该层时，必须保证除了逻辑符号外，还有至少一个条件
                foreach ($where as $k => $v) {
                    if ($k > 0) {
                        if (! is_array($v)) {//被递归的子条件，必须是数组
                            throw new Exception("Build whereSql failure in complex logic layer, \$where[{$k}] is not an array! Current \$where is {$whereJson}.");
                        }
                        if (count($v) > 0) { //被递归的子条件，必须是非空数组
                            $ret = $this->_seniorWhere($v, $layer + 1);
                            $whereSql .= " {$logic} ({$ret['whereSql']})";
                            $conds = array_merge($conds, $ret['conds']);
                        }
                    }
                }
            }
            return array('whereSql' => $whereSql, 'conds' => $conds);
        } else { //处于简单逻辑的层，属于叶子节点，不再递归
            if (count($where) != 3 || is_array($where[0]) || is_array($where[1])) { //简单条件必须是含有三个元素的数组，且前两个元素不能为数组，第三个要看运算符是什么来决定
                throw new Exception('Build whereSql failure in simple condition layer, $where must be an array with 3 elements, and the first 2 elements of $where can not be array! Current $where is '.$whereJson.'.');
            }
            list($field, $op, $value) = $where;
            $key = str_replace('.', '', $field).'_'.intval(microtime(1)*1000);
            if ($op == 'IN' || $op == 'NOT IN') {
                if (! is_array($value) || count($value) == 0) { //含有IN运算符的条件值必须是含有至少一个元素的数组
                    throw new Exception("Build whereSql failure in [IN|NOT IN] condition layer, \$where[2] must be an array, and must contain at least one element value! Current \$where is {$whereJson}.");
                }
                $whereSql .= " {$field} {$op} (";
                $keys = array();
                foreach ($value as $vk => $vv) {
                    array_push($keys, ":{$key}_{$vk}");
                    $conds["{$key}_{$vk}"] = $vv;
                }
                $whereSql .= implode(',', $keys) . ')';
            } else {
                $whereSql .= " {$field} {$op} :{$key}";
                $conds[$key] = $value;
            }
            return array('whereSql' => $whereSql, 'conds' => $conds);
        }
    }
    
}