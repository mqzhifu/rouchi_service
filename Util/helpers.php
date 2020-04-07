<?php


if (!function_exists('raise')) {
    function raise($eArr) {
        if (!is_array($eArr)) {
            $eArr = func_get_args();
        }

        if (count($eArr) == 1) {
            $eArr[] = 3002;
        }
        throw new \Exception(...$eArr);
    }
}

if (!function_exists('now')) {
    function now($timestamp = null) {
        if (is_null($timestamp)) {
            $timestamp = time();
        } elseif (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        return date('Y-m-d H:i:s', $timestamp);
    }
}