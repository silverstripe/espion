<?php

namespace SilverStripe\ProjectWatcher;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use InvalidArgumentException;
use SilverStripe\ORM\FieldType\DBDatetime;
use SplFileInfo;
use Psr\SimpleCache\InvalidArgumentException as CacheInvalidArgumentException;

class ProjectWatcher
{
    use Injectable;
    use Configurable;

    const CACHE_KEY = 'timestamp';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Watcher[]
     */
    private $watchers = [];

    /**
     * @var array|null
     */
    private $modifications;

    /**
     * ProjectWatcher constructor.
     * @param CacheInterface $cache
     * @param Watcher[] $watchers
     */
    public function __construct(CacheInterface $cache, array $watchers = [])
    {
        $this->cache = $cache;
        $this->setWatchers($watchers);
    }

    /**
     * @return SplFileInfo[]
     * @throws CacheInvalidArgumentException
     */
    public function getModifications(): array
    {
        if ($this->modifications) {
            return $this->modifications;
        }

        $mods = [];
        $since = $this->getTimestamp() ?: DBDatetime::now()->getTimestamp();
        foreach ($this->watchers as $watcher) {
            $mods = array_merge($mods, $watcher->getModifications($since));
        }
        $now = DBDatetime::now()->getTimestamp();
        $this->cache->set(self::CACHE_KEY, $now);
        $this->modifications = $mods;

        return $this->modifications;
    }

    /**
     * @return bool
     */
    public function hasModifications(): bool
    {
        return !empty($this->getModifications());
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->modifications = null;
    }

    /**
     * @return int|null
     * @throws CacheInvalidArgumentException
     */
    public function getTimestamp(): ?int
    {
        if ($this->cache->has(self::CACHE_KEY)) {
            return $this->cache->get(self::CACHE_KEY);
        }

        return null;
    }
    /**
     * @param array $watchers
     * @return $this
     */
    public function setWatchers(array $watchers): self
    {
        foreach ($watchers as $watcher) {
            if ($watcher === false) {
                continue;
            }
            if (!$watcher instanceof Watcher) {
                throw new InvalidArgumentException(sprintf(
                    '%s::%s must be passed an array of %s instances',
                    __CLASS__,
                    __FUNCTION__,
                    Watcher::class
                ));
            }
            $this->watchers[] = $watcher;
        }

        return $this;
    }
}
