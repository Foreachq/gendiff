<?php

namespace Differ\DiffGenerator\FindDifferences;

function getDifferences(array $arr1, array $arr2): array
{
    $allEntries = array_unique(array_merge(array_keys($arr1), array_keys($arr2)));

    $diffBuilder = [];
    foreach ($allEntries as $key) {
        if (!in_array($key, array_keys($arr2), true)) {
            $diffBuilder[] = ['mod' => '-', 'key' => $key, 'val' => $arr1[$key]];
            continue;
        }
        if (!in_array($key, array_keys($arr1), true)) {
            $diffBuilder[] = ['mod' => '+', 'key' => $key, 'val' => $arr2[$key]];
            continue;
        }
        if ($arr2[$key] !== $arr1[$key]) {
            if (is_array($arr1[$key]) && is_array($arr2[$key])) {
                $newValue = getDifferences($arr1[$key], $arr2[$key]);
                $diffBuilder[] = ['mod' => '', 'key' => $key, 'val' => $newValue];
                continue;
            }
            $diffBuilder[] = ['mod' => '-', 'key' => $key, 'val' => $arr1[$key]];
            $diffBuilder[] = ['mod' => '+', 'key' => $key, 'val' => $arr2[$key]];
            continue;
        }
        $diffBuilder[$key] = $arr1[$key];
    }

    return normalizeDifferences($diffBuilder);
}

function normalizeDifferences(array $diffs): array
{
    $keys = array_keys($diffs);
    return array_map(function ($key) use ($diffs) {
        $diff = $diffs[$key];
        if (!is_array($diff)) {
            return ['mod' => '', 'key' => $key, 'val' => $diff];
        }
        if (!array_key_exists('mod', $diff)) {
            $newValue = normalizeDifferences($diff);
            return ['mod' => '', 'key' => $key, 'val' => $newValue];
        }
        if (is_array($diff['val'])) {
            $newValue = normalizeDifferences($diff['val']);
            return ['mod' => $diff['mod'], 'key' => $diff['key'], 'val' => $newValue];
        }

        return $diff;
    }, $keys);
}