<?php
require_once __DIR__ . '/Logger.php';
class PageManager
{
    private $jsonFilePath = __DIR__ . '/../site.json';
    private $pages;
    private $pageTarget;
    private $secondPageTarget;

    public function __construct()
    {
        $pages = $this->initFile();
        uasort($pages, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        $this->pages = $pages;
    }

    private function initFile()
    {
        $jsonData = [];
        if (file_exists($this->jsonFilePath)) {
            $jsonData = json_decode(file_get_contents($this->jsonFilePath), true);
        }

        $requiredPages = ['root', 'bh-header', 'bh-footer'];
        $missingPages = array_diff($requiredPages, array_keys($jsonData));
        if (!empty($missingPages)) {
            foreach ($missingPages as $pageName) {
                $this->add($pageName, '', false);
            }
            return json_decode(file_get_contents($this->jsonFilePath), true);
        }

        return $jsonData;
    }

    public function add($pageName, $description, $addToNav, $parent = null)
    {
        try {
            // Format page name to URL
            $pageUrl = preg_replace('/[^A-Za-z0-9\-]/', '-', $pageName);
            $pageUrl = trim($pageUrl, '-');
            $pageUrl = str_replace(['à', 'â', 'é', 'è', 'ê', 'ë', 'î', 'ï', 'ô', 'û', 'ù', 'ü'], ['a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'o', 'u', 'u', 'u'], $pageUrl);
            $pageUrl = strtolower($pageUrl);

            // Create or update JSON file
            $jsonData = [];
            if (file_exists($this->jsonFilePath)) {
                $jsonData = json_decode(file_get_contents($this->jsonFilePath), true);
            }

            // Find the maximum order of the item level
            $maxOrder = 0;
            if ($parent) {
                $parentItem = $jsonData[$parent];
                if (isset($parentItem[$pageUrl])) {
                    $subPages = $parentItem[$pageUrl] ?? [];
                    foreach ($subPages as $subPage) {
                        if (isset($subPage['order']) && $subPage['order'] > $maxOrder) {
                            $maxOrder = $subPage['order'];
                        }
                    }
                }
            } else {
                foreach ($jsonData as $item) {
                    if (isset($item['order']) && $item['order'] > $maxOrder) {
                        $maxOrder = $item['order'];
                    }
                }
            }

            // Increment the order for the new item
            $newOrder = $pageName === 'root' ? 0 : $maxOrder + 1;
            $pageData['order'] = $newOrder;

            if ($parent) {
                // Add subpage data to parent page
                $subPages = $jsonData[$parent][$pageUrl] ?? [];
                $subPages[] = $pageData;
                $jsonData[$parent]['subPages'][$pageUrl] = [
                    'description' => $description,
                    'pageName' => $pageName,
                    'addToNav' => $addToNav === 'true' ? true : false,
                    'order' => $newOrder
                ];
            } else {
                // Add top-level page data
                $jsonData[$pageUrl] = [
                    'description' => $description,
                    'pageName' => $pageName === 'root' ? 'Home' : $pageName,
                    'addToNav' => $addToNav === 'true' ? true : false,
                    'order' => $newOrder
                ];
            }
            file_put_contents($this->jsonFilePath, json_encode($jsonData, JSON_PRETTY_PRINT));
            return ['success' => true, 'page' => $pageUrl, 'parent' => $parent];
        } catch (Exception $e) {
            Logger::log('Failed to add page: ' . $e->getMessage());
            // throw an exception with the error message
            throw new Exception($e->getMessage());
        }
    }
    public function getPages()
    {
        return $this->pages;
    }

    public function changeOrder($pagePath, $belowPagePath, $topPagePath)
    {
        if (!$belowPagePath && !$topPagePath) {
            return;
        }
        $isBelow = $belowPagePath ? true : false;
        $param = $isBelow ? $belowPagePath : $topPagePath;

        // array Destination
        $key = $this->updatePageTargetAndGetKey($this->pages, $param);
        $destinationArray = &$this->pageTarget;

        // array Source
        $keySource = $this->updatePageTargetAndGetKey($this->pages, $pagePath, true);
        $sourceArray = &$this->secondPageTarget;

        // not move if more than two levels
        if (str_contains($param, '/') && !empty($sourceArray[$keySource]['subPages'])) {
            return;
        }

        $previousOrder = !empty($destinationArray[$key]['order']) ? $destinationArray[$key]['order'] : 999;
        $pageToMove = $sourceArray[$keySource];
        if ($isBelow) {
            $pageToMove['order'] = $previousOrder - 0.5;
        } else {
            $pageToMove['order'] = $previousOrder + 0.5;
        }
        unset($sourceArray[$keySource]);
        $destinationArray[$keySource] = $pageToMove;
        $this->sortPages($destinationArray);

        // remove third level pages
        foreach ($this->pages as &$page) {
            if (!empty($page['subPages'])) {
                foreach ($page['subPages'] as &$subPage) {
                    if (!empty($subPage['subPages'])) {
                        unset($subPage['subPages']);
                    }
                }
            }
        }

        file_put_contents($this->jsonFilePath, json_encode($this->pages, JSON_PRETTY_PRINT));
        return ['success' => true];
    }

    private function sortPages(&$pages)
    {
        // Sort the pages based on their current order
        uasort($pages, function ($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return ($a['order'] < $b['order']) ? -1 : 1;
        });

        // Assign new incremental order values starting from 1
        $order = 1;
        foreach ($pages as &$page) {
            $page['order'] = $order;
            $order++;
        }
    }

    public function addUnauthorizedUsers($email, $pagePath)
    {
        $updatedPages = $this->updatePageUnauthorizedUsers($this->pages, $email, $pagePath, true);
        file_put_contents($this->jsonFilePath, json_encode($updatedPages, JSON_PRETTY_PRINT));
        return ['success' => true];
    }

    public function removeUnauthorizedUsers($email, $pagePath)
    {
        $updatedPages = $this->updatePageUnauthorizedUsers($this->pages, $email, $pagePath, false);
        file_put_contents($this->jsonFilePath, json_encode($updatedPages, JSON_PRETTY_PRINT));
        return ['success' => true];
    }

    private function updatePageUnauthorizedUsers(&$pages, $email, $pagePath, $add)
    {
        $page = null;
        if (strpos($pagePath, '/') === false) {
            $page =& $pages[$pagePath];  // Pass by reference
        } else {
            list($parentPage, $subpage) = explode('/', $pagePath);
            $page =& $pages[$parentPage]['subPages'][$subpage];  // Pass by reference
        }
        if ($add) {
            if (!array_key_exists('unauthorizedUsers', $page)) {
                $page['unauthorizedUsers'] = [$email];
            } elseif (!in_array($email, $page['unauthorizedUsers'])) {
                $page['unauthorizedUsers'][] = $email;
                $page['unauthorizedUsers'] = array_values($page['unauthorizedUsers']);
            }
        } else {
            unset($page['unauthorizedUsers']);
        }
        return $pages;
    }

    public function getPageParams($pagePath)
    {
        $key = $this->updatePageTargetAndGetKey($this->pages, $pagePath);
        if (isset($key) && isset($this->pageTarget[$key])) {
            $params = $this->pageTarget[$key];
            $params['url'] = $key;
            return $params;
        }
        throw new Exception('Page not found');
    }

    public function editPage($url, $pageName, $description, $addToNav, $pagePath)
    {
        $key = $this->updatePageTargetAndGetKey($this->pages, $pagePath);
        $newKey = ($url == $key) ? $key : $url;
        $this->pageTarget[$newKey] = $this->pageTarget[$key];
        $this->pageTarget[$newKey]['pageName'] = $pageName;
        $this->pageTarget[$newKey]['description'] = $description;
        $this->pageTarget[$newKey]['addToNav'] = ($addToNav === 'true') ? true : false;
        if ($url !== $key) {
            unset($this->pageTarget[$key]);
        }
        file_put_contents($this->jsonFilePath, json_encode($this->pages, JSON_PRETTY_PRINT));
    }

    private function updatePageTargetAndGetKey(&$json, $page, $secondPageTarget = false)
    {
        $targetParam = $secondPageTarget ? 'secondPageTarget' : 'pageTarget';
        $pathParts = explode('/', $page);
        $key = $pathParts[0];
        $this->{$targetParam} = &$json;

        if (count($pathParts) > 1) {
            foreach (array_slice($pathParts, 1) as $part) {
                $this->{$targetParam} = &$this->{$targetParam}[$pathParts[0]]['subPages'];
                $key = $part;
            }
        }
        return $key;
    }

    public function removePage($pagePath)
    {
        $key = $this->updatePageTargetAndGetKey($this->pages, $pagePath);
        unset($this->pageTarget[$key]);
        file_put_contents($this->jsonFilePath, json_encode($this->pages, JSON_PRETTY_PRINT));
        return ['success' => true];
    }

    public function copyPage($pagePath)
    {
        $key = $this->updatePageTargetAndGetKey($this->pages, $pagePath);
        $this->pageTarget[$key . '-copy'] = $this->pageTarget[$key];

        $subpages = [];
        if (isset($this->pageTarget[$key]['subPages'])) {
            foreach ($this->pageTarget[$key]['subPages'] as $subkey => $value) {
                $subpages[$subkey . '-copy'] = $value;
            }
        }
        $this->pageTarget[$key . '-copy']['subPages'] = $subpages;
        file_put_contents($this->jsonFilePath, json_encode($this->pages, JSON_PRETTY_PRINT));
        return ['success' => true];
    }
}