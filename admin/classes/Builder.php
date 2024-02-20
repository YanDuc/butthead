<?php
require_once __DIR__ . '/ContentManager.php';
require_once __DIR__ . '/HTMLProcessor.php';
require_once __DIR__ . '/Logger.php';
class Builder
{
    const PREVIEWS_CONFIG = __DIR__ . '/../../previews/config.json';
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

    public function build()
    {
        if (!empty($this->flatPages)) {
            foreach ($this->flatPages as $page) {
                $htmlProcessor = new HTMLProcessor();
                $html = $htmlProcessor->compile($page);

                // create page folder if it doesn't exist
                if (!is_dir(self::BUILD_PATH . $page)) {
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
        }
    }

    public function flattenPages($pages, $prefix = '')
    {
        $result = [];
        foreach ($pages as $key => $page) {
            $result[] = $prefix . $key;
            if (isset($page['subPages'])) {
                $result = array_merge($result, $this->flattenPages($page['subPages'], $prefix . $key . '/'));
            }
        }
        return $result;
    }
}