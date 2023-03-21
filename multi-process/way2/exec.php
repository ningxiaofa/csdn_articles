<?php
require_once './functions.php';

$username = 'william_n';  // 修改为你的 CSDN 用户名
$articleList = getUserBlogList($username);
downloadArticles($username, $articleList, 16);  // 16 为进程数，可以根据需要自行调整

// 其中，getUserBlogList和downloadArticles函数分别用于获取博客列表和多进程下载博客内容并保存。
// 完整调用代码包括上面的定义函数和修改代码。
// 将上面的所有代码复制到一个PHP文件中，添加上面的调用代码到文件底部，然后替换$username为您的CSDN用户名，即可运行。
// 运行前确保安装了pcntl扩展，否则多进程相关函数无法使用，可能会导致程序无法正常运行。

// 检查pcntl扩展
// ➜  php php -m | grep pcntl
// pcntl
// ➜  php 