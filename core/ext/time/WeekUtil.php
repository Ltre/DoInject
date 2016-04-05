<?php
class WeekUtil {
    
    /**
     * 获取本周时间信息
     * @param mixed 输出格式，默认false输出时间戳；可以设置“Y-m-d”之类参数来控制格式
     * @param int $firstDay 设置为1，则表示周一为每周开始
     */
    public function getCurrentWeek($format = false, $firstDay = 1){
        $today = date('Y-m-d');
        $w = date('w', strtotime($today));
        $weekStart = strtotime("$today -".($w ? $w - $firstDay : 6).' days');
        $weekEnd = $weekStart + 6 * 86400;
        if (!! $format) {
            $weekStart = date($format, $weekStart);
            $weekEnd = date($format, $weekEnd);
        }
        return array('weekIndex' => $w, 'weekStart' => $weekStart, 'weekEnd' => $weekEnd);
    }
    
}