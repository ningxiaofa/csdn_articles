<?php
header("Content-Type: application/force-download");
header("Content-Disposition: attachment; filename=csdn_blog_articles.zip");

$username = 'william_n';
$url_base = "https://blog.csdn.net/$username/article/list/";
$dir_name = $username.'_blog_articles';

if (!file_exists($dir_name)) {
    mkdir($dir_name);
}

$zip = new ZipArchive();
$zip_filename = 'csdn_blog_articles.zip';
$zip->open($zip_filename, ZipArchive::CREATE);

$page = 1;
$count = 0;
$articles = [];

while (true) {
    $html = file_get_contents($url_base . $page++);
    var_dump($html);
exit;
    if (!$html) {
        break;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);
    $entries = $xpath->query("//h4[@class='text-truncate']/a");

    foreach ($entries as $entry) {
        $url = $entry->getAttribute("href");
        
        $article_html = file_get_contents($url);
        $article_dom = new DOMDocument();
        @$article_dom->loadHTML(mb_convert_encoding($article_html, 'HTML-ENTITIES', 'UTF-8'));
        $article_xpath = new DOMXPath($article_dom);
        $article_body = $article_xpath->query("//div[@class='md-article-title']")->item(0);
        $article_title = trim($article_body->nodeValue);
        $article_filename = iconv('UTF-8', 'gbk', $article_title).'.txt'; // 将文章标题作为文件名，保留文件名中文
        $article_content = $article_xpath->query("//div[@class='markdown_views']")->item(0);
        $article = $article_title."\n\n".$article_content->nodeValue;
        file_put_contents($article_filename, $article);
        $zip->addFile($article_filename);
        unlink($article_filename); //删除临时文件
        $count++;
    }
}

$zip->close();
readfile($zip_filename);
unlink($zip_filename);

// 此代码会将 CSDN 个人主页中所有博客都单独导出为一个文件，并将所有文件打包成一个 Zip 文件，最后下载该 Zip 文件, 位于以username目录下.
// 请将$username变量替换为您自己的 CSDN 用户名，然后运行脚本即可。

// 执行时间太久了，会time out, 需要改造!!!
