<?php
// 登录CSDN并保存Cookie到文件
function csdn_login($username, $password) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://passport.csdn.net/v1/register/pc/login/doLogin');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
        'username' => $username,
        'password' => $password,
    ));
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_exec($ch);
    curl_close($ch);
}

// 获取CSDN个人博客列表
function csdn_get_blogs($username) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://blog.csdn.net/$username");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $html = curl_exec($ch);
    curl_close($ch);

    $doc = new DOMDocument();
    // exit($html);
    $doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    $nodes = $xpath->query('//div[@class="article-list"]/div[@class="article-item-box"]/h4/a');

    $blogs = array();
    foreach ($nodes as $node) {
        $title = $node->nodeValue;
        $url = $node->getAttribute('href');
        $blogs[] = array('title' => $title, 'url' => $url);
    }

    return $blogs;
}

// 导出一篇博客并保存为一个文件
function csdn_export_blog($url, $filename) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $html = curl_exec($ch);
    curl_close($ch);

    $doc = new DOMDocument();
    $doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    $node = $xpath->query('//div[@class="article-content"]');
    $content = $node->item(0)->C14N();

    file_put_contents($filename, $content);
}

// 导出所有博客到指定目录下，使用多进程方式
function export_csdn_blogs($username, $password, $dir, $num_processes) {
    csdn_login($username, $password);
    $blogs = csdn_get_blogs($username);
    $blogs_chunked = array_chunk($blogs, ceil(count($blogs) / $num_processes));
    $pids = array();
    for ($i = 0; $i < $num_processes; ++$i) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new Exception('Could not create process');
        } elseif ($pid == 0) {  // 子进程
            foreach ($blogs_chunked[$i] as $blog) {
                $filename = $dir . '/' . str_replace('/', '／', $blog['title']) . '.html';
                csdn_export_blog($blog['url'], $filename);
            }
            exit(0);
        } else {  // 父进程
            $pids[] = $pid;
        }
    }
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }
}

// 这个示例将原函数拆分为三个小函数，并在导出博客时使用csdn_export_blog函数代替了原先在主函数内部编写的获取HTML内容和保存文件的代码。这样拆分后的函数更加简单易懂，也可以更方便地进行单元测试和代码复用。


// $username = 'your_username';
// $password = 'your_password';
// $dir = '/path/to/export/dir';
$username = 'william_n';
$password = 'Nxf+1158885641...';
$dir = './william_n';
$num_processes = 1;

try {
    export_csdn_blogs($username, $password, $dir, $num_processes);
    echo "Export succeeded.\n";
} catch (Exception $e) {
    echo "Export failed: " . $e->getMessage() . "\n";
}

// 这个示例中，将要导出的CSDN用户名、密码、导出目录和进程数量作为参数传递给export_csdn_blogs函数。在函数执行过程中，将会调用其他几个函数，包括csdn_login、csdn_get_blogs和csdn_export_blog。如果导出成功，将打印一条成功信息，否则将打印一条错误信息