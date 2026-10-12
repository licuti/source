<?php
/**
 * Tests cho LibraryService — thư viện file (TCH-07/admin).
 */

namespace Tests\Services;

use Lms\ClassService;
use Lms\LibraryService;
use Lms\UserService;
use Tests\LmsTestCase;

class LibraryServiceTest extends LmsTestCase
{
    private $svc;
    private $userSvc;
    private $teacher1;
    private $teacher2;
    private $subjectId;
    private $class1;
    private $class2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new LibraryService($this->db);
        $this->userSvc = new UserService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'Math'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Algebra'));
        $this->subjectId = (int) $this->db->insertId;

        $this->teacher1 = $this->createTeacher('lib_teacher_1', 'LIB001');
        $this->teacher2 = $this->createTeacher('lib_teacher_2', 'LIB002');

        $classSvc = new ClassService($this->db);
        $this->class1 = $classSvc->createClass(array(
            'program_id' => $programId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher1,
            'class_type' => 'one_on_one',
            'name' => 'Class one',
            'student_ids' => array($this->createStudent('lib_student_1', 'LIBS01')),
        ));
        $this->class2 = $classSvc->createClass(array(
            'program_id' => $programId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacher2,
            'class_type' => 'one_on_one',
            'name' => 'Class two',
            'student_ids' => array($this->createStudent('lib_student_2', 'LIBS02')),
        ));
        $this->assertNotNull($this->class1);
        $this->assertNotNull($this->class2);
    }

    private function createTeacher($username, $code)
    {
        $id = $this->userSvc->createUser($username, 'password123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id' => $id,
            'teacher_code' => $code,
            'full_name' => 'Teacher ' . $code,
            'status' => 'active',
            'max_classes' => 5,
        ));
        return $id;
    }

    private function createStudent($username, $code)
    {
        $id = $this->userSvc->createUser($username, 'password123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id' => $id,
            'student_code' => $code,
            'full_name' => 'Student ' . $code,
            'status' => 'studying',
        ));
        return $id;
    }

    private function addFile($uploader, $classId, $title, $type = 'document', $url = 'library/sample.pdf')
    {
        return $this->svc->createFile(array(
            'class_id' => $classId,
            'subject_id' => $this->subjectId,
            'title' => $title,
            'file_type' => $type,
            'file_url' => $url,
            'file_size_bytes' => 12345,
            'description' => 'Description ' . $title,
        ), $uploader);
    }

    public function testCreateAndGetFile()
    {
        $id = $this->addFile($this->teacher1, $this->class1, 'Algebra notes', 'slide');
        $this->assertNotNull($id);
        $this->assertFalse($this->svc->hasErrors());

        $file = $this->svc->getFile($id);
        $this->assertSame('Algebra notes', $file['title']);
        $this->assertSame('slide', $file['file_type']);
        $this->assertSame('Algebra', $file['subject_name']);
        $this->assertSame('Class one', $file['class_name']);
        $this->assertSame(0, (int) $file['download_count']);
    }

    public function testCreateFileRequiresPathAndValidUploader()
    {
        $this->assertNull($this->svc->createFile(array('file_type' => 'pdf'), $this->teacher1));
        $this->assertArrayHasKey('file_url', $this->svc->errors());

        $this->assertNull($this->svc->createFile(array('file_url' => 'a.pdf'), 0));
        $this->assertArrayHasKey('system', $this->svc->errors());
    }

    public function testAdminCanUploadAndTeacherCannotAttachForeignClass()
    {
        $adminId = $this->userSvc->createUser('lib_admin', 'password123', 'admin');
        $id = $this->svc->createFile(array(
            'file_url' => 'library/admin.pdf',
            'title' => 'Admin general file',
        ), $adminId);
        $this->assertNotNull($id);
        $this->assertSame('admin', $this->svc->getFile($id)['uploader_role']);

        $foreign = $this->svc->createFile(array(
            'file_url' => 'library/foreign.pdf',
            'class_id' => $this->class2,
            'title' => 'Foreign class file',
        ), $this->teacher1, $this->teacher1);
        $this->assertNull($foreign);
        $this->assertArrayHasKey('class_id', $this->svc->errors());
    }

    public function testCreateFileNormalizesTypeAndTitle()
    {
        $id = $this->svc->createFile(array(
            'file_url' => 'library/worksheet.pdf',
            'file_type' => 'invalid-type',
        ), $this->teacher1);
        $file = $this->svc->getFile($id);
        $this->assertSame('other', $file['file_type']);
        $this->assertSame('worksheet.pdf', $file['title']);
    }

    public function testListFilesFiltersAndPaginates()
    {
        $this->addFile($this->teacher1, $this->class1, 'Algebra review', 'exam', 'library/review.pdf');
        $this->addFile($this->teacher2, $this->class2, 'Geometry slides', 'slide', 'library/geometry.pdf');

        $all = $this->svc->listFiles();
        $this->assertSame(2, $all['total']);

        $exam = $this->svc->listFiles(array('file_type' => 'exam'));
        $this->assertSame(1, $exam['total']);
        $this->assertSame('Algebra review', $exam['items'][0]['title']);

        $byClass = $this->svc->listFiles(array('class_id' => $this->class2));
        $this->assertSame(1, $byClass['total']);
        $this->assertSame('Geometry slides', $byClass['items'][0]['title']);

        $byQuery = $this->svc->listFiles(array('q' => 'geometry'));
        $this->assertSame(1, $byQuery['total']);

        $page = $this->svc->listFiles(array(), 1, 1);
        $this->assertSame(2, $page['total']);
        $this->assertCount(1, $page['items']);
    }

    public function testListFilesForTeacherScopesOwnAndTaughtClasses()
    {
        $own = $this->addFile($this->teacher1, null, 'Teacher one general', 'document', 'library/own.pdf');
        $classOne = $this->addFile($this->teacher2, $this->class1, 'Class one resource', 'slide', 'library/class1.pdf');
        $classTwo = $this->addFile($this->teacher2, $this->class2, 'Class two private', 'exam', 'library/class2.pdf');

        $res = $this->svc->listFilesForTeacher($this->teacher1);
        $ids = array_map(function ($row) { return (int) $row['id']; }, $res['items']);
        $this->assertContains($own, $ids);
        $this->assertContains($classOne, $ids);
        $this->assertNotContains($classTwo, $ids);
        $this->assertSame(2, $res['total']);
        $this->assertTrue($this->svc->canTeacherAccessFile($own, $this->teacher2));
        $this->assertTrue($this->svc->canTeacherAccessFile($classOne, $this->teacher1));
        $this->assertFalse($this->svc->canTeacherAccessFile($classTwo, $this->teacher1));
    }

    public function testUpdateFileChangesMetadataAndAudits()
    {
        $id = $this->addFile($this->teacher1, $this->class1, 'Old title');
        $this->assertTrue($this->svc->updateFile($id, array(
            'title' => 'New title',
            'file_type' => 'exam',
            'description' => 'Updated description',
            'class_id' => $this->class2,
            'subject_id' => $this->subjectId,
        )));

        $file = $this->svc->getFile($id);
        $this->assertSame('New title', $file['title']);
        $this->assertSame('exam', $file['file_type']);
        $this->assertSame('Updated description', $file['description']);
        $this->assertSame($this->class2, (int) $file['class_id']);

        $audit = $this->db->safeFetchOne(
            'select action from db_lms_audit_logs where target_type = ? and target_id = ? order by id desc limit 1',
            array('library_files', $id)
        );
        $this->assertSame('update_library_file', $audit['action']);
    }

    public function testUpdateMissingFileFails()
    {
        $this->assertFalse($this->svc->updateFile(999, array('title' => 'missing')));
        $this->assertArrayHasKey('system', $this->svc->errors());
    }

    public function testDeleteIsSoftAndCanCheckOwner()
    {
        $id = $this->addFile($this->teacher1, $this->class1, 'Delete me');

        $this->assertFalse($this->svc->deleteFile($id, $this->teacher2));
        $this->assertArrayHasKey('system', $this->svc->errors());
        $this->assertNotEmpty($this->svc->getFile($id));

        $this->assertTrue($this->svc->deleteFile($id, $this->teacher1));
        $this->assertSame(array(), $this->svc->getFile($id));
        $this->assertSame(0, $this->svc->listFiles()['total']);

        $row = $this->db->safeFetchOne('select deleted_at from db_lms_library_files where id = ?', array($id));
        $this->assertNotEmpty($row['deleted_at']);
    }

    public function testIncrementDownloadOnlyCountsActiveFile()
    {
        $id = $this->addFile($this->teacher1, null, 'Download me');
        $this->svc->incrementDownload($id);
        $this->svc->incrementDownload($id);
        $this->assertSame(2, (int) $this->svc->getFile($id)['download_count']);

        $this->svc->deleteFile($id);
        $this->svc->incrementDownload($id);
        $row = $this->db->safeFetchOne('select download_count from db_lms_library_files where id = ?', array($id));
        $this->assertSame(2, (int) $row['download_count']);
    }

    public function testAdminCanDisableTeacherDownloads()
    {
        $id = $this->addFile($this->teacher1, $this->class1, 'Restricted slides');
        $this->assertTrue($this->svc->canTeacherDownloadFile($id, $this->teacher1));
        $this->assertTrue($this->svc->updateFile($id, array(
            'title' => 'Restricted slides',
            'is_downloadable' => 0,
        )));
        $this->assertFalse($this->svc->canTeacherDownloadFile($id, $this->teacher1));
        $this->assertSame(0, (int) $this->svc->getFile($id)['is_downloadable']);
    }

    public function testFormatSize()
    {
        $this->assertSame('—', LibraryService::formatSize(0));
        $this->assertSame('512 B', LibraryService::formatSize(512));
        $this->assertSame('1 KB', LibraryService::formatSize(1024));
        $this->assertSame('1 MB', LibraryService::formatSize(1048576));
    }
}
