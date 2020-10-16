<?php

namespace SilverStripe\Espion;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

class Espion
{
    use Injectable;
    use Configurable;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * Espion constructor.
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
}
