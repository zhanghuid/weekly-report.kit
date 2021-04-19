<?php

return [
    /**
     * 提交者
     * 可以在本地通过 `git config --global user.name` 命令查看
     */
    'author' => git_get_author(),
];
