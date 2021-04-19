#!/usr/bin/env php
<?php

use GitWrapper\GitWorkingCopy;

require 'vendor/autoload.php';

$config = require 'config.php';
$author = $config['author'];

/**
 * 命令行参数list
 */
$help = function () {
    $string =  <<<HELP
    \033[36m Usage \033[0m:
        php week project-path since until

    \033[36m Example \033[0m:
        php week ~/web/ 20210401 20210407

    \033[36m Params mean \033[0m:
        \033[33m project-path \033[0m: 项目地址的上级目录
        \033[33m since \033[0m: 开始时间
        \033[33m until \033[0m: 结束时间

                   _    _                                       _   
__      _____  ___| | _| |_   _       _ __ ___ _ __   ___  _ __| |_ 
\ \ /\ / / _ \/ _ \ |/ / | | | |_____| '__/ _ \ '_ \ / _ \| '__| __|
 \ V  V /  __/  __/   <| | |_| |_____| | |  __/ |_) | (_) | |  | |_ 
  \_/\_/ \___|\___|_|\_\_|\__, |     |_|  \___| .__/ \___/|_|   \__|
                          |___/               |_|                   

An Weekly report generate for PHP

Version: 0.0.1
    \n
HELP;

    die($string);
};

/**
 * 获取参数
 */
if (count($argv) < 4) {
    $help();
}

$projects = getProjects($argv[1]);

if (!$projects) {
    toast("ERROR: your path {$argv[1]} no exist git projects", 'error', true);
}

// 开始时间 ~ 结束时间
$since = date('Y-m-d', strtotime($argv[2]));
$until = date('Y-m-d', strtotime($argv[3]));

/**
 * 初始化
 */
date_default_timezone_set('PRC');
$gitWrapper = new GitWrapper\GitWrapper();
$content = '';
$content .= "### {$since} ~ {$until} 周报" . PHP_EOL;

foreach ($projects as $key => $project) {
    /**
     * 初始化配置
     */
    $projectPath = $project['path'];
    $projectUrl = $project['url'] ?? '';

    $gitCommand = sprintf(
        'log --pretty=format:%%h|%%ai|%%s --branches --no-merges --author=%1$s --since=%2$s --until=%3$s --reverse --shortstat -- . "%4$s"',
        $author,
        $since,
        $until,
        $project['pathspec'] ?? '.'
    );
//    echo $gitCommand . PHP_EOL;

    /**
     * Git log
     */
    $fullText = $gitWrapper->git(
        $gitCommand,
        $projectPath
    );

    if (empty($fullText)) {
        continue;
    }

    /**
     * 表头
     */
    $table = [];
    $table[] = ['时间', '内容', '工作量'];

    /**
     * 分离提交日志
     */
    $logs = explode("\n\n", $fullText);

    /**
     * 提取表格
     */
    $widths = [];
    foreach ($logs as $log) {
        $lines = explode("\n", $log);
        $pieces = explode('|', $lines[0]);
        $stat = translate_file_changes($lines[1]);

        $commitId = $pieces[0];
        $datetime = date('Y年m月d日 H点i分', strtotime($pieces[1]));
        $message = $pieces[2];
        
        if (is_callable($projectUrl)) {
            $projectUrl = $projectUrl($projectPath);
        }

        if (!empty($projectUrl)) {
            $link = "$projectUrl/commit/$commitId";
            $message = "[$message]($link)";
        }

        $row = [$datetime, $message, $stat];
        foreach ($row as $index => $col) {
            $width = mb_strwidth($col); // 计算字符串宽度
            if (!isset($widths[$index]) || $width > $widths[$index]) {
                $widths[$index] = $width; // 保留列最大宽度
            }
        }

        $table[] = $row;
    }

    /**
     * 拼接表格内容
     */

    $tableContent = '';
    foreach ($table as $index => $row) {
        $tableContent .= table_format_row($row, $widths) . PHP_EOL;

        if ($index == 0) { // 追加表头分割线
            $cols = array_map(function ($width) {
                return str_repeat('-', $width);
            }, $widths);
            $tableContent .= table_format_row($cols, $widths) . PHP_EOL;
        }
    }

    toast($project['name'], 'success');

    $content .= "- **{$project['name']}**" . PHP_EOL . PHP_EOL . $tableContent . PHP_EOL;
}
//echo trim($content, PHP_EOL) . PHP_EOL;

if (!is_dir("./posts")) {
    mkdir('./posts');
}

file_put_contents("./posts/week-{$argv[2]}-{$argv[3]}.md", trim($content, PHP_EOL));
