<?php

// 使用 PHP 脚本逐个导出 CSDN 中个人的所有博客，每个博客导出为一个文件，使用多进程方式。

// 实现步骤：
// 1.使用 curl 获取 CSDN API 数据； 
// 2.根据 API 数据获得博客列表； 
// 3.循环遍历博客列表，使用多进程方式，获取并导出每个博客为一个文件，保存到本地文件夹中。

// 代码实现大致如下：

// 1.获取 API 数据
function curl_get($url, $headers = array(), $useCookie = false, $cookie = '')
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_AUTOREFERER => true,
        CURLOPT_HTTPHEADER => $headers,
    ));

    if ($useCookie) {
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);
    }

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}

// 2.获取博客列表
function getUserBlogList($username)
{
    $baseUrl = 'https://blog.csdn.net/';
    $url = $baseUrl . $username . '/article/list/';

    $htmlContent = curl_get($url);

    $regex = '/<div\sclass="article-list">.*<\/div>/si';

    preg_match_all($regex, $htmlContent, $match);

    $articleList = array();

    if (isset($match[0][0])) {
        $articleList = getArticleListFromHtml($match[0][0], $baseUrl . $username);
    }

    return $articleList;
}

// 3.多进程下载保存
function downloadArticles($username, $articleList, $processCount)
{
    for ($i = 0; $i < $processCount; $i++) {
        $processes[$i] = array();
    }

    $i = 0;
    foreach ($articleList as $article) {
        if (!empty($article['link'])) {
            $processIndex = $i % $processCount;

            $processes[$processIndex][] = $article;
            $i++;
        }
    }

    for ($i = 0; $i < $processCount; $i++) {
        $pid = pcntl_fork();

        if ($pid == -1) {
            die("创建子进程失败");
        } elseif ($pid == 0) {
            set_time_limit(0);

            foreach ($processes[$i] as $article) {
                $articleId = substr($article['link'], strrpos($article['link'], '/') + 1);
                $blogUrl = 'https://blog.csdn.net/' . $username . '/article/details/' . $articleId;
                $content = curl_get($blogUrl);

                $doc = new DOMDocument();
                @$doc->loadHTML($content);
                $xpath = new DOMXPath($doc);

                $articleTitle = trim($xpath->query('//h1')->item(0)->nodeValue);
                $articleContentNode = $xpath->query('//div[@id="article_content"]')->item(0);
                $articleContentHtml = $doc->saveHTML($articleContentNode);

                if (!empty($articleTitle) && !empty($articleContentHtml)) {
                    $fileContent = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . $articleTitle . '</title></head><body>' . $articleContentHtml . '</body></html>';
                    file_put_contents($articleTitle . '.html', $fileContent);
                }
            }

            exit(0);
        }
    }

    while (pcntl_waitpid(0, $status) != -1) {
        $status = pcntl_wexitstatus($status);
    }
}

// 该函数的目的是从博客列表html字符串中解析出每篇博客的链接、标题、摘要等信息
function getArticleListFromHtml($html, $baseUrl)
{
    $articleList = array();

    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    $articleNodes = $xpath->query('//div[@class="article-item-box csdn-tracking-statistics"]');

    if ($articleNodes->length == 0) {
        return $articleList;
    }

    foreach ($articleNodes as $articleNode) {
        $link = $xpath->query('.//a[@class="title"]', $articleNode)->item(0);
        $title = $link->nodeValue;
        $url = $link->getAttribute('href');
        $url = $baseUrl . substr($url, strrpos($url, '/') + 1);

        $summaryNode = $xpath->query('.//p[@class="content"]', $articleNode)->item(0);
        $summary = $summaryNode ? $summaryNode->nodeValue : '';

        $publishTimeNode = $xpath->query('.//span[@class="date"]', $articleNode)->item(0);
        $publishTime = $publishTimeNode ? $publishTimeNode->nodeValue : '';

        $articleList[] = array(
            'link' => $url,
            'title' => $title,
            'summary' => $summary,
            'publishTime' => $publishTime,
        );
    }

    return $articleList;
}