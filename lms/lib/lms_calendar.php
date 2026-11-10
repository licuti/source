<?php
/**
 * LMS Calendar — helper dùng chung cho lưới lịch tuần (admin / teacher / student).
 *
 * PHP 7.3 compatible.
 */

if (!defined('LMS_CAL_HOUR_H')) {
    // Chiều cao 1 hàng giờ (px). Dùng chung cho CSS var --lms-cal-hour-h và phép tính offset.
    define('LMS_CAL_HOUR_H', 72);
}

if (!function_exists('lms_cal_variant')) {
    /**
     * Trạng thái buổi học → variant màu của thẻ lịch.
     *
     * @param string $status
     * @param bool   $missingHw buổi đã kết thúc nhưng thiếu BTVN
     * @return string upcoming|missing|finished|cancelled
     */
    function lms_cal_variant($status, $missingHw = false)
    {
        $status = strtolower(trim((string) $status));
        if ($status === 'cancelled' || $status === 'absent_teacher' || $status === 'absent_student') {
            return 'cancelled';
        }
        if ($status === 'upcoming') {
            return 'upcoming';
        }
        if ($missingHw) {
            return 'missing';
        }
        return 'finished';
    }
}

if (!function_exists('lms_cal_variant_label')) {
    /**
     * Nhãn tiếng Việt của variant (dùng cho badge modal + chú thích).
     *
     * @param string $variant
     * @return string
     */
    function lms_cal_variant_label($variant)
    {
        $map = array(
            'upcoming'  => 'Sắp tới',
            'missing'   => 'Thiếu BTVN',
            'finished'  => 'Đã hoàn thành',
            'cancelled' => 'Đã nghỉ',
            'free'      => 'Lịch trống',
        );
        $variant = strtolower(trim((string) $variant));
        return isset($map[$variant]) ? $map[$variant] : ucfirst($variant);
    }
}

if (!function_exists('lms_cal_missing_hw')) {
    /**
     * Buổi đã kết thúc và chưa có BTVN.
     *
     * @param array $session
     * @param bool  $requireHwContent kiểm tra cả nội dung bài tập đã giao (admin)
     * @return bool
     */
    function lms_cal_missing_hw(array $session, $requireHwContent = false)
    {
        if ((string) ($session['status'] ?? '') !== 'finished') {
            return false;
        }
        if (empty($session['summary_submitted_at'])) {
            return true;
        }
        return $requireHwContent && trim((string) ($session['hw_content'] ?? '')) === '';
    }
}

if (!function_exists('lms_cal_minutes')) {
    /**
     * Số phút lẻ trong giờ của một mốc thời gian ('17:30' / '17:30:00' → 30).
     *
     * @param string $time
     * @return int 0..59
     */
    function lms_cal_minutes($time)
    {
        $time = trim((string) $time);
        if (!preg_match('/^\d{1,2}:(\d{2})/', $time, $m)) {
            return 0;
        }
        $minutes = (int) $m[1];
        return ($minutes >= 0 && $minutes < 60) ? $minutes : 0;
    }
}

if (!function_exists('lms_cal_minutes_of_day')) {
    /**
     * Số phút tính từ 00:00 của một mốc giờ ('17:30' → 1050).
     *
     * @param string $time
     * @return int|null
     */
    function lms_cal_minutes_of_day($time)
    {
        $time = trim((string) $time);
        if (!preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
            return null;
        }
        $h = (int) $m[1];
        $i = (int) $m[2];
        if ($h > 23 || $i > 59) {
            return null;
        }
        return $h * 60 + $i;
    }
}

if (!function_exists('lms_cal_offset_px')) {
    /**
     * Khoảng thụt xuống (px) của thẻ trong hàng giờ khi giờ bắt đầu lẻ.
     * Hàng 72px, bắt đầu 17:30 → 36px; 17:00 → 0.
     *
     * @param string $start      'HH:MM'
     * @param int    $hourHeight chiều cao hàng giờ (px)
     * @return int
     */
    function lms_cal_offset_px($start, $hourHeight = LMS_CAL_HOUR_H)
    {
        $minutes = lms_cal_minutes($start);
        if ($minutes === 0 || $hourHeight <= 0) {
            return 0;
        }
        return (int) round($minutes / 60 * (int) $hourHeight);
    }
}

if (!function_exists('lms_cal_span_px')) {
    /**
     * Chiều cao (px) của thẻ theo thời lượng buổi học.
     * 17:30–19:00 (90 phút) với hàng 72px → 108px, tức thẻ kéo dài
     * quá ô của nó xuống tận cuối hàng 18:00.
     *
     * @param string $start      'HH:MM'
     * @param string $end        'HH:MM'
     * @param int    $hourHeight chiều cao hàng giờ (px)
     * @return int 0 khi dữ liệu giờ không hợp lệ
     */
    function lms_cal_span_px($start, $end, $hourHeight = LMS_CAL_HOUR_H)
    {
        $from = lms_cal_minutes_of_day($start);
        $to   = lms_cal_minutes_of_day($end);
        if ($from === null || $hourHeight <= 0) {
            return 0;
        }
        $duration = ($to === null || $to <= $from) ? 60 : $to - $from;
        return (int) round($duration / 60 * (int) $hourHeight);
    }
}

if (!function_exists('lms_cal_lanes_for_rows')) {
    /**
     * Chia "lane" (cột con trong cùng một cột ngày) cho các thẻ BUỔI HỌC chồng giờ nhau.
     * Khung lịch trống không tham gia: nó là dải nền full chiều ngang nằm sau thẻ buổi học.
     *
     * @param array $rows calRows của calendar_week (mỗi cell có 'sessions')
     * @return array map "date|s<session_id>" => array('lane' => int, 'lanes' => int)
     */
    function lms_cal_lanes_for_rows(array $rows)
    {
        $byDate = array();
        foreach ($rows as $row) {
            if (!isset($row['cells']) || !is_array($row['cells'])) {
                continue;
            }
            foreach ($row['cells'] as $cell) {
                $date = isset($cell['date']) ? (string) $cell['date'] : '';
                if ($date === '') {
                    continue;
                }
                if (!isset($byDate[$date])) {
                    $byDate[$date] = array();
                }
                $sessions = isset($cell['sessions']) && is_array($cell['sessions']) ? $cell['sessions'] : array();
                foreach ($sessions as $ses) {
                    $byDate[$date][] = array(
                        'key'   => 's' . (int) ($ses['id'] ?? 0),
                        'start' => (string) ($ses['start_time'] ?? ''),
                        'end'   => (string) ($ses['end_time'] ?? ''),
                    );
                }
            }
        }

        $map = array();
        foreach ($byDate as $date => $items) {
            usort($items, 'lms_cal_lane_sort');
            // Gom thành từng nhóm chồng giờ: thẻ không chồng với ai luôn full chiều ngang.
            $clusters   = array();
            $cluster    = array();
            $clusterEnd = null;
            foreach ($items as $item) {
                $from = lms_cal_minutes_of_day($item['start']);
                if ($from === null) {
                    continue;
                }
                $to = lms_cal_minutes_of_day($item['end']);
                if ($to === null || $to <= $from) {
                    $to = $from + 60;
                }
                if ($clusterEnd !== null && $from >= $clusterEnd) {
                    $clusters[] = $cluster;
                    $cluster    = array();
                    $clusterEnd = null;
                }
                $cluster[] = array('item' => $item, 'from' => $from, 'to' => $to);
                $clusterEnd = $clusterEnd === null ? $to : max($clusterEnd, $to);
            }
            if ($cluster !== array()) {
                $clusters[] = $cluster;
            }

            foreach ($clusters as $cluster) {
                $laneEnds = array();
                $assigned = array();
                foreach ($cluster as $entry) {
                    $lane = null;
                    foreach ($laneEnds as $index => $endsAt) {
                        if ($entry['from'] >= $endsAt) {
                            $lane = $index;
                            break;
                        }
                    }
                    if ($lane === null) {
                        $lane = count($laneEnds);
                    }
                    $laneEnds[$lane] = $entry['to'];
                    $assigned[$entry['item']['key']] = $lane;
                }
                $total = max(1, count($laneEnds));
                foreach ($assigned as $key => $lane) {
                    $map[$date . '|' . $key] = array('lane' => $lane, 'lanes' => $total);
                }
            }
        }
        return $map;
    }
}

if (!function_exists('lms_cal_lane_sort')) {
    /**
     * Thứ tự quét lane: giờ bắt đầu trước, thẻ dài hơn trước, rồi theo key.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    function lms_cal_lane_sort(array $a, array $b)
    {
        $startA = lms_cal_minutes_of_day($a['start']);
        $startB = lms_cal_minutes_of_day($b['start']);
        if ($startA !== $startB) {
            return $startA < $startB ? -1 : 1;
        }
        $lenA = lms_cal_minutes_of_day($a['end']) - $startA;
        $lenB = lms_cal_minutes_of_day($b['end']) - $startB;
        if ($lenA !== $lenB) {
            return $lenA > $lenB ? -1 : 1;
        }
        return strcmp((string) $a['key'], (string) $b['key']);
    }
}

if (!function_exists('lms_cal_lane_of')) {
    /**
     * Lane của một thẻ trong lưới (mặc định lane 0 / 1 lane khi không trùng giờ).
     *
     * @param array  $lanes map từ lms_cal_lanes_for_rows()
     * @param string $date  'Y-m-d'
     * @param string $key   's<id>' hoặc 'f<HH:MM>-<HH:MM>'
     * @return array array('lane' => int, 'lanes' => int)
     */
    function lms_cal_lane_of(array $lanes, $date, $key)
    {
        $id = $date . '|' . $key;
        if (isset($lanes[$id])) {
            return $lanes[$id];
        }
        return array('lane' => 0, 'lanes' => 1);
    }
}

if (!function_exists('lms_cal_time_text')) {
    /**
     * Chuỗi khung giờ hiển thị: '17:30–19:00'.
     *
     * @param string $start
     * @param string $end
     * @return string
     */
    function lms_cal_time_text($start, $end)
    {
        return substr(trim((string) $start), 0, 5) . '–' . substr(trim((string) $end), 0, 5);
    }
}
