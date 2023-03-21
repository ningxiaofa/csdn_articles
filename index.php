<?php
header("Content-Type: application/force-download");
header("Content-Disposition: attachment; filename=csdn_blog_articles.zip");

$zip = new ZipArchive();
$zip_filename = 'csdn_blog_articles.zip';
$zip->open($zip_filename, ZipArchive::CREATE);

$username = 'william_n';
$url_base = "https://blog.csdn.net/$username";

$page = 1;
$count = 0;
$articles = [];

while (true) {
    $html = file_get_contents($url_base.'/article/list/'.$page++);
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