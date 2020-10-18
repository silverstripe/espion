<?php


namespace SilverStripe\ProjectWatcher;


use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Path;
use SilverStripe\ORM\ArrayLib;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;
use JsonException;

class ComposerDependencyWatcher implements Watcher
{
    use Injectable;
    use Configurable;


    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $paths = [];

    /**
     * @var array
     */
    private $match = [];

    /**
     * @var array
     */
    private $depTypes = ['silverstripe-vendormodule'];

    /**
     * ComposerDependencyWatcher constructor.
     * @param CacheInterface $cache
     * @param array $paths
     * @param array $match
     */
    public function __construct(CacheInterface $cache, array $paths = [], array $match = [])
    {
        $this->cache = $cache;
        $this->paths = $paths;
        $this->match = $match;
    }

    /**
     * @param int $timestamp
     * @return array
     * @throws Exception
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public function getModifications(int $timestamp): array
    {
        $mods = [];
        $installFilePath = Path::join(BASE_PATH, 'vendor/composer/installed.json');

        if (!file_exists($installFilePath)) {
            return [];
        }

        $dependencies = $this->getSourceDepdendencies($installFilePath);

        $isMapped = ArrayLib::is_associative($this->getPaths());
        foreach ($dependencies as $dep) {
            $name = $dep['name'];

            // todo: this probably isn't always accurate. Probably need the Composer library.
            $installDir = 'vendor/' . $name;

            $watcher = new DirectoryWatcher();
            $pathSrc = $isMapped && isset($this->paths[$name])
                ? $this->paths[$name]
                : $this->paths;
            $paths = $this->normalisePaths($installDir, $pathSrc);
            foreach ($paths as $path) {
                $watcher->addPath($path);
            }
            $mods = array_merge($mods, $watcher->getModifications($timestamp));
        }

        return $mods;
    }

    /**
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @param array $paths
     * @return $this
     */
    public function setPaths(array $paths): self
    {
        $this->paths = $paths;
        return $this;
    }

    /**
     * @return array
     */
    public function getMatch(): array
    {
        return $this->match;
    }

    /**
     * @param array $match
     * @return $this
     */
    public function setMatch(array $match): self
    {
        $this->match = $match;
        return $this;
    }


    /**
     * @param string $installDir
     * @param array $paths
     * @return array
     */
    private function normalisePaths(string $installDir, array $paths): array
    {
        if (!is_array($paths)) {
            $paths = [$paths];
        }

        return array_map(function ($path) use ($installDir) {
            return Path::join($installDir, $path);
        }, $paths);
    }

    /**
     * @param string $installFilePath
     * @return array
     * @throws InvalidArgumentException
     */
    private function getSourceDepdendencies(string $installFilePath): array
    {
        $installHash = md5(
            file_get_contents($installFilePath) .
            serialize($this->depTypes)
        );

        if ($this->cache->has($installHash)) {
            return $this->cache->get($installHash);
        }

        $this->cache->clear();
        $json = file_get_contents($installFilePath);
        $config = json_decode($json, true);
        if ($config === false) {
            throw new Exception(sprintf(
                'Composer install file at %s is invalid!',
                $installFilePath
            ));
        }

        $sourceDeps = array_filter($config, function ($dep) {
            $src = $dep['installation-source'] ?? '';
            $type = $dep['type'] ?? '';

            return $src === 'source' && in_array($type, $this->depTypes);
        });


        $this->cache->set($installHash, $sourceDeps);

        return $sourceDeps;
    }
}
