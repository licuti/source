-- ---------------------------------------------------------------------------
-- Seed: 2026_10_10_lms_violation_thresholds — ngưỡng phạt vi phạm (tick)
--
-- SRS_MasterStudy.md §ADM-06: rule theo khoảng số lớp GV đang dạy:
--   1-3 lớp  → miễn 2 tick
--   4-7 lớp  → miễn 3 tick
--   >=8 lớp  → miễn 4 tick
--   Ngưỡng hạ phí dịch vụ: 8 tick (áp dụng fee_percentage_after_demote tháng sau)
--
-- Chạy trên cả DB thật (phuongnamv_db) và DB test (phuongnamv_db_lms_test).
--
-- Giá trị đã chốt (business confirm 12/10/2026 — P4):
--  - ticks_to_warn = 5: cảnh báo trước khi chạm ngưỡng hạ phí (8).
--    Khoảng cách 5→8 cho GV 3 tick "thời gian sửa sai" sau cảnh báo.
--  - fee_percentage_after_demote = 30.00: tỷ lệ phí dịch vụ trực tiếp sau khi
--    bị hạ (thấp hơn mức mặc định teachers.service_fee_percentage = 48.00, khớp
--    ví dụ ERD). Payroll không cho phép giá trị này làm tăng tỷ lệ hiện hành
--    (GV đang ở 48% vẫn nhận tối đa 48%). Đổi qua UI ADM-06 khi business yêu
--    cầu, KHÔNG sửa seed này (chỉ áp dụng cho DB mới).
-- ---------------------------------------------------------------------------

-- Re-running this production seed replaces the official 2026-10-10 set
-- without accumulating duplicate threshold rows. Historical effective dates
-- remain untouched.
DELETE FROM `db_lms_violation_thresholds`
 WHERE `effective_from` = '2026-10-10';

INSERT INTO `db_lms_violation_thresholds`
    (`min_classes`, `max_classes`, `ticks_to_warn`, `ticks_to_demote_fee`,
     `fee_percentage_after_demote`, `ticks_exempted`, `effective_from`, `effective_to`)
VALUES
    (1, 3,    5, 8, 30.00, 2, '2026-10-10', NULL),
    (4, 7,    5, 8, 30.00, 3, '2026-10-10', NULL),
    (8, NULL, 5, 8, 30.00, 4, '2026-10-10', NULL);
