<?php
class func_index{
    // Cấu hình dùng chung cho Singleton
    public static $shared_config = null;
    public static $shared_db = null;

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

    // Thuộc tính phục vụ Query Builder (Mới)
    protected $qb_where = [];
    protected $qb_order = [];
    protected $qb_limit = "";
    protected $qb_with = [];

    function __construct($config = array()){
        // Lưu config vào static nếu có, dùng cho các Model sau này
        if (!empty($config)) self::$shared_config = $config;

        if(!empty($config) || !empty(self::$shared_config))
        {
           $conf = !empty($config) ? $config : self::$shared_config;
           $this->init($conf);
           $this->connect();
        }
    }

    function init($config = array()){
        foreach($config as $k=>$v)
            $this->$k = $v;
    }

    function connect(){
        if (self::$shared_db !== null) {
            $this->db = self::$shared_db;
            return;
        }

        try {
            $this->db = new PDO("mysql:host=$this->servername;dbname=$this->database;charset=utf8", $this->username, $this->password);
            // set the PDO error mode to exception
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec("set names utf8");
            
            // Lưu lại dùng chung
            self::$shared_db = $this->db;

        }
        catch(PDOException $e){
            echo "Connection failed: " . $e->getMessage();
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
        return $this->rawQuery();
    }

    function rawQuery($sql){
        $this->sql = str_replace('#_', $this->refix, $sql);
        $stmt = $this->db->prepare($this->sql); 
        return $stmt->execute();
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
        $into = "";
        $values = "";
        foreach ($data as $int => $val) {
                $into .= ",".$int;
                $values .= ",'".$val."'";
        }
        if($into[0] == ",") $into[0] = "(";
        $into .=")";
        if($values[0] == ",") $values[0] = "(";
        $values .= ")";

        $this->sql = "insert into ".$this->table.$into." values ".$values;			
        $this->sql = str_replace('#_', $this->refix, $this->sql);

        $stmt = $this->db->prepare($this->sql); 
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    function update($data = array()){
        $values = "";
        foreach ($data as $col => $val) {
                $values .= ",".$col." = '".$val."' ";
        }
        if($values[0] == ",") $values[0] = " ";
        $this->sql = "update ".$this->table." set ".$values.$this->where;

        $this->sql = str_replace('#_', $this->refix, $this->sql);
        $this->result = $this->rawQuery($this->sql);
        return $this->result;
    }


    function delete(){
        $this->sql = "delete from ".$this->table.$this->where;
        $this->sql = str_replace('#_', $this->refix, $this->sql);
        return $this->rawQuery($this->sql);
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
        $query=$this->o_fet("select * from #_category where id_loai=$id and hien_thi=1 order by so_thu_tu asc, id desc");
        if(count($query>0)) {
            foreach($query as $item) {
                $str.=",".$item['id'];
                $check=$this->o_fet("select * from #_category where id_loai={$item['id']} and hien_thi=1 order by so_thu_tu asc, id desc");
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

    function clean($str) {
        $str = @trim($str);
        if(get_magic_quotes_gpc()) {
        $str = stripslashes($str);
        }
        return strip_tags($str);
    }

    function redirect($url=''){
        echo '<script language="javascript">window.location = "'.$url.'" </script>';
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
        $str="";
        $and = ($notshow>0) ? " and id!=$notshow" : '';

        if($id_loai==0) {
            $query = $this->o_fet("select * from cf_code where id_loai=0 $and order by so_thu_tu asc, id desc");	
            echo $d->sql;
            $plit="";
        }
        else {
            $query = $this->o_fet("select * from cf_code where id_loai=$id_loai $and order by so_thu_tu asc, id desc");
            echo $d->sql;
            $plit.="= ";
        }

        foreach($query as $item) {	
            if($item['id']==$select_){ $selected="selected='selected'";} else{ $selected="";}
            if($module>0) {
                if($item['module']==$module) {
                    $str.="<option value='".$item['id']."' ".$selected.">".$plit." ".$item['ten']."</option>";
                }
            }
            else {
                $str.="<option value='".$item['id']."' ".$selected.">".$plit." ".$item['ten']."</option>";
            }

            $check_sub = $this->num_rows("select * from cf_code where id_loai='{$item['id']}'");

            if($check_sub>0) {
                $str.=$this->array_category($item['id'],$plit,$select_,$module,$notshow);
            }
        }
        return $str;
    }

    function getIdsub($id_code) {
        //$lis_id .= $id_code;
        $query = $this->o_fet("select * from cf_code where id_loai= $id_code");
        foreach ($query as $key => $value) {
           $lis_id.=','.$value['id'];
           $query2 = $this->o_fet("select * from cf_code where id_loai= ".$value['id']);
           if(count($query2)>0){
               $lis_id.= CategoryModel::query()->getChildrenIds($value['id']);
           }
        }
        return  $lis_id;
    }

    public function checkPermission($id_user, $id_page) {
        if($_SESSION['is_admin']==1){
            return 1;
        }else{
            $query = $this->o_fet("select * from #_user_permission_group where id_user = $id_user and id_permission in ($id_page)");
            if(count($query)>0){
                return 1;
            }else{
                return 0;
            }
        }
    }
    public function checkPermission_view($id_page) {
        if($_SESSION['is_admin']==1){
            return 1;
        }else{
            $query = $this->o_fet("select * from #_user_permission_group where id_user = ".$_SESSION['id_user']." and id_permission = $id_page and (action like '%1%' or action like '%2%' or action like '%3%') ");
            if(count($query)>0){
                return 1;
            }else{
                return 0;
            }
        }
    }
                
    public function checkPermission_edit($id_page) {
        if($_SESSION['is_admin']==1){
            return 1;
        }else{
            $query = $this->o_fet("select * from #_user_permission_group where id_user = ".$_SESSION['id_user']." and id_permission = $id_page and (action like '%3%' or action like '%2%')");
            if(count($query)>0){
                return 1;
            }else{
                return 0;
            }
        }
    }
                
    public function checkPermission_dele($id_page) {
        if($_SESSION['is_admin']==1){
            return 1;
        }else{
            $query = $this->o_fet("select * from #_user_permission_group where id_user = ".$_SESSION['id_user']." and id_permission = $id_page and (action like '%3%')");
            if(count($query)>0){
                return 1;
            }else{
                return 0;
            }
        }
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
    function getTxt($id) {
        return \TextModel::translate($id);
    } 
    
    function showthongtin($data='') {
        $url = URLPATH;
        $mxh        =   $this->simple_fetch("select * from #_thongtin where lang = '".$_SESSION['lang']."'");
        if($data==''){
            return $mxh;
        }else{
            if($data=='logo'){
                $text = $url.'img_data/images/'.$mxh['icon_share'];
            }elseif($data=='favicon'){
                $text = $url.'img_data/images/'.$mxh['favicon'];
            }elseif($data=='backlink'){
                $text = '<a href="http://phuongnamvina.vn/" target="_blank" title="Design Web: PhuongNamVina">Design Web: PhuongNamVina</a>';
            }else{
                $text = $mxh[$data];
            }
            return $text;
        }
        
        
    }

    function cookie_destroy(){

       if (isset($_SERVER['HTTP_COOKIE'])) {

            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);

            foreach($cookies as $cookie) {

                $parts = explode('=', $cookie);

                $name = trim($parts[0]);

                setcookie($name, '', time()-1000);

                setcookie($name, '', time()-1000, '/');

            }
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

        // xss filter
        function xss_filter($string){

                $string = strip_tags($string);
                
                //Remove any non-printable characters
                $string = preg_replace('/[\x00-\x1F\x7F]/', '', $string);
         
                //Remove any characters that are not allowed in HTML
                $string = preg_replace('/[<>\?\'\"\(\)\[\]]/', '', $string);
         
                // Remove any characters that are used for XSS attacks
                $string = str_replace(['<', '>', '\'', '\"', ')', '('], '', $string);
          
                $string = preg_replace('#<script(.*?)>(.*?)</script>#is', '',$string);
         
                return $string;
        }

        // xss filter
        function xss_filterContent($string){

                //$string = strip_tags($string);
                
                //Remove any non-printable characters
                $string = preg_replace('/[\x00-\x1F\x7F]/', '', $string);
         
                //Remove any characters that are not allowed in HTML
                $string = preg_replace('/[<>\?\'\"\(\)\[\]]/', '', $string);
         
                // Remove any characters that are used for XSS attacks
                $string = str_replace(['<', '>', '\'', '\"', ')', '('], '', $string);
          
                $string = preg_replace('#<script(.*?)>(.*?)</script>#is', '',$string);
         
                return $string;
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

    /**
     * Tự động nạp dữ liệu quan hệ (Eager Loading) từ bảng khác
     * Giúp tránh lỗi N+1 Query và không cần JOIN phức tạp.
     */
    public function loadRelation($data, $table, $foreignKey, $relationKey = 'id_code', $alias = '', $isMultiple = true) {
        if (empty($data)) return $data;
        
        // Nếu không đặt alias, lấy tên bảng làm định danh
        if ($alias == '') $alias = str_replace('#_', '', $table);
        
        // Lấy danh sách ID duy nhất cần truy vấn
        $ids = array_unique(array_filter(array_column($data, $foreignKey)));
        if (empty($ids)) {
            foreach ($data as &$item) {
                $item[$alias] = $isMultiple ? [] : null;
            }
            return $data;
        }

        $idStr = implode(',', $ids);
        $relations = $this->o_fet("SELECT * FROM $table WHERE $relationKey IN ($idStr)");
        
        // Nhóm dữ liệu để tìm kiếm nhanh
        $relMap = [];
        foreach ($relations as $rel) {
            if ($isMultiple) {
                $relMap[$rel[$relationKey]][] = $rel;
            } else {
                $relMap[$rel[$relationKey]] = $rel;
            }
        }
        
        // Gán ngược dữ liệu vào mảng gốc
        foreach ($data as &$item) {
            $item[$alias] = $relMap[$item[$foreignKey]] ?? ($isMultiple ? [] : null);
        }
        
        return $data;
    }


}
/* KẾT THÚC class func_index */
