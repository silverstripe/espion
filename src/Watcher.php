<?php


namespace SilverStripe\ProjectWatcher;


interface Watcher
{
    public function getModifications(int $timestamp): array;
}
