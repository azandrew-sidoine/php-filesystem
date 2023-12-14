<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Filesystem\Helpers;

class Arr
{
    /**
     * Filter array returning only the values matching the provided keys.
     *
     * @param array $keys
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public static function only(array $list, $keys = [], bool $use_keys = true)
    {
        if (\function_exists('drewlabs_core_array_only')) {
            return (new \ReflectionFunction('drewlabs_core_array_only'))->invoke($list, $keys, $use_keys);
        }
        // Call the current implementation if the global function <drewlabs_core_array_only> does not exists
        if (!\is_string($keys) && !\is_array($keys) && !($keys instanceof \Iterator)) {
            throw new \InvalidArgumentException('$keys parameter must be a PHP string|array or a validate iterator');
        }
        $keys = \is_string($keys) ? [$keys] : (\is_array($keys) ? $keys : iterator_to_array($keys));
        if (empty($keys)) {
            return [];
        }

        return array_filter($list, static function ($current) use ($keys) {
            return \in_array($current, $keys, true);
        }, $use_keys ? \ARRAY_FILTER_USE_KEY : \ARRAY_FILTER_USE_BOTH);
    }
}
