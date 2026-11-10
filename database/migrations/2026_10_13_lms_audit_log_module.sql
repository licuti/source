-- Register the admin-only audit log viewer (N3#11).
INSERT INTO db_lms_modules (parent, name, alias, portal, icon, so_thu_tu, hien_thi)
SELECT 0, 'Nhật ký hệ thống', 'lms-audit-log', 'admin', 'fa-solid fa-clipboard-list', 15, 1
WHERE NOT EXISTS (
    SELECT 1 FROM db_lms_modules WHERE alias = 'lms-audit-log' AND portal = 'admin'
);

INSERT INTO db_lms_role_permissions (id_module, role, can_view, can_edit, can_delete)
SELECT m.id, 'admin', 1, 0, 0
FROM db_lms_modules m
WHERE m.alias = 'lms-audit-log' AND m.portal = 'admin'
  AND NOT EXISTS (
      SELECT 1 FROM db_lms_role_permissions p
      WHERE p.id_module = m.id AND p.role = 'admin'
  );
