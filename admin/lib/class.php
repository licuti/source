<?php
class func_index{
    // global $rf;
    var $db = "";
    var $result = "";
    var $insert_id = "";
    var $sql = "";
    var $table = "";
    var $where = "";
    var $order = "";
    var $limit = "";

    var $servername = "";
    var $username = "";
    var $password = "";
    var $database = "";
    var $refix = "";

    function __construct($config = array()){
        if(!empty($config))
        {
           $this->init($config);
           $this->connect();
        }
    }

    function init($config = array()){
        foreach($config as $k=>$v)
            $this->$k = $v;
    }

    function connect(){
        try {
            $this->db = new PDO("mysql:host=$this->servername;dbname=$this->database;charset=utf8", $this->username, $this->password);
            // set the PDO error mode to exception
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec("set names utf8");
             // echo "Connected successfully"; 

        }
        catch(PDOException $e){
            // Không in message của PDO ra màn hình vì nó chứa tên database/user
            error_log('[DB] Connection failed: ' . $e->getMessage());
            echo 'Không kết nối được cơ sở dữ liệu. Vui lòng thử lại sau.';
            exit;
        }
    }

    function disconnect(){
        $this->db = null;
    }
    
    function select($str = "*"){
        $this->sql = "select " . $str;
        $this->sql .= " from " . $this->refix .$this->table;
        $this->sql .=  $this->where;
        $this->sql .=  $this->order;
        $this->sql .=  $this->limit;
        $this->sql = str_replace('#_', $this->refix, $this->sql);
        return $this->query();
    }

    /** @param string $sql Để trống thì dùng $this->sql (select() gọi không tham số) */
    function query($sql = ''){
        if ($sql === '') { $sql = $this->sql; }
        $this->sql = str_replace('#_', $this->refix, $sql);
        $stmt = $this->db->prepare($this->sql); 
        return $stmt->execute();
    }

    /**
     * Chạy câu lệnh có tham số, trả về mảng kết quả.
     *
     * Đây là cách ĐÚNG để truy vấn với dữ liệu người dùng — giá trị được bind
     * nên không cần addslashes và không thể bị SQL injection.
     *
     *      $d->safeFetch("select * from #_user where email = ? and hien_thi = ?", [$email, 1]);
     *
     * @param  string $sql    Dùng ? cho tham số
     * @param  array  $params Giá trị theo đúng thứ tự dấu ?
     * @return array
     */
    public function safeFetch($sql, $params = array()){
        $this->sql = str_replace('#_', $this->refix, $sql);
        $stmt = $this->db->prepare($this->sql);
        $stmt->execute(array_values($params));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Như safeFetch() nhưng chỉ trả về dòng đầu tiên (mảng rỗng nếu không có).
     *
     * @param  string $sql
     * @param  array  $params
     * @return array
     */
    public function safeFetchOne($sql, $params = array()){
        $rows = $this->safeFetch($sql, $params);
        return empty($rows) ? array() : $rows[0];
    }

    /**
     * Chạy INSERT/UPDATE/DELETE có tham số.
     *
     * @param  string $sql
     * @param  array  $params
     * @return bool
     */
    public function safeExec($sql, $params = array()){
        $this->sql = str_replace('#_', $this->refix, $sql);
        $stmt = $this->db->prepare($this->sql);
        return $stmt->execute(array_values($params));
    }
	
    function fetch_array($sql){

        $arr = array();
        $this->sql = str_replace('#_', $this->refix, $sql);
        $stmt = $this->db->prepare($this->sql); 
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function fetch(){
        $arr = array();
        $this->sql = str_replace('#_', $this->refix, $this->sql);
        $stmt = $this->db->prepare($this->sql); 
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function o_fet($sql){
        $this->sql = $sql;
        return $this->fetch();
    }

    public function o_fet_class($sql){
        $this->sql = $sql;
        return $this->fetch_class();
    }

    public function fetch_class(){
        $arr = array();
        $this->sql = str_replace('#_', $this->refix, $this->sql);
        $stmt = $this->db->prepare($this->sql); 
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    public function o_sel($sel,$table, $where = "", $order = "", $limit = ""){
        if( $where <> '')  $where = " where ". $where;
        else $where = "";
        if( $order <> '')  $order = " order by ". $order;
        else $order = "";
        if( $limit <> '')  $limit = " limit ". $limit;
        else $limit = "";
        $sql = "select ".$sel. " from ".$table." ".$where.$order.$limit;
        $this->sql = $sql;
        return $this->fetch();
    }
    public function o_que($sql){
        $this->sql = $sql;
        return $this->que();
    }
    function assoc_array($sql){
        $this->sql = str_replace('#_', $this->refix,$sql);
        $stmt = $this->db->prepare($this->sql); 
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function num_rows($sql){
        $this->sql = str_replace('#_', $this->refix, $sql);
        $stmt = $this->db->prepare($this->sql); 
        $stmt->execute();
        return $stmt->rowCount(); 
    }

    function num(){
        $this->sql = str_replace('#_', $this->refix, $this->sql);
        $stmt = $this->db->prepare($this->sql); 
        $stmt->execute();
        return $stmt->rowCount(); 
    }

    function que(){
        $this->sql = str_replace('#_', $this->refix, $this->sql);
        $stmt = $this->db->prepare($this->sql); 
        return $stmt->execute();
    }

    function setTable($str){
        $this->table = $str;
    }

    function setWhere($col,$dk){
        if($this->where == ""){
            $this->where = " where ".$col." = '".$dk."'";
        }
        else{
            $this->where .= " and ".$col." = '".$dk."'";
        }
    }

    function setWhereOrther($col,$dk){
        if($this->where == ""){
            $this->where = " where ".$col." <> '".$dk."'";
        }
        else{
            $this->where .= " and ".$col." <> '".$dk."'";
        }
    }

    function setWhereOr($col,$dk){
        if($this->where == ""){
            $this->where = " where ".$col." = '".$dk."'";
        }
        else{
            $this->where .= " or ".$col." = '".$dk."'";
        }
    }

    function setOrder($str){
        $this->order = " order by " . $str;
    }

    function setLimit($str){
        $this->limit = " limit " . $str;
    }

    function reset(){
        $this->sql = "";
        $this->result = "";
        $this->where = "";
        $this->order = "";
        $this->limit = "";
        $this->table = "";
    }

    function insert($data = array()){
        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data);
        $holders = array_fill(0, count($columns), '?');

        $this->sql = str_replace('#_', $this->refix,
            "insert into " . $this->table
            . " (" . implode(',', $columns) . ")"
            . " values (" . implode(',', $holders) . ")"
        );

        $stmt = $this->db->prepare($this->sql);
        $stmt->execute($this->bindValues($data));

        return $this->db->lastInsertId();
    }

    function update($data = array()){
        if (empty($data)) {
            return false;
        }

        $assignments = array();
        foreach (array_keys($data) as $col) {
            $assignments[] = $col . ' = ?';
        }

        $this->sql = str_replace('#_', $this->refix,
            "update " . $this->table . " set " . implode(', ', $assignments) . $this->where
        );

        $stmt = $this->db->prepare($this->sql);
        $this->result = $stmt->execute($this->bindValues($data));

        return $this->result;
    }

    /**
     * Chuẩn hoá giá trị trước khi bind.
     *
     * QUAN TRỌNG: phần lớn module gọi insert()/update() với giá trị đã qua
     * addslashes(). Trước đây giá trị được nối thẳng vào SQL nên MySQL tự
     * "tháo" dấu \ khi lưu. Nay dùng bind, MySQL KHÔNG tháo nữa -> nếu giữ
     * nguyên thì dữ liệu sẽ bị thừa dấu \ ("O\'Brien").
     * Vì vậy phải stripslashes() để trả giá trị về dạng thô, đúng như dữ liệu
     * thực sự được lưu trước đây.
     *
     * @param  array $data
     * @return array Giá trị theo đúng thứ tự cột
     */
    private function bindValues(array $data){
        $values = array();

        foreach ($data as $val) {
            if (is_string($val)) {
                $val = stripslashes($val);
            }
            $values[] = $val;
        }

        return $values;
    }


    function delete(){
        $this->sql = "delete from ".$this->table.$this->where;
        $this->sql = str_replace('#_', $this->refix, $this->sql);
        return $this->query($this->sql);
    }


    // other-----------------------------
    function alert($str)
    {
        echo '<script language="javascript"> alert("'.$str.'") </script>';
    }

    function location($url)
    {
        echo '<script language="javascript">window.location = "'.$url.'" </script>';
    }
   
    function checkLink($alias ,$id=''){
        if($id!=''){
            $where = " and id <> ".$id;
        }else{
            $where="";
        }
        $row_cate = $this->num_rows("select * from #_category where alias = '$alias' $where ");
        $row_sanpham = $this->num_rows("select * from #_sanpham where alias = '$alias' $where ");
        $row_tintuc = $this->num_rows("select * from #_tintuc where alias = '$alias' $where ");
        if($row_cate == 0 and $row_sanpham==0 and $row_tintuc == 0 ){
            return 1;
        }else{
            return 0;
        }
    }

    function fullAddress(){
        $adr = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
        $adr .= isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : getenv('HTTP_HOST');
        $adr .= isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : getenv('REQUEST_URI');
        $adr2 = explode('&page=', $adr);
            return $adr2[0];
    }
    
    function fns_Rand_digit($min,$max,$num){
        $result='';
        for($i=0;$i<$num;$i++){
                $result.=rand($min,$max);
        }
        return $result;
    }

    function simple_fetch($sql) {
        $arr = array();
        $this->sql = str_replace('#_', $this->refix, $sql);
        $stmt = $this->db->prepare($this->sql); 
        $stmt->execute();
        // $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
        $result = $stmt->fetchAll();	
        if(!empty($result)){
                return $result[0];
        }
        return array();
    }

    function findIdSub($id,$level=0) {
        $str="";
        $id = (int) $id;
        $query=$this->o_fet("select * from #_category where id_loai=".$id." and hien_thi=1 order by so_thu_tu asc, id desc");
        if(count($query) > 0) {
            foreach($query as $item) {
                $str.=",".$item['id'];
                $check=$this->o_fet("select * from #_category where id_loai=".(int)$item['id']." and hien_thi=1 order by so_thu_tu asc, id desc");
                if(count($check)>0 && $level==0) {
                    $str.=$this->findIdSub($item['id']);
                }
            }
        }
        return $str;
    }
    	
    function clear($html) {
        // 1. Loại bỏ thuộc tính sự kiện (onload, onclick, ...)
        $html = preg_replace('/\son\w+\s*=\s*["\'].*?["\']/is', '', $html);

        // 2. Loại bỏ javascript: trong href/src/data
        $html = preg_replace('/\s(href|src|data)\s*=\s*["\']\s*javascript:[^"\']*["\']/is', '', $html);

        // 3. Loại bỏ src/href/data dùng base64
        $html = preg_replace('/\s(href|src|data)\s*=\s*["\']\s*data:[^"\']*["\']/is', '', $html);

        return $html;
    }
		
    function generateUniqueToken($username) {
        $token = time() . rand(10, 5000) . sha1(rand(10, 5000)) . md5(__FILE__);
        $token = str_shuffle($token);
        $token = sha1($token) . md5(microtime()) . md5($username);
        return $token;
    }

    function getPassHash($token,$password) {
        $password_hash=md5(md5($token) . md5($password));
        return $password_hash;
    }

    /**
     * Băm mật khẩu bằng bcrypt. Dùng cho MỌI nơi lưu mật khẩu.
     *
     * @param  string $password
     * @return string
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Đối chiếu mật khẩu với hash đang lưu.
     *
     * Chấp nhận cả 3 định dạng đang tồn tại trong dữ liệu: bcrypt, sha1
     * và md5(md5(token).md5(pass)) — nhờ đó không ai phải đặt lại mật khẩu.
     *
     * @param  string $password
     * @param  string $storeHash
     * @param  string $token     Chỉ cần cho định dạng md5 cũ
     * @return bool
     */
    public function verifyPassword($password, $storeHash, $token = '') {
        if (empty($storeHash)) {
            return false;
        }

        if ($this->isModernHash($storeHash)) {
            return password_verify($password, $storeHash);
        }

        // hash_equals: so sánh trong thời gian hằng số
        return hash_equals($storeHash, sha1($password))
            || hash_equals($storeHash, $this->getPassHash($token, $password));
    }

    /**
     * @param  string $storeHash
     * @return bool
     */
    public function needsRehash($storeHash) {
        if (!$this->isModernHash($storeHash)) {
            return true;
        }
        return password_needs_rehash($storeHash, PASSWORD_DEFAULT);
    }

    /**
     * Nâng hash cũ lên bcrypt sau khi mật khẩu đã được xác thực đúng.
     *
     * @param int|string $id_user
     * @param string     $password  Mật khẩu thô vừa xác thực
     * @param string     $storeHash Hash hiện tại trong DB
     */
    public function upgradePasswordIfNeeded($id_user, $password, $storeHash) {
        if (!$this->needsRehash($storeHash)) {
            return;
        }

        $this->reset();
        $this->setTable('#_user');
        $this->setWhere('id', (int) $id_user);
        $this->update(array('pass_hash' => $this->hashPassword($password)));
        $this->reset();
    }

    /** Hash bcrypt/argon2 luôn có dạng $2y$..., $argon2i$... */
    private function isModernHash($storeHash) {
        return strlen($storeHash) > 40 && $storeHash[0] === '$';
    }

    function clean($str) {
        $str = @trim($str);
        // get_magic_quotes_gpc() bị xoá từ PHP 8.0 nên phải kiểm tra tồn tại
        if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()) {
            $str = stripslashes($str);
        }
        return strip_tags($str);
    }

    function redirect($url=''){
        // Chuyển hướng bằng HTTP nếu chưa gửi output; nếu đã gửi thì đành dùng JS
        if (!headers_sent()) {
            header('Location: ' . $url, true, 302);
        } else {
            echo '<script>window.location = ' . json_encode($url) . ';</script>';
        }
        exit();
    }

    function link_redirect($alias = ''){
        $link_web = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $link_goc = URLPATH.$alias;

        if($link_web != $link_goc){
            $this->redirect($link_goc);
        }
    }
		
    function array_category($id_loai=0,$plit="=",$select_="",$module=0,$notshow=0) {
        // Nạp toàn bộ cây 1 lần rồi dựng đệ quy trong PHP.
        // Bản cũ chạy 1 query cho mỗi nhánh + 1 query num_rows() cho MỖI node.
        $rows = $this->o_fet("select id, id_loai, ten, module from cf_code order by so_thu_tu asc, id desc");

        $childrenMap = array();
        foreach ($rows as $row) {
            $childrenMap[(string) $row['id_loai']][] = $row;
        }

        return $this->buildCategoryOptions($childrenMap, (int) $id_loai, $plit, $select_, (int) $module, (int) $notshow);
    }

    /**
     * Dựng chuỗi <option> cho cây danh mục (đệ quy trên mảng đã nạp sẵn).
     *
     * @param  array  $childrenMap [id_loai => danh sách con]
     * @param  int    $parentId
     * @param  string $plit        Tiền tố thụt lề
     * @param  mixed  $selected    id đang được chọn
     * @param  int    $module      >0: chỉ lấy danh mục thuộc module này
     * @param  int    $notshow     id cần loại khỏi danh sách
     * @return string
     */
    private function buildCategoryOptions($childrenMap, $parentId, $plit, $selected, $module, $notshow) {
        if (empty($childrenMap[(string) $parentId])) {
            return '';
        }

        $prefix = ($parentId === 0) ? '' : $plit . '= ';
        $str    = '';

        foreach ($childrenMap[(string) $parentId] as $item) {
            if ($notshow > 0 && (int) $item['id'] === $notshow) {
                continue;
            }

            if ($module === 0 || (int) $item['module'] === $module) {
                $isSelected = ((string) $item['id'] === (string) $selected) ? " selected='selected'" : '';
                $str .= "<option value='" . $item['id'] . "'" . $isSelected . ">"
                    . $prefix . ' ' . $item['ten'] . "</option>";
            }

            $str .= $this->buildCategoryOptions($childrenMap, (int) $item['id'], $prefix, $selected, $module, $notshow);
        }

        return $str;
    }

    function getIdsub($id_code) {
        $lis_id = '';
        $query = $this->o_fet("select * from cf_code where id_loai= $id_code");
        foreach ($query as $key => $value) {
           $lis_id.=','.$value['id'];
           $query2 = $this->o_fet("select * from cf_code where id_loai= ".$value['id']);
           if(count($query2)>0){
               $lis_id.= $this->getIdsub($value['id']);
           }
        }
        return  $lis_id;
    }


    function getRootParent($id){
        $query = $this->o_fet("SELECT * FROM cf_code WHERE id = $id");
        if (empty($query)) {
            return false;
        }

        $category = $query[0];

        if (empty($category['id_loai']) || $category['id_loai'] == 0) {
            return $category;
        }

        return $this->getRootParent($category['id_loai']);
    }
    

    public function checkPermission($id_user, $id_page) {
        if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
            return 1;
        }

        // $id_page có thể là danh sách "1,2,3" -> ép từng phần tử về số nguyên
        $ids = $this->intList($id_page);
        if ($ids === '') {
            return 0;
        }

        $query = $this->safeFetch(
            "select id from #_user_permission_group where id_user = ? and id_permission in ($ids) limit 1",
            array((int) $id_user)
        );

        return count($query) > 0 ? 1 : 0;
    }

    public function checkPermission_view($id_page) {
        return $this->hasPermissionAction($id_page, array('1', '2', '3'));
    }
                
    public function checkPermission_edit($id_page) {
        return $this->hasPermissionAction($id_page, array('2', '3'));
    }
                
    public function checkPermission_dele($id_page) {
        return $this->hasPermissionAction($id_page, array('3'));
    }

    /**
     * Kiểm tra user hiện tại có ít nhất một trong các quyền $actions trên trang.
     *
     * Cột `action` lưu chuỗi ghép các mã quyền (vd '123'), nên vẫn phải dùng
     * LIKE — nhưng giá trị được bind thay vì nối chuỗi.
     *
     * @param  int|string $id_page
     * @param  array      $actions Mã quyền: 1=xem, 2=sửa, 3=xoá
     * @return int 1|0
     */
    private function hasPermissionAction($id_page, array $actions) {
        if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
            return 1;
        }

        $conditions = array();
        $params     = array((int) @$_SESSION['id_user'], (int) $id_page);

        foreach ($actions as $action) {
            $conditions[] = 'action like ?';
            $params[]     = '%' . $action . '%';
        }

        $query = $this->safeFetch(
            "select id from #_user_permission_group"
            . " where id_user = ? and id_permission = ?"
            . " and (" . implode(' or ', $conditions) . ") limit 1",
            $params
        );

        return count($query) > 0 ? 1 : 0;
    }

    /**
     * Ép chuỗi "1, 2, abc" thành "1,2" — an toàn để nhúng vào IN (...).
     *
     * @param  mixed $list
     * @return string Rỗng nếu không có số hợp lệ
     */
    private function intList($list) {
        $ids = array();

        foreach (explode(',', (string) $list) as $part) {
            $part = trim($part);
            if ($part !== '' && ctype_digit($part)) {
                $ids[] = (int) $part;
            }
        }

        return implode(',', $ids);
    }

    function getProduct($id_code, $col='*') {
        $row = $this->simple_fetch("select $col from #_sanpham where id_code = $id_code and hien_thi = 1 "._where_lang."");
        return $row;
    }

    function getNameUser($id_user){
       $name = '';
       $row = $this->simple_fetch("select ho_ten from #_user where id=".$id_user."");
       if(!empty($row)){
          $name = $row['ho_ten'];
       }
       return $name;
    }

    function getCate($id_code, $col='*') {
        $row = $this->simple_fetch("select $col from #_category where id_code = $id_code and hien_thi = 1 "._where_lang."");
        if($col=='*'){
            return $row;
        }else{
            return $row[$col];
        }
        
    }

    function getContent($id_code, $col='') {
        if($col==''){
            $row = $this -> simple_fetch("select * from #_category_noidung where hien_thi = 1  and id_code = $id_code "._where_lang."");
            return $row;
        }else{
            $row = $this -> simple_fetch("select $col from #_category_noidung where hien_thi = 1  and id_code = $id_code "._where_lang."");
            return $row[$col];
        }
    }

    function getContents($id_code,$limit='') {
        if($limit!=''){
            $where = " limit 0,".$limit;
        }else{
            $where = "";
        }
        $row = $this -> o_fet("select * from #_content where hien_thi = 1 and id_loai = $id_code "._where_lang." order by so_thu_tu ASC, id DESC $where");
        return $row;
    }

    function getContent_id($id_code,$limit='') {
        if($limit!=''){
            $where = " limit 0,".$limit;
        }else{
            $where = "";
        }
        $row = $this -> simple_fetch("select * from #_content where hien_thi = 1 and id_code = $id_code "._where_lang." order by so_thu_tu ASC, id DESC $where");
        return $row;
    }
    
    function getData($tale, $col='', $where='', $limit='') {
        if($limit!=''){
            $limited = 'limit 0,'.$limit; 
        }else{
            $limited="";
        }
        if($col!=''){
            $col_txt = $col;
        }else{
            $col_txt='*';
        }
        if($where!=""){
           $where_txt = " and $where"; 
        }else{
            $where_txt='';
        }
        $row = $this -> o_fet("select $col_txt from #_".$tale." where hien_thi = 1 $where_txt order by so_thu_tu ASC, id DESC $limited");
        return $row;
    }
    function getTinh($col='*', $id='') {
        if($id==''){
            $row = $this -> o_fet("select $col from #_thanhpho order by ten ASC ");
        }else{
            $row = $this -> simple_fetch("select $col from #_thanhpho where code= '".$id."' order by ten ASC ");
        }
        return $row;
    }
    function getHuyen($code_tinh, $col='*', $code='') {
        if($code==''){
            $row = $this -> o_fet("select $col from #_huyen where code_tinh ='".$code_tinh."' order by ten ASC ");
        }else{
            $row = $this -> simple_fetch("select $col from #_huyen where code_tinh ='".$code_tinh."' and code= '".$code."' order by ten ASC ");
        }
        return $row;
    }
    function getXa($code_huyen, $col='*', $code='') {
        if($code==''){
            $row = $this -> o_fet("select $col from #_xa where code_huyen ='".$code_huyen."' order by ten ASC ");
        }else{
            $row = $this -> simple_fetch("select $col from #_xa where code_huyen ='".$code_huyen."' and code='".$code."' order by ten ASC ");
        }
        return $row;
    }
    
    function getDataId($tale, $id_code, $col='*') {
        $row = $this -> simple_fetch("select $col from #_".$tale." where id_code = $id_code  limit 0,1");
        return $row;
    }
    function getTxt($idOrCode) {
        static $cache = array();
        $key = is_numeric($idOrCode) ? ('id:' . (int) $idOrCode) : ('code:' . (string) $idOrCode);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        if (is_numeric($idOrCode)) {
            $row = $this->simple_fetch('select text from #_text where id = ' . (int) $idOrCode . ' limit 1');
        } else {
            $code = addslashes(trim((string) $idOrCode));
            if ($code === '') {
                return $cache[$key] = '';
            }
            $row = $this->simple_fetch("select text from #_text where code = '" . $code . "' limit 1");
        }
        if (!is_array($row) || empty($row['text'])) {
            return $cache[$key] = '';
        }
        $arr = json_decode($row['text'], true);
        if (!is_array($arr)) {
            return $cache[$key] = '';
        }
        $lang = defined('_lang') ? _lang : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'vi');
        if (isset($arr[$lang]) && $arr[$lang] !== '' && $arr[$lang] !== null) {
            return $cache[$key] = $arr[$lang];
        }
        if (isset($arr['vi']) && $arr['vi'] !== '' && $arr['vi'] !== null) {
            return $cache[$key] = $arr['vi'];
        }
        foreach ($arr as $v) {
            if ($v !== '' && $v !== null) {
                return $cache[$key] = $v;
            }
        }
        return $cache[$key] = '';
    } 
    
    function showthongtin($data='') {
        $url = URLPATH;
        $mxh        =   $this->simple_fetch("select * from #_thongtin where lang = '".$_SESSION['lang']."'");
        if($data==''){
            return $mxh;
        }else{
            if($data=='logo'){
                $text = $url.'media/images/'.$mxh['icon_share'];
            }elseif($data=='favicon'){
                $text = $url.'media/images/'.$mxh['favicon'];
            }elseif($data=='backlink'){
                $text = '<a href="http://phuongnamvina.vn/" target="_blank" title="Design Web: PhuongNamVina">Design Web: PhuongNamVina</a>';
            }else{
                $text = $mxh[$data];
            }
            return $text;
        }
        
        
    }

    /**
     * Xoá cookie đăng nhập admin (key_ad).
     * Không xoá toàn bộ cookie trình duyệt.
     */
    function cookie_destroy(){
        setrawcookie('key_ad', '0', time() - 3600, '/', null, null, true);
        if (isset($_COOKIE['key_ad'])) {
            unset($_COOKIE['key_ad']);
        }
    }

    function getYoutubeIdFromUrl($url) {
        $parts = parse_url($url);
        if(isset($parts['query'])){
            parse_str($parts['query'], $qs);
            if(isset($qs['v'])){
                return $qs['v'];
            }else if($qs['vi']){
                return $qs['vi'];
            }
        }
        if(isset($parts['path'])){
            $path = explode('/', trim($parts['path'], '/'));
            return $path[count($path)-1];
        }
        return false;
    }

     function randomStringAlias($length) {
        $str ='';
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $size = strlen( $chars );
        for( $i = 0; $i < $length; $i++ ) {
        $str .= $chars[ rand( 0, $size - 1 ) ];
        }
        return $str;
    }

    // function xử lý alias
    function createAlias($alias){

            $link = $alias;
            
            // xử lý ký tự đặc biệt [^A-Za-z0-9\-]
            $link = str_replace(' ', '-',$link);

            $link = preg_replace('/[^A-Za-z0-9\-]/', '', $link);

            // xử lý độ dài của chuỗi
            if(!empty($link) && strlen($link) > 100){
                
                $link = substr($alias,0,50)."-".$this->randomStringAlias(6);
            }
            
            // link quá ngắn
            if(strlen($link) <= 3){

                $link .= $this->randomStringAlias(6);

            }

            // xử lý rỗng
            if($link == ''){

                $link = "link-".$this->randomStringAlias(10);

            }
            
            return $link;
        }

    /**
     * Làm sạch chuỗi KHÔNG cho phép HTML (tên, tiêu đề, ô nhập 1 dòng...).
     *
     * Bản cũ xoá luôn ( ) ' " ? [ ] nên làm SAI dữ liệu:
     * "Công ty (TNHH)" -> "Công ty TNHH". Nay chỉ bỏ thẻ HTML và ký tự
     * điều khiển; việc chống XSS thuộc về lúc XUẤT ra HTML (dùng e() /
     * htmlspecialchars), không phải lúc nhận dữ liệu vào.
     *
     * @param  string $string
     * @return string
     */
    function xss_filter($string){
        $string = strip_tags((string) $string);
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        return trim($string);
    }

    /**
     * Làm sạch nội dung CÓ cho phép HTML (bài viết, mô tả từ CKEditor).
     *
     * Giữ lại thẻ định dạng, chỉ loại bỏ phần có thể thực thi mã:
     * thẻ script/iframe/object, thuộc tính on*, javascript:, data:.
     *
     * @param  string $string
     * @return string
     */
    function xss_filterContent($string){
        $string = (string) $string;

        $string = preg_replace('#<(script|iframe|object|embed|applet|base|form)\b.*?</\1\s*>#is', '', $string);
        $string = preg_replace('#<(script|iframe|object|embed|applet|base|form)\b[^>]*/?>#is', '', $string);
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $string);

        // clear() lo phần on*, javascript:, data:
        return $this->clear($string);
    }

    function buildMenuTree($elements, $parentId = 0) {
        $branch = array();
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildMenuTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    /**
     * Lấy cây menu theo ID của menu
     */
    function getMenuTreeById($menu_id) {
        if (!$menu_id) return [];        
        $items = $this->o_fet("select * from #_menu_items where menu_id = ".(int)$menu_id." order by sort_order asc, id asc"); 
        return $this->buildMenuTree($items);
    }

    /**
     * Lấy cây menu theo Vị trí (Location) và Ngôn ngữ
     * Ví dụ: $d->getMenuByLocation('primary', 'vi');
     */
    function getMenuByLocation($location_name, $lang = 'vi') {
        $row = $this->simple_fetch("select menu_id from #_menu_locations where location_name = '$location_name' and lang = '$lang'");
        
        if (!empty($row) && $row['menu_id'] > 0) {
            return $this->getMenuTreeById($row['menu_id']);
        }

        return [];
    }

}

?>