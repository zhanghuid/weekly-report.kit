<?php

/**
 * 转换 Git log 的 File changes 为中文
 *
 * @param string $stat
 * @return string
 */
function translate_file_changes($stat)
{
    $map = [
        'files' => '个文件',
        'file' => '个文件',
        ' changed' => '被改变',
        'insertions' => '行代码新增',
        'insertion' => '行代码新增',
        'deletions' => '行代码删除',
        'deletion' => '行代码删除',
        '(+)' => '',
        '(-)' => '',
    ];
    $stat = trim($stat);
    $stat = str_ireplace(array_keys($map), array_values($map), $stat);

    return $stat;
}

/**
 * 格式化一行表格
 *
 * @param array $cols
 * @param array $widths
 * @param string $tab
 * @param string $separator
 * @return string
 */
function table_format_row($cols, $widths, $tab = '  ', $separator = ' | ')
{
    $rowContent = $tab . ltrim($separator);
    foreach ($cols as $index => $col) {
        $rowContent .= ($index == 0) ? '' : $separator;
        $spaces = $widths[$index] - mb_strwidth($col);
        $rowContent .= str_repeat(' ', $spaces) . $col;
    }
    $rowContent .= rtrim($separator);
    return $rowContent;
}

/**
 * 使用 Git 获取全局提交者名字
 *
 * @return string
 */
function git_get_author()
{

//    $process = new \Symfony\Component\Process\Process(['/usr/bin/git', 'config', '--global', 'user.name']);
//    $process->start();
//
//    foreach ($process as $type => $data) {
//        if ($process::OUT === $type) {
//            echo "\nRead from stdout: ".$data;
//        } else { // $process::ERR === $type
//            echo "\nRead from stderr: ".$data;
//        }
//    }
//    exit;

//    $gitCommand = new GitWrapper\GitWrapper();
//    var_dump($gitCommand->git('config --global  --list'));exit;

    return trim(
        (new GitWrapper\GitWrapper())->git('config --global user.name')
    );
}

/**
 * 使用正则匹配计算总代码行数
 *
 * @param string $file
 * @return int
 */
function stat_code_lines($file)
{
    $content = file_get_contents($file);
    preg_match_all('/(\d+)\s*行代码新增/', $content, $matches);
    $sum = array_sum($matches[1]);
    return $sum;
}

/**
 * 通过 remote origin url 获取仓库的远程地址
 *
 * @param string $path
 * @return string|null
 */
function get_remote_url($path)
{
    $url = trim(
        (new GitWrapper\GitWrapper())->git('ls-remote --get-url origin', $path)
    );
    $url = preg_match('/^(.+?)(@)?(?(2)(.+?)|):(.*?)\.git$/i', $url, $matches);
    if ($matches[2]) {
        return 'http://' . $matches[3] . '/' . $matches[4];
    }
    return $matches[1] . ':' . $matches[4];
}

/**
 * 判断某字符串是否为某子字符串开头
 *
 * @param string $haystack
 * @param string|array $needles
 * @return bool
 */
function starts_with($haystack, $needles)
{
    foreach ((array) $needles as $needle) {
        if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
            return true;
        }
    }
    return false;
}

function toast($txt, $level = 'success', $isDie = false)
{
    $success = function ($txt) {
        return <<<LINE
{$txt} \033[36m OK \033[0m
LINE;
    };

    $error = function ($txt) {
        return <<<LINE
{$txt} \033[0;31m {$txt} \033[0;31m
LINE;
    };

    $string = $$level($txt);

    $isDie ? die($string) : printf($string. PHP_EOL);
}

function getProjects($path) {
    // 获取所有的项目
    if (!is_dir($path)) {
        toast("ERROR: your path error($path) no exist", 'error', true);
    }

    $paths = scandir($path);

    $tmp = [];
    foreach ($paths as $projectName) {
        if ($path === '.' || $path === '..' || $path === '.DS_Store') {
            continue;
        }

        $projectPath = "{$path}/{$projectName}";
        if (!is_dir("{$projectPath}/.git")) {
            continue;
        }

        $tmp[] = [
            'path' => "{$path}/{$projectName}",     ## [必填] Git 仓库本地目录；请填绝对路径。
            'url' => 'get_remote_url',
            'name' => $projectName,
        ];
    }

    return $tmp;
}

