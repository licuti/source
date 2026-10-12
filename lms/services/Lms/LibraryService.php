<?php
/**
 * LibraryService — Thư viện tài liệu lớp học (TCH-07 + admin, Phase 3).
 *
 * Quản lý bảng db_lms_library_files:
 *  - Admin xem/upload/sửa/xoá mọi file.
 *  - GV upload cho lớp của mình, xem/xoá file của mình.
 *  - Download: mọi người có quyền truy cập lớp đó; mỗi lần tải tăng download_count.
 *
 * File được lưu tại lms/public/uploads/library/<subdir>/ qua lms_upload.php.
 * file_url = relative path trong uploads/ (không có tiền tố /uploads/).
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class LibraryService extends LmsService
{
    /** Loại file hợp lệ (db_lms_library_files.file_type). */
    const FILE_TYPES = array(
        'slide'    => 'Slide bài giảng',
        'exam'     => 'Đề thi / Bài test',
        'video'    => 'Video',
        'audio'    => 'Audio',
        'document' => 'Tài liệu',
        'other'    => 'Khác',
    );

    /** Đuôi file cho phép upload vào thư viện. */
    const ALLOWED_EXTENSIONS = array('pdf', 'docx', 'xlsx', 'pptx', 'jpg', 'jpeg', 'png', 'mp3', 'mp4', 'zip');

    /** Dung lượng tối đa 1 file thư viện (100 MB). */
    const MAX_FILE_SIZE = 104857600;

    /**
     * Danh sách file thư viện + filter + phân trang.
     *
     * @param array $filters q, subject_id, class_id, file_type, teacher_id
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function listFiles(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = array('f.deleted_at is null');
        $params = array();

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[] = '(f.title like ? or f.description like ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like);
        }

        $subjectId = isset($filters['subject_id']) ? (int) $filters['subject_id'] : 0;
        if ($subjectId > 0) {
            $where[] = 'f.subject_id = ?';
            $params[] = $subjectId;
        }

        $classId = isset($filters['class_id']) ? (int) $filters['class_id'] : 0;
        if ($classId > 0) {
            $where[] = 'f.class_id = ?';
            $params[] = $classId;
        }

        $fileType = isset($filters['file_type']) ? (string) $filters['file_type'] : '';
        if ($fileType !== '' && isset(self::FILE_TYPES[$fileType])) {
            $where[] = 'f.file_type = ?';
            $params[] = $fileType;
        }

        $teacherId = isset($filters['teacher_id']) ? (int) $filters['teacher_id'] : 0;
        if ($teacherId > 0) {
            $where[] = 'f.uploaded_by = ?';
            $params[] = $teacherId;
        }

        $whereSql = implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_library_files f where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $items = $this->db->safeFetch(
            'select f.id, f.class_id, f.uploaded_by, f.title, f.file_type, f.file_url,'
            . ' f.file_size_bytes, f.subject_id, f.topic_id, f.description,'
            . ' f.download_count, f.is_downloadable, f.created_at,'
            . ' u.username as uploader_username, u.role as uploader_role,'
            . ' t.full_name as uploader_name, t.teacher_code,'
            . ' s.name as subject_name,'
            . ' c.name as class_name, c.class_code,'
            . ' top.name as topic_name'
            . ' from db_lms_library_files f'
            . ' left join db_lms_users u on u.id = f.uploaded_by'
            . ' left join db_lms_teachers t on t.user_id = f.uploaded_by'
            . ' left join db_lms_subjects s on s.id = f.subject_id'
            . ' left join db_lms_classes c on c.id = f.class_id'
            . ' left join db_lms_topics top on top.id = f.topic_id'
            . ' where ' . $whereSql
            . ' order by f.created_at desc'
            . ' limit ' . $offset . ', ' . $perPage,
            $params
        );

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Chi tiết 1 file.
     *
     * @param int $id
     * @return array rỗng khi không tồn tại hoặc đã xoá
     */
    public function getFile($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return array();
        }
        return $this->db->safeFetchOne(
            'select f.id, f.class_id, f.uploaded_by, f.title, f.file_type, f.file_url,'
            . ' f.file_size_bytes, f.subject_id, f.topic_id, f.description,'
            . ' f.download_count, f.is_downloadable, f.created_at,'
            . ' u.username as uploader_username, u.role as uploader_role,'
            . ' t.full_name as uploader_name, t.teacher_code,'
            . ' s.name as subject_name,'
            . ' c.name as class_name, c.class_code,'
            . ' top.name as topic_name'
            . ' from db_lms_library_files f'
            . ' left join db_lms_users u on u.id = f.uploaded_by'
            . ' left join db_lms_teachers t on t.user_id = f.uploaded_by'
            . ' left join db_lms_subjects s on s.id = f.subject_id'
            . ' left join db_lms_classes c on c.id = f.class_id'
            . ' left join db_lms_topics top on top.id = f.topic_id'
            . ' where f.id = ? and f.deleted_at is null limit 1',
            array($id)
        );
    }

    /**
     * Kiểm tra file có thể xem/tải trong portal Teacher hay không.
     * File chung (class_id NULL) chỉ truy cập được bởi uploader; file theo lớp
     * chỉ truy cập nếu GV hiện tại đang dạy lớp đó.
     */
    public function canTeacherAccessFile($id, $teacherId)
    {
        $row = $this->db->safeFetchOne(
            'select f.uploaded_by, f.class_id from db_lms_library_files f'
            . ' where f.id = ? and f.deleted_at is null limit 1',
            array((int) $id)
        );
        if (empty($row) || (int) $teacherId <= 0) {
            return false;
        }
        if ((int) $row['uploaded_by'] === (int) $teacherId || empty($row['class_id'])) {
            return true;
        }
        return !empty($this->db->safeFetchOne(
            'select id from db_lms_classes where id = ? and teacher_id = ? and deleted_at is null limit 1',
            array((int) $row['class_id'], (int) $teacherId)
        ));
    }

    /** Whether a teacher may download this accessible file. */
    public function canTeacherDownloadFile($id, $teacherId)
    {
        if (!$this->canTeacherAccessFile($id, $teacherId)) {
            return false;
        }
        $row = $this->db->safeFetchOne(
            'select is_downloadable from db_lms_library_files where id = ? and deleted_at is null limit 1',
            array((int) $id)
        );
        return !empty($row) && (int) $row['is_downloadable'] === 1;
    }

    /**
     * Thêm file thư viện mới (sau khi đã upload file lên disk qua lms_upload.php).
     *
     * @param array $data file_url (required), file_type, title, description,
     *                     file_size_bytes, subject_id, class_id, topic_id
     * @param int   $uploadedBy user id (admin or teacher)
     * @param int|null $teacherScope when set, class_id must belong to this teacher
     * @return int|null file id
     */
    public function createFile(array $data, $uploadedBy, $teacherScope = null)
    {
        $this->clearErrors();

        $uploadedBy = (int) $uploadedBy;
        if ($uploadedBy <= 0) {
            $this->addError('system', 'Người upload không hợp lệ.');
            return null;
        }

        $fileUrl = isset($data['file_url']) ? trim((string) $data['file_url']) : '';
        if ($fileUrl === '') {
            $this->addError('file_url', 'Đường dẫn file là bắt buộc.');
            return null;
        }

        $fileType = isset($data['file_type']) ? (string) $data['file_type'] : 'other';
        if (!isset(self::FILE_TYPES[$fileType])) {
            $fileType = 'other';
        }

        $classId = isset($data['class_id']) && (int) $data['class_id'] > 0
            ? (int) $data['class_id'] : null;
        $subjectId = isset($data['subject_id']) && (int) $data['subject_id'] > 0
            ? (int) $data['subject_id'] : null;
        $topicId = isset($data['topic_id']) && (int) $data['topic_id'] > 0
            ? (int) $data['topic_id'] : null;

        // Validate uploader and optional teacher ownership scope.
        $uploader = $this->db->safeFetchOne(
            'select id, role from db_lms_users where id = ? limit 1',
            array($uploadedBy)
        );
        if (empty($uploader) || !in_array((string) $uploader['role'], array('admin', 'teacher'), true)) {
            $this->addError('uploaded_by', 'Tài khoản upload không hợp lệ.');
            return null;
        }

        if ($classId !== null) {
            $cls = $this->db->safeFetchOne(
                'select id, teacher_id from db_lms_classes where id = ? and deleted_at is null limit 1',
                array($classId)
            );
            if (empty($cls)) {
                $this->addError('class_id', 'Lớp học không tồn tại.');
                return null;
            }
            if ($teacherScope !== null && (int) $cls['teacher_id'] !== (int) $teacherScope) {
                $this->addError('class_id', 'Bạn chỉ được tải tài liệu lên lớp mình đang dạy.');
                return null;
            }
        }

        $title = isset($data['title']) ? trim((string) $data['title']) : '';
        if ($title === '') {
            $title = basename($fileUrl);
        }
        if (mb_strlen($title, 'UTF-8') > 200) {
            $title = mb_substr($title, 0, 200, 'UTF-8');
        }

        $ok = $this->db->insert('db_lms_library_files', array(
            'class_id'         => $classId,
            'uploaded_by'      => $uploadedBy,
            'title'            => $title,
            'file_type'        => $fileType,
            'file_url'         => $fileUrl,
            'file_size_bytes'  => isset($data['file_size_bytes']) ? (int) $data['file_size_bytes'] : null,
            'subject_id'       => $subjectId,
            'topic_id'         => $topicId,
            'description'      => isset($data['description']) ? trim((string) $data['description']) : null,
            'download_count'   => 0,
            'is_downloadable'  => isset($data['is_downloadable']) ? (int) (bool) $data['is_downloadable'] : 1,
        ));

        if (!$ok) {
            $this->addError('system', 'Không thể lưu thông tin file. Vui lòng thử lại.');
            return null;
        }

        $fileId = (int) $this->db->insertId;
        $this->audit('upload_library_file', 'library_files', $fileId, null, array(
            'file_url' => $fileUrl,
            'file_type' => $fileType,
            'class_id' => $classId,
        ));
        return $fileId;
    }

    /**
     * Sửa metadata file (tiêu đề, mô tả, loại file, lớp/môn/chuyên đề).
     *
     * @param int   $id
     * @param array $data title, file_type, description, class_id, subject_id, topic_id
     * @return bool
     */
    public function updateFile($id, array $data)
    {
        $this->clearErrors();

        $id = (int) $id;
        if ($id <= 0) {
            $this->addError('system', 'ID không hợp lệ.');
            return false;
        }

        $before = $this->getFile($id);
        if (empty($before)) {
            $this->addError('system', 'File không tồn tại.');
            return false;
        }

        $fileType = isset($data['file_type']) ? (string) $data['file_type'] : (string) $before['file_type'];
        if (!isset(self::FILE_TYPES[$fileType])) {
            $fileType = 'other';
        }

        $classId = isset($data['class_id']) && (int) $data['class_id'] > 0
            ? (int) $data['class_id'] : null;
        $subjectId = isset($data['subject_id']) && (int) $data['subject_id'] > 0
            ? (int) $data['subject_id'] : null;
        $topicId = isset($data['topic_id']) && (int) $data['topic_id'] > 0
            ? (int) $data['topic_id'] : null;

        $title = isset($data['title']) ? trim((string) $data['title']) : '';
        if ($title === '') {
            $title = (string) $before['title'];
        }
        if (mb_strlen($title, 'UTF-8') > 200) {
            $title = mb_substr($title, 0, 200, 'UTF-8');
        }

        $ok = $this->db->update('db_lms_library_files', array(
            'title'       => $title,
            'file_type'   => $fileType,
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'class_id'    => $classId,
            'subject_id'  => $subjectId,
            'topic_id'    => $topicId,
            'is_downloadable' => isset($data['is_downloadable']) ? (int) (bool) $data['is_downloadable'] : (int) $before['is_downloadable'],
        ), 'id = ?', array($id));

        if ($ok) {
            $after = $this->getFile($id);
            $this->audit('update_library_file', 'library_files', $id, $before, $after);
        } else {
            $this->addError('system', 'Không thể cập nhật file. Vui lòng thử lại.');
        }
        return $ok;
    }

    /**
     * Xoá file (soft delete). Admin xoá mọi file; GV chỉ xoá file của mình.
     *
     * @param int      $id
     * @param int|null $ownerCheck null = không check (admin); int = chỉ cho phép nếu uploaded_by = ownerCheck
     * @return bool
     */
    public function deleteFile($id, $ownerCheck = null)
    {
        $this->clearErrors();

        $id = (int) $id;
        if ($id <= 0) {
            $this->addError('system', 'ID không hợp lệ.');
            return false;
        }

        $before = $this->getFile($id);
        if (empty($before)) {
            $this->addError('system', 'File không tồn tại.');
            return false;
        }

        if ($ownerCheck !== null && (int) $before['uploaded_by'] !== (int) $ownerCheck) {
            $this->addError('system', 'Bạn chỉ được xoá file do chính mình tải lên.');
            return false;
        }

        $ok = $this->db->update(
            'db_lms_library_files',
            array('deleted_at' => $this->now()),
            'id = ?',
            array($id)
        );

        if ($ok) {
            $this->audit('delete_library_file', 'library_files', $id, $before, null);
        } else {
            $this->addError('system', 'Không thể xoá file. Vui lòng thử lại.');
        }
        return $ok;
    }

    /**
     * Tăng download_count (gọi trước khi redirect download).
     *
     * @param int $id
     * @return void
     */
    public function incrementDownload($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return;
        }
        $this->db->safeExec(
            'update db_lms_library_files set download_count = download_count + 1'
            . ' where id = ? and deleted_at is null',
            array($id)
        );
    }

    /**
     * Danh sách file của các lớp 1 GV đang dạy (portal Teacher).
     * Gộp filter teacher_id vào listFiles.
     *
     * @param int   $teacherId
     * @param array $filters q, file_type, class_id
     * @param int   $page
     * @param int   $perPage
     * @return array
     */
    public function listFilesForTeacher($teacherId, array $filters = array(), $page = 1, $perPage = 20)
    {
        $teacherId = (int) $teacherId;
        if ($teacherId <= 0) {
            return array('items' => array(), 'total' => 0);
        }

        // Lớp GV đang dạy
        $classRows = $this->db->safeFetch(
            'select id from db_lms_classes where teacher_id = ? and deleted_at is null',
            array($teacherId)
        );
        $classIds = array();
        foreach ($classRows as $r) {
            $classIds[] = (int) $r['id'];
        }
        // Teacher thấy: file do chính mình upload HOẶC file gắn với lớp mình dạy
        // Phạm vi: (uploaded_by = teacher) OR (class_id IN lớp của teacher)
        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = array('f.deleted_at is null');
        $params = array();

        // Scope GV: tài liệu chung, file của mình, hoặc file lớp mình dạy.
        if ($classIds !== array()) {
            $inPh = implode(', ', array_fill(0, count($classIds), '?'));
            $where[] = '(f.class_id is null or f.uploaded_by = ? or f.class_id in (' . $inPh . '))';
            $params[] = $teacherId;
            $params = array_merge($params, $classIds);
        } else {
            $where[] = '(f.class_id is null or f.uploaded_by = ?)';
            $params[] = $teacherId;
        }

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[] = '(f.title like ? or f.description like ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like);
        }

        $fileType = isset($filters['file_type']) ? (string) $filters['file_type'] : '';
        if ($fileType !== '' && isset(self::FILE_TYPES[$fileType])) {
            $where[] = 'f.file_type = ?';
            $params[] = $fileType;
        }

        $filterClassId = isset($filters['class_id']) ? (int) $filters['class_id'] : 0;
        if ($filterClassId > 0) {
            $where[] = 'f.class_id = ?';
            $params[] = $filterClassId;
        }

        $whereSql = implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_library_files f where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $items = $this->db->safeFetch(
            'select f.id, f.class_id, f.uploaded_by, f.title, f.file_type, f.file_url,'
            . ' f.file_size_bytes, f.subject_id, f.topic_id, f.description,'
            . ' f.download_count, f.is_downloadable, f.created_at,'
            . ' u.username as uploader_username, u.role as uploader_role,'
            . ' s.name as subject_name,'
            . ' c.name as class_name, c.class_code'
            . ' from db_lms_library_files f'
            . ' left join db_lms_users u on u.id = f.uploaded_by'
            . ' left join db_lms_subjects s on s.id = f.subject_id'
            . ' left join db_lms_classes c on c.id = f.class_id'
            . ' where ' . $whereSql
            . ' order by f.created_at desc'
            . ' limit ' . $offset . ', ' . $perPage,
            $params
        );

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Định dạng dung lượng file để hiển thị.
     *
     * @param int|null $bytes
     * @return string
     */
    public static function formatSize($bytes)
    {
        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return '—';
        }
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        return round($bytes / 1073741824, 1) . ' GB';
    }
}
