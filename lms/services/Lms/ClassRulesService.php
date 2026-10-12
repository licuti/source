<?php
/** Stores the single administrator-authored class-rules document. */
namespace Lms;

class ClassRulesService extends LmsService
{
    public function getRules()
    {
        $row = $this->db->safeFetchOne(
            'select content, updated_at from db_lms_class_rules where id = 1 limit 1'
        );
        return empty($row) ? array('content' => '', 'updated_at' => null) : $row;
    }

    public function saveRules($content, $adminId)
    {
        $this->clearErrors();
        $content = trim((string) $content);
        $adminId = (int) $adminId;
        if ($content === '') {
            $this->addError('content', 'Nội dung nội quy không được để trống.');
            return false;
        }
        if (mb_strlen($content, 'UTF-8') > 50000) {
            $this->addError('content', 'Nội dung không được vượt quá 50.000 ký tự.');
            return false;
        }
        $before = $this->getRules();
        $ok = $this->db->safeExec(
            'insert into db_lms_class_rules (id, content, updated_by) values (1, ?, ?)'
            . ' on duplicate key update content = values(content), updated_by = values(updated_by)',
            array($content, $adminId > 0 ? $adminId : null)
        );
        if (!$ok) {
            $this->addError('system', 'Không thể lưu nội quy.');
            return false;
        }
        $this->audit('update_class_rules', 'class_rules', 1, $before, $this->getRules());
        return true;
    }
}
