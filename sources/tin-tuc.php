<?php
    $id_loai = $row['id_code'].$d->getIdsub($row['id_code']);
    $root_parent = $d->getRootParent($row['id_code']);
    $root_id = !empty($root_parent) ? $root_parent['id'] : 0;

    $limit = (int)get_json('posts', 'paging');
    if ($limit <= 0) $limit = 12;

    $total_posts = count($d->o_fet("select id from #_tintuc where id_loai in ($id_loai) and hien_thi = 1 "._where_lang));

    $url_params = getQueryParams();
    $page = isset($url_params['page']) ? (int)$url_params['page'] : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    $allPost = $d->o_fet("select * from #_tintuc where id_loai in ($id_loai) and hien_thi = 1 "._where_lang." order by so_thu_tu asc, id desc limit $offset, $limit");

?>


<?php if ($row['id_code'] == 195): ?>
    <?php include _source."tin-tuc-thu-vien.php"; ?>
<?php else: ?>
    <?php include _source."tin-tuc-simple.php"; ?>
<?php endif ?>