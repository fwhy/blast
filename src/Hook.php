<?php

namespace Fwhy\Blast;

interface Hook
{
    const int AFTER_LOAD_HOOKS = 10;
    const int AFTER_BUILD_PAGES = 20;
    const int AFTER_RENDER = 30;

    const int PRIORITY = 10;

    public static function timing(): int;

    public static function execute(Builder &$builder): void;
}
