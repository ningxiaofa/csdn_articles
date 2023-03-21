<?php
header("Content-Type: application/force-download");
header("Content-Disposition: attachment; filename=csdn_blog_articles.zip"); 

$zip = new ZipArchive();
$zip_filename = 'csdn_blog_articles.zip';
$zip->open($zip_filename, ZipArchive::CREATE);

$access_token = ''; // 授权Token
$username = ''; // CSDN用户名
$pageSize = 20; // 每页大小
$page = 1;
$count = 0;

while (true) {
    $list_url = sprintf('https://mp.csdn.net/apis/sdkearner/blogservice/list?access_token=%s&username=%s&pageSize=%d&currentPage=%d', $access_token, $username, $pageSize, $page);
    $list_content = file_get_contents($list_url);
    $list_data = json_decode($list_content, true);
    $total_page = ceil($list_data['data']['totalRow'] / $pageSize);

    foreach ($list_data['data']['dtoList'] as $item) {
        $detail_url = sprintf('https://blog.csdn.net/article/details/%s', $item['id']);
        $detail_content = file_get_contents($detail_url);

        preg_match('/<title>(.*?)<\/title>/', $detail_content, $matches);
        $article_title = $matches[1];
        $article_filename = iconv('UTF-8', 'gbk', $article_title).'.txt'; // 将文章标题作为文件名，保留文件名中文

        preg_match('/<div id="content_views" class=".*?">(.*?)<\/div>/s', $detail_content, $matches);
        $article_content = $matches[1];
        $article_content = preg_replace('/<pre[\s\S+]*?(?=<\/pre>)<\/pre>/', '', $article_content); // 处理代码块，去除语言声明

        $article = $article_title."\n\n".$article_content;
        file_put_contents($article_filename, $article);
        $zip->addFile($article_filename);
        unlink($article_filename); //删除临时文件
        $count++;
    }

    if ($page >= $total_page) {
        break;
    } else {
        $page++;
    }
}

$zip->close();
readfile($zip_filename);
unlink($zip_filename);

// 备注：在 PHP 脚本开头的 $access_token 和 $username 变量中，您需要填写您自己的 CSDN 授权 Token 和用户名。
// 您可以在 https://mp.csdn.net/console/apitoken 页面中获取 Token。