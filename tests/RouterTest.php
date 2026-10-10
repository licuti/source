<?php
/**
 * Tests cho router LMS (T4 + D10).
 *
 * Phủ phần logic "thuần" của router (không dùng header()/exit):
 *  - lms_parse_route(): parse route mới (clean URL) + cũ (?p=&a=)
 *  - lms_find_module(): lookup module theo alias + portal (sai portal → null)
 *  - Helper URL: lms_url() / lms_url_to() / lms_asset_url() / portal label+icon
 *
 * Phần auth gate + redirect nằm ở AuthPortalTest (dùng lms_check_permission).
 */

namespace Tests;

use Tests\LmsTestCase;

class RouterTest extends LmsTestCase
{
    // ===================== lms_parse_route — clean URL (D10) =====================

    public function testEmptyRouteDefaultsToDashboard()
    {
        $r = lms_parse_route('', array());
        $this->assertSame('lms-tong-quan', $r['alias']);
        $this->assertSame('hienthi', $r['action']);
    }

    public function testDashboardReservedWordMapsToTongQuan()
    {
        $r = lms_parse_route('dashboard', array());
        $this->assertSame('lms-tong-quan', $r['alias']);
        $this->assertSame('hienthi', $r['action']);
    }

    public function testReservedActionsAreNotModuleAliases()
    {
        foreach (array('login', 'logout') as $act) {
            $r = lms_parse_route($act, array());
            $this->assertSame('', $r['alias']);
            $this->assertSame($act, $r['action']);
        }
    }

    public function testModuleAliasDefaultsToManAction()
    {
        $r = lms_parse_route('lms-hoc-vien', array());
        $this->assertSame('lms-hoc-vien', $r['alias']);
        $this->assertSame('man', $r['action']);
    }

    public function testModuleAliasWithAction()
    {
        $r = lms_parse_route('lms-hoc-vien/them', array());
        $this->assertSame('lms-hoc-vien', $r['alias']);
        $this->assertSame('them', $r['action']);
    }

    public function testRouteIsTrimmedOfSurroundingSlashes()
    {
        $r = lms_parse_route('/lms-hoc-vien/', array());
        $this->assertSame('lms-hoc-vien', $r['alias']);
        $this->assertSame('man', $r['action']);
    }

    // ===================== lms_parse_route — backward compat (?p=&a= cũ) =====================

    public function testBackwardCompatQueryArgs()
    {
        $r = lms_parse_route('', array('p' => 'lms-hoc-vien', 'a' => 'sua'));
        $this->assertSame('lms-hoc-vien', $r['alias']);
        $this->assertSame('sua', $r['action']);
    }

    public function testBackwardCompatOnlyPGiven()
    {
        $r = lms_parse_route('', array('p' => 'lms-giao-vien'));
        $this->assertSame('lms-giao-vien', $r['alias']);
        $this->assertSame('man', $r['action']);
    }

    public function testNewRouteTakesPrecedenceOverOldQueryArgs()
    {
        $r = lms_parse_route('lms-lop-hoc', array('p' => 'lms-hoc-vien', 'a' => 'sua'));
        $this->assertSame('lms-lop-hoc', $r['alias']);
        $this->assertSame('man', $r['action']);
    }

    // ===================== Helper URL =====================

    public function testBaseUrlIsAbsolutePathEndingWithLms()
    {
        $base = lms_base_url();
        $this->assertStringStartsWith('/', $base);
        $this->assertStringEndsWith('/lms', $base);
    }

    public function testUrlToOtherPortal()
    {
        $expected = lms_base_url() . '/student/lms-khoa-hoc/chi-tiet?id=7';
        $this->assertSame($expected, lms_url_to('student', 'lms-khoa-hoc', 'chi-tiet', array('id' => 7)));
    }

    public function testUrlToOtherPortalWithoutParams()
    {
        $expected = lms_base_url() . '/teacher';
        $this->assertSame($expected, lms_url_to('teacher'));
    }

    public function testUrlForCurrentPortal()
    {
        // _LMS_PORTAL = 'admin' (tests/bootstrap.php)
        $base = lms_base_url();
        $this->assertSame($base . '/admin/lms-hoc-vien', lms_url('lms-hoc-vien'));
        $this->assertSame($base . '/admin/login', lms_url('', 'login'));
        $this->assertSame($base . '/admin', lms_url(''));
    }

    public function testUrlWithMultipleParams()
    {
        $url = lms_url('lms-hoc-vien', 'sua', array('id' => 3, 'page' => 2));
        $this->assertSame(lms_base_url() . '/admin/lms-hoc-vien/sua?id=3&page=2', $url);
    }

    public function testAssetUrl()
    {
        $base = lms_base_url();
        $this->assertSame($base . '/public/css/lms.css', lms_asset_url('css/lms.css'));
        // slash đầu phải được strip
        $this->assertSame($base . '/public/js/lms.js', lms_asset_url('/js/lms.js'));
    }

    public function testPortalLabels()
    {
        $this->assertSame('Quản trị', lms_portal_label('admin'));
        $this->assertSame('Giáo viên', lms_portal_label('teacher'));
        $this->assertSame('Học viên', lms_portal_label('student'));
        $this->assertSame('khac', lms_portal_label('khac'));
    }

    public function testPortalIconsAreFontAwesome6Classes()
    {
        foreach (array('admin', 'teacher', 'student', 'khac') as $p) {
            $this->assertStringStartsWith('fa-solid fa-', lms_portal_icon($p));
        }
        $this->assertSame('fa-solid fa-user-shield', lms_portal_icon('admin'));
    }

    // ===================== lms_find_module =====================

    private function seedModule($alias, $portal)
    {
        $this->db->insert('db_lms_modules', array(
            'parent'     => 0,
            'name'       => 'Module ' . $alias,
            'alias'      => $alias,
            'portal'     => $portal,
            'icon'       => 'fa-solid fa-circle',
            'so_thu_tu'  => 1,
            'hien_thi'   => 1,
        ));
        return (int) $this->db->insertId;
    }

    public function testFindModuleInCorrectPortal()
    {
        $id = $this->seedModule('lms-hoc-vien', 'admin');

        $m = lms_find_module($this->db, 'lms-hoc-vien', 'admin');

        $this->assertNotNull($m);
        $this->assertSame($id, (int) $m['id']);
        $this->assertSame('lms-hoc-vien', $m['alias']);
    }

    public function testFindModuleInWrongPortalReturnsNull()
    {
        $this->seedModule('lms-hoc-vien', 'admin');

        $this->assertNull(lms_find_module($this->db, 'lms-hoc-vien', 'student'));
        $this->assertNull(lms_find_module($this->db, 'lms-hoc-vien', 'teacher'));
    }

    public function testFindModuleUnknownAliasReturnsNull()
    {
        $this->seedModule('lms-hoc-vien', 'admin');

        $this->assertNull(lms_find_module($this->db, 'lms-khong-ton-tai', 'admin'));
    }

    public function testFindModuleEmptyAliasReturnsNull()
    {
        $this->assertNull(lms_find_module($this->db, '', 'admin'));
    }
}
