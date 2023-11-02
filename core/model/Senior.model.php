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
     *      from => FROM子句，如"v_user u LEFT JOIN v_fanhe_upload fh ON fh.uid = u.user_id"
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
     *              以上为复杂的逻辑条件，也支持简单的条件，如: where => ['u.user_id', '=', '50014545']
     *      orderBy => ORDER BY子句，如"u.user_id DESC"
     *      groupBy => GROUP BY子句，如"u.role"
     *      limitBy => 分页所需参数，数组元素顺序如下：页码、每页结果集个数上限、可见的页码范围长度。
     *                 如：[5, 10, 10]表示取第5页，显示最多10条数据，假设总页数足够，则可见的页码为[5,6,7,8,9,10,11,12,13,14]
     *      listable => 值true|false，是否获取列表，默认为是
     *      pageable => 值true|false, 是否获取分页，默认为否
     * </pre>
     */
    public function seniorSelect($args){
        if (empty($args['from']) && empty($this->table_name)) {
            throw new Exception('The [args.from] is empty!');
        }
        if (! (empty($args['limitBy']) || is_array($args['limitBy']) && 3 == count($args['limitBy']) && is_numeric(join('', $args['limitBy'])))) {
            throw new Exception('The [args.limitBy] is only accept empty value or an array contains three numbers!');
        }

        @$select = $args['select'] ?: '*';
        @$from = $args['from'] ?: " {$this->table_name} ";
        @$where = $args['where'] ?: array();
        @$orderBy = $args['orderBy'] ?: '';
        @$groupBy = $args['groupBy'] ?: '';
        @$listable = (! isset($args['listable']) || $args['listable']) ? true : false;
        @$pageable = $args['pageable'] ? true : false;

        //如需分页，则limit必有，进而limitBy必有
        if ($pageable) {
            @$limitBy = $args['limitBy'] ?: [1, 10, 10];
        } elseif (@$args['limitBy']) {
            @$limitBy = $args['limitBy'];
        }
        if ($limitBy) {
            $page = max(1, (int)$limitBy[0]);
            $limit = $limitBy[1];
            $scope = $limitBy[2];
            $offset = ($page - 1) * $limit;
            $limitSql = "LIMIT {$offset}, {$limit}";
        } else {
            $limitSql = "";
        }

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
            if ($groupBy) {
                $totalSql = "SELECT COUNT(1) `cnt` FROM ( SELECT 1 {$tailSql} {$groupSql} ) AS `tmp`";
            } else {
                $totalSql = "SELECT COUNT(1) `cnt` {$tailSql}";
            }
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
            return array('whereSql' => 'WHERE 1=1', 'conds' => array());
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
            $key = '_'.preg_replace('/\W/', '', $field).'_'.intval(microtime(1)*1000).'_r'.rand(100, 999);
            $op = strtoupper(trim($op));
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
    
    
    /**
     * 循环方式，将别表的数据列合并到左列表（节流查询）
     * 
     *      实际不执行联表，而是一次性IN查询
     *
     * @param array $list 左列表
     * @param string $select 右表select子句，实际会在字句的左边，自动追加k2k参数中的左侧键名
     * @param string $from 右表的from子句
     * @param string $k2k 左表待展开查询的数据ID，该ID与右表的查询键关联。例如"user_id -> id"，可视为左表的user_id，对应右表(用户)的主键id
     * @return array 左右合并后的列表
     */
    public function loopMergeTable(array $list, $select, $from, $k2k = 'user_id -> id'){
        if (empty($list)) return [];
        if (! preg_match('/([\w_]+)\s*->\s*(\w+)/', $k2k, $matches)) return [];

        $ids = [];
        list (, $leftKey, $rightKey) = $matches;
        foreach ($list as $v) {
            $id = $v[$leftKey];
            $ids[] = $id;
        }
        $ids = array_unique($ids);

        //取得旁表数据MAP
        $map = [];
        $pack = $this->seniorSelect([
            'select' => "{$rightKey}, {$select}",
            'from' => $from,
            'where' => [$rightKey, 'IN', $ids],
            'listable' => true,
            'pageable' => false,
        ]);
        foreach ($pack['list'] as $v) $map[$v[$rightKey]] = $v;

        //合并数据
        foreach ($list as $k => $v) {
            $rightData = $map[$v[$leftKey]];
            unset($rightData[$rightKey]);
            $list[$k] += $rightData;
        }

        return $list;
    }
    

    /**
     * 计算任意表达式
     *
     * @param string $field 指定字段，形式如"表名.字段"或"字段"，如 user.score 或 score
     * @param string $formula 含有问号的表达式，如 "SUM(?)"
     * @param string $where
     * @return mixed 计算结果
     */
    public function calc($field, $formula, $where){
        $e = explode('.', $field);
        $from = count($e)==2 ? $e[0] : $this->table_name;
        $field = count($e)==2 ? $e[1] : $field;
        $formula = str_replace('?', "`{$field}`", $formula);
        $pack = $this->seniorSelect([
            'select' => "{$formula} rs",
            'from' => $from,
            'where' => $where,
            'pageable' => false,
        ]);
        return $pack['list'][0]['rs'] ?: 0;
    }


    /* 未确定是否加入此代码
    //当不指定具体的select子句时，将使用驼峰化命名
    public function makeHumpFileds(){
        $dRs = $this->query("DESC `{$this->table_name}`") ?: [];
        $fs = [];
        foreach ($dRs as $v) {
            //@todo 有空根据$v['Type']，将输出数据类型指定好，不用搞的全部是字符串
            $fs[] = $v['Field'] . ' AS ' . preg_replace_callback('/_([a-zA-Z])/', function($matches){
                return strtoupper($matches[1]);
            }, $v['Field']);
        }
        return join(', ', $fs);
    }
    */


    /* 未确定是否加入此代码
    public function proFind($conditions=array(),$field='', $order=null) {
        if ($field == '' || trim(((string)$field).'') == '*') {
            $field = $this->makeHumpFileds();
        }
        return parent::find($conditions, $field, $order);
    }
    */
    

    /* 未确定是否加入此代码
    public function proSelect( $conditions=array(), $field='', $order=null, $limit=null ){
        if ($field == '' || trim(((string)$field).'') == '*') {
            $field = $this->makeHumpFileds();
        }
        return parent::select($conditions, $field, $order, $limit);
    }
    */

}
