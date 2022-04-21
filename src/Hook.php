<?php

namespace Fwhy\Blast;

interface Hook
{
    const AFTER_LOAD_HOOKS = 10;
    const AFTER_BUILD_PAGES = 20;
    const AFTER_RENDER = 30;

    public static function timing(): int;

    public static function execute(Builder &$builder): void;
}
