INSERT INTO db_lms_modules (parent, name, alias, portal, icon, so_thu_tu, hien_thi)
SELECT 0, 'Nhận xét theo tháng', 'lms-nhan-xet-thang', 'teacher',
       'fa-solid fa-comment-dots', 7, 1
WHERE NOT EXISTS (
  SELECT 1 FROM db_lms_modules
  WHERE alias = 'lms-nhan-xet-thang' AND portal = 'teacher'
);

INSERT INTO db_lms_role_permissions (id_module, role, can_view, can_edit, can_delete)
SELECT m.id, 'teacher', 1, 1, 0
FROM db_lms_modules m
WHERE m.alias = 'lms-nhan-xet-thang' AND m.portal = 'teacher'
  AND NOT EXISTS (
    SELECT 1 FROM db_lms_role_permissions p
    WHERE p.id_module = m.id AND p.role = 'teacher'
  );
