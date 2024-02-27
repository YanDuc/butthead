<?php
require_once __DIR__ . '/ContentManager.php';
require_once __DIR__ . '/HTMLProcessor.php';
require_once __DIR__ . '/Logger.php';
class Builder
{
    const PREVIEWS_CONFIG = __DIR__ . '/../site.json';
    const BUILD_PATH = __DIR__ . '/../../build/';
    private $flatPages = [];
    public function __construct()
    {
        if (file_exists(self::PREVIEWS_CONFIG)) {
            $jsonData = json_decode(file_get_contents(self::PREVIEWS_CONFIG), true);
            $this->flatPages = $this->flattenPages($jsonData);
        } else {
            Logger::log('Config file not found');
        }
    }

    public function build($page = false)
    {
        $this->flatPages = $page ? [$page] : $this->flatPages;
        if (!empty($this->flatPages)) {
            foreach ($this->flatPages as $page) {
                $htmlProcessor = new HTMLProcessor();
                $html = $htmlProcessor->compile($page);

                if ($page === 'root') {
                    file_put_contents(self::BUILD_PATH . 'index.html', $html);
                } else if (!is_dir(self::BUILD_PATH . $page)) {
                    mkdir(self::BUILD_PATH . $page, 0777, true);
                    // create index.html file
                    if (!file_exists(self::BUILD_PATH . $page . '/index.html')) {
                        file_put_contents(self::BUILD_PATH . $page . '/index.html', $html);
                    }
                } else {
                    // update index.html file
                    file_put_contents(self::BUILD_PATH . $page . '/index.html', $html);
                }
            }

            if (!$page) {
                // remove old build folders
                $files = $this->getAllSubdirectories(realpath(self::BUILD_PATH));
                foreach ($files as $file) {
                    preg_match('/\/build\/(.+)/', $file, $matches);
                    $pathAfterBuild = $matches[1];
                    if (!in_array($pathAfterBuild, $this->flatPages)) {
                        $this->removeNonFlatPagesDirectories($file, $this->flatPages);
                        rmdir($file);
                    }
                }
            }
        }
    }

    private function getAllSubdirectories($directory) {
        $subdirs = glob($directory . '/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            $subdirs = array_merge($subdirs, $this->getAllSubdirectories($subdir));
        }
        return $subdirs;
    }

    private function removeNonFlatPagesDirectories($directory, $flatPages) {
        $files = glob($directory . '/*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->removeNonFlatPagesDirectories($file, $flatPages);
                if (!in_array(basename($file), $flatPages)) {
                    $this->removeNonFlatPagesDirectories($file, $flatPages); // Remove subdirectories first
                    rmdir($file);
                }
            } else {
                unlink($file); // Remove files
            }
        }
    }

    public function flattenPages($pages, $prefix = '')
    {
        $result = [];
        foreach ($pages as $key => $page) {
            if (!isset($page['pageName'])) {
                continue;
            }
            $result[] = trim($prefix . $key);
            if (isset($page['subPages'])) {
                $result = array_merge($result, $this->flattenPages($page['subPages'], trim($prefix . $key) . '/'));
            }
        }
        return $result;
    }
}