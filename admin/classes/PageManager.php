<?php
require_once __DIR__ . '/Logger.php';
class PageManager
{
    private $jsonFilePath = __DIR__ . '/../site.json';
    private $pages;

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

    public function changeOrder($pagePath, $belowPagePath)
    {
        $pages = $this->getPages();

        // Check if both $pagePath and $belowPagePath contain '/'
        if (strpos($pagePath, '/') !== false) {
            // Extract parent pages and subpage names
            list($parentPage1, $subpage1) = explode('/', $pagePath);

            // Check if $belowPagePath is empty
            if (empty($belowPagePath)) {
                // Handle ordering for subpages when $belowPagePath is empty
                $this->reorderSubpages($pages, $parentPage1, $subpage1, null);
            } else {
                // Extract parent pages and subpage names from $belowPagePath
                list($parentPage2, $subpage2) = explode('/', $belowPagePath);

                // Ensure that both subpages belong to the same parent
                if ($parentPage1 == $parentPage2) {
                    $this->reorderSubpages($pages, $parentPage1, $subpage1, $subpage2);
                }
            }
        } else if (strpos($pagePath, '/') == false && strpos($belowPagePath, '/') == false) {
            // Handle ordering for top-level pages
            $this->reorderTopLevelPages($pages, $pagePath, $belowPagePath);
        }

        file_put_contents($this->jsonFilePath, json_encode($pages, JSON_PRETTY_PRINT));
        return ['success' => true];
    }

    private function reorderTopLevelPages(&$pages, $pagePath, $belowPagePath)
    {
        $dragPage = $this->getPageParams($pagePath);
        $belowDropPageOrder = 999;

        if ($belowPagePath) {
            $belowDropPage = $this->getPageParams($belowPagePath);
            $belowDropPageOrder = $belowDropPage['order'];
        }

        foreach ($pages as &$page) {
            if ($page['pageName'] === $dragPage['pageName']) {
                $page['order'] = (int) $belowDropPageOrder - 0.5;
            }
        }

        $this->sortPages($pages);
    }

    private function reorderSubpages(&$pages, $parentPage, $subpage1, $subpage2)
    {
        $parentPageData = &$pages[$parentPage];
        $dragPage = $this->getPageParams($parentPage . '/' . $subpage1);
        $belowDropPageOrder = 999;

        if ($subpage2) {
            $belowDropPage = $this->getPageParams($parentPage . '/' . $subpage2);
            $belowDropPageOrder = $belowDropPage['order'];
        }

        foreach ($parentPageData['subPages'] as &$subpage) {
            if ($subpage['pageName'] === $dragPage['pageName']) {
                $subpage['order'] = (int) $belowDropPageOrder - 0.5;
            }
        }

        // Sort the subpages based on their current order
        uasort($parentPageData['subPages'], function ($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return ($a['order'] < $b['order']) ? -1 : 1;
        });

        // Assign new incremental order values starting from 1
        $order = 1;
        foreach ($parentPageData['subPages'] as &$subpage) {
            $subpage['order'] = $order;
            $order++;
        }

        // $this->sortSubpages($parentPageData['subPages']);
        $this->sortPages($pages);
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

    private function sortSubpages(&$subpages)
    {
        // Sort the subpages based on their current order
        uasort($subpages, function ($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return ($a['order'] < $b['order']) ? -1 : 1;
        });

        // Assign new incremental order values starting from 1
        $order = 1;
        foreach ($subpages as &$subpage) {
            $subpage['order'] = $order;
            $order++;
        }
    }

    public function reorganizeOrder()
    {
        $pages = $this->getPages();

        // Sort the pages based on their current order
        usort($pages, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        // Assign new incremental order values starting from 1
        $order = 1;
        foreach ($pages as &$page) {
            $page['order'] = $order;
            $order++;
        }

        file_put_contents($this->jsonFilePath, json_encode($pages, JSON_PRETTY_PRINT));
        return ['success' => true];
    }

    public function sort()
    {
        $jsonData = [];
        if (file_exists($this->jsonFilePath)) {
            $jsonData = json_decode(file_get_contents($this->jsonFilePath), true);
        }

        // Find the maximum sort number
        $maxSort = 0;
        foreach ($jsonData as $page) {
            if (isset($page['order']) && $page['order'] > $maxSort) {
                $maxSort = $page['order'];
            }
        }

        // Increment the sort number for the new item
        $newSort = $maxSort + 1;

        // Add the sort number to the new item
        $newItem = end($jsonData);
        $newItem['order'] = $newSort;
        $jsonData[key($jsonData)] = $newItem;

        file_put_contents($this->jsonFilePath, json_encode($jsonData, JSON_PRETTY_PRINT));
    }

    private function log($message)
    {
        // if message is not an array
        if (!is_array($message)) {
            error_log($message);
        } else {
            error_log(print_r($message, true));
        }
    }

    public function addUnauthorizedUsers($email, $pagePath)
    {
        $pages = $this->getPages();
        $updatedPages = $this->updatePageUnauthorizedUsers($pages, $email, $pagePath, true);
        file_put_contents($this->jsonFilePath, json_encode($updatedPages, JSON_PRETTY_PRINT));
        return ['success' => true];
    }

    public function removeUnauthorizedUsers($email, $pagePath)
    {
        $pages = $this->getPages();
        $updatedPages = $this->updatePageUnauthorizedUsers($pages, $email, $pagePath, false);
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
        $parent = null;
        if (str_contains($pagePath, '/')) {
            $parent = explode('/', $pagePath)[0];
        }
        $pages = $this->getPages();
        foreach ($pages as $key => $page) {
            if (!$parent) {
                if ($key == $pagePath) {
                    $page['url'] = $key;
                    return $page;
                }
            } else {
                $subPageName = explode('/', $pagePath)[1];
                if ($key == $parent && isset($page['subPages'][$subPageName])) {
                    $page['subPages'][$subPageName]['url'] = $subPageName;
                    return $page['subPages'][$subPageName];
                }
            }
        }
        throw new Exception('Page not found');
    }

    public function editPage($url, $pageName, $description, $addToNav, $pagePath)
    {
        $parent = null;
        if (str_contains($pagePath, '/')) {
            $parent = explode('/', $pagePath)[0];
        }
        $pages = $this->getPages();
        $change = false;
        foreach ($pages as $key => &$page) {
            if (!$parent && $key == $pagePath) {
                $pages[$url] = $page;
                $pages[$url]['description'] = $description;
                $pages[$url]['pageName'] = $pageName;
                $pages[$url]['addToNav'] = $addToNav === 'true' ? true : false;
                if ($url != $key) {
                    unset($pages[$key]);
                }
                $change = true;
                break;
            } else {
                $subPageName = explode('/', $pagePath)[1];
                if ($key == $parent && isset($page['subPages'][$subPageName])) {
                    $pages[$parent]['subPages'][$url] = $page['subPages'][$subPageName];
                    $pages[$parent]['subPages'][$url]['description'] = $description;
                    $pages[$parent]['subPages'][$url]['pageName'] = $pageName;
                    $pages[$parent]['subPages'][$url]['addToNav'] = $addToNav === 'true' ? true : false;
                    if ($url != $subPageName) {
                        unset($pages[$parent]['subPages'][$subPageName]);
                    }
                    $change = true;
                    break;
                }
            }
        }
        if ($change) {
            file_put_contents($this->jsonFilePath, json_encode($pages, JSON_PRETTY_PRINT));
        }
    }

    public function removePage($pagePath)
    {
        $parent = null;
        if (str_contains($pagePath, '/')) {
            $parent = explode('/', $pagePath)[0];
        }
        $pages = $this->getPages();
        foreach ($pages as $key => &$page) {
            if (!$parent && $key == $pagePath) {
                unset($pages[$key]);
                break;
            } else {
                $subPageName = explode('/', $pagePath)[1];
                if ($key == $parent && isset($page['subPages'][$subPageName])) {
                    unset($page['subPages'][$subPageName]);
                    break;
                }
            }
        }

        file_put_contents($this->jsonFilePath, json_encode($pages, JSON_PRETTY_PRINT));
        return ['success' => true];
    }
}