<?php


namespace SilverStripe\ProjectWatcher;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Path;
use Symfony\Component\Finder\Finder;

class DirectoryWatcher implements Watcher
{
    use Configurable;
    use Injectable;

    /**
     * @var array
     */
    private $paths = [];

    /**
     * @var array
     */
    private $match = [];

    /**
     * DirectoryWatcher constructor.
     * @param array $paths
     * @param array $match
     */
    public function __construct(array $paths = [], array $match = [])
    {
        $this->paths = $paths;
        $this->match = $match;
    }

    /**
     * @param int $timestamp
     * @return array
     */
    public function getModifications(int $timestamp): array
    {
        $parent = new Finder();
        $dateStr = '> ' . date('Y-m-d H:i:s', $timestamp);

        $valid = false;
        foreach ($this->getPaths() as $path) {
            if (is_array($path)) {
                list ($dirName, $match) = $path;
            } else {
                $dirName = $path;
                $match = $this->getMatch();
            }
            $dir = Path::join(BASE_PATH, $dirName);
            if (is_dir($dir)) {
                $finder = new Finder();
                $finder->files()->in($dir)->name($match)->date($dateStr);
                $parent->append($finder);
                $valid = true;
            }
        }

        return $valid ? iterator_to_array($parent) : [];
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
     * @return DirectoryWatcher
     */
    public function setMatch(array $match): DirectoryWatcher
    {
        $this->match = $match;
        return $this;
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
     * @return DirectoryWatcher
     */
    public function setPaths(array $paths): DirectoryWatcher
    {
        $this->paths = $paths;
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function addPath(string $path): self
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * @param string $remove
     * @return $this
     */
    public function removePath(string $remove): self
    {
        $this->paths = array_filter($this->paths, function ($path) use ($remove) {
           return $path !== $remove;
        });

        return $this;
    }
}
