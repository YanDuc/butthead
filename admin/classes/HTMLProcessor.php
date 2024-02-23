<?php
session_start();
require_once __DIR__ . '/ContentManager.php';
require_once __DIR__ . '/PageManager.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/../config/config.php';
class HTMLProcessor
{

    private $html;
    private $htmlElementsWithCSS = [
        'classes' => [],
        'ids' => [],
        'tags' => [],
    ];
    const ASSETS_PATH = __DIR__ . '/../../assets/';

    public function compile($path)
    {
        try {
            // get html page content
            $contentManager = new ContentManager();
            $html = $contentManager->getPageContent($path);

            // get Meta data
            $pageManager = new PageManager();
            $page = $pageManager->getPageParams($path);
            $title = $page['pageName'];
            $description = $page['description'];

            // get styles
            $globalStyle = "<style>" . $this->getGlobalStyles() . "</style>";
            // get header
            $contentManager = new ContentManager();
            $header = $contentManager->getBlocContentFromFile('bh-header', 'bh-header');
            $nav = $this->getNavigation($path);
            $header = preg_replace('/\{\{\s*nav\s*\}\}/', $nav, $header);
            $footer = $contentManager->getBlocContentFromFile('bh-footer', 'bh-footer');

            $this->html = $header . '<!-- separator -->' . $html . '<!-- separator -->' . $footer;
            $blocsArray = $this->splitHtml();

            // filter empty blocs
            $blocsArray = array_filter($blocsArray, function ($block) {
                return trim($block) !== '';
            });

            // In your compile method
            foreach ($blocsArray as $key => $block) {
                $className = $this->extractClassName($block);
                $blocsArray[$key] = $this->addClassInStyle($block, $className);
                $blocsArray[$key] = $this->addClassInHtml($blocsArray[$key], $className);
                $blocsArray[$key] = $this->addContent($blocsArray[$key]);
            }
            $blocsArray = $this->moveBlocksInsideLayouts($blocsArray);
            $htmlString = implode('', $blocsArray);

            $style = $this->extractBlocStyles($globalStyle . $htmlString);
            $head = $this->getHead($title, $description, $style);
            $this->html = $this->formatAndMinimizeHtml($htmlString, $head);
            return $this->html;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function getHead($title, $description, $styles)
    {
        $lang = $_SESSION['loggedIn']['lang'] = 'fr_FR' ? 'fr' : 'en';
        return "<!DOCTYPE html>
        <html lang=\"$lang\">
        <head>
            <meta charset=\"UTF-8\">
            <title>$title</title>
            <meta name=\"description\" content=\"$description\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <base href=\"/\">
            <style>
                $styles
            </style>
        </head>";
    }

    private function formatAndMinimizeHtml($html, $head)
    {
        // keep only head styles
        $stylePattern = '/<style\b[^>]*>.*?<\/style>/s';
        $firstStyleTag = true;
        $html = preg_replace_callback($stylePattern, function ($match) use (&$firstStyleTag) {
            if ($firstStyleTag) {
                $firstStyleTag = false;
                return $match[0];
            } else {
                return '';
            }
        }, $html);

        // remove extra spaces
        $html = preg_replace('/\s{2,}/', ' ', $html);

        // remove sript type application/json
        $html = preg_replace('/<script type="application\/json">(.*?)<\/script>/s', '', $html);
        
        // remove comments
        $html = preg_replace('/<!--(.*?)-->/', '', $html);

        return $head . "<body>" . $html . '</body></html>';
    }

    private function moveBlocksInsideLayouts($blocsArray)
    {
        $blocsToDelete = [];
        foreach ($blocsArray as $key => $value) {
            if (str_contains($value, '"layout":')) {
                $pattern = '/"blocs":(\[.*?\])/';
                if (preg_match($pattern, $value, $matches)) {
                    $blocsIDs = json_decode($matches[1], true);
                    foreach ($blocsIDs as $id) {
                        $blockContent = $this->getBloc($blocsArray, $id);
                        if ($blockContent) {
                            $blocs[] = $blockContent['bloc'];
                            $blocsToDelete[] = $blockContent['key'];
                        }
                    }
                    if (!empty($blocs)) {
                        $content = implode('', $blocs);
                        $blocsArray[$key] = preg_replace('/\{\{\s*content\s*\}\}/', $content, $blocsArray[$key]);
                    } else {
                        $blocsArray[$key] = preg_replace('/\{\{\s*content\s*\}\}/', '', $blocsArray[$key]);
                        ;
                    }
                }
            }
            $blocs = [];
        }
        // remove blocs
        foreach ($blocsToDelete as $index) {
            unset($blocsArray[$index]);
        }
        return $blocsArray;
    }

    private function getBloc($blockArray, $id)
    {
        foreach ($blockArray as $key => $block) {
            // search inside <script type="application/json">{"bloc":"test2","id":"65bce69a3cdd7","input1":"jljljkl","input2":"jkljklj","input3":"kljljl"}</script>
            if (str_contains($block, "\"id\":\"$id\"")) {
                return array(
                    "bloc" => $block,
                    "key" => $key
                );
            }
        }
        return null;
    }



    private function addContent($content)
    {
        $datas = $this->_extractData($content);

        // regex for getting content inside brackets
        preg_match_all('/\{\{(.+?)\}\}/', $content, $matches);
        $contentToReplace = $matches[0];
        $i = 0;
        foreach ($contentToReplace as $key => $value) {
            $pos = strpos($content, $value);
            if (($pos !== false) && $this->containsDynamicInput($value)) {
                if (str_contains($value, 'img')) {
                    $fileName = $datas[$i][0];
                    $alt = $datas[$i][1];
                    $content = substr_replace(
                        $content,
                        "<picture>
                            " . ($this->isFileExists("{$fileName}_s.jpeg") ? "<source media=\"(max-width: " . HTMLConfig::BREAKPOINTS['s'] . "px)\" srcset=\"../assets/img/{$fileName}_s.jpeg\">" : "") . "
                            " . ($this->isFileExists("{$fileName}_m.jpeg") ? "<source media=\"(max-width: " . HTMLConfig::BREAKPOINTS['m'] . "px)\" srcset=\"../assets/img/{$fileName}_m.jpeg\">" : "") . "
                            <img src=\"assets/img/{$fileName}.jpeg\" alt=\"" . $alt . "\">
                        </picture>",
                        $pos,
                        strlen($value)
                    );
                } else {
                    $content = substr_replace(
                        $content,
                        $datas[$i],
                        $pos,
                        strlen($value)
                    );
                }
                $i++;
            }
        }
        return $content;
    }

    // Function to check if a file exists
    private function isFileExists($filename)
    {
        return file_exists(__DIR__ . "/../../assets/img/{$filename}");
    }

    private function containsDynamicInput($value)
    {
        foreach (HTMLConfig::DYNAMIC_INPUTS as $substring) {
            if (strpos($value, $substring) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getNavigation($path)
    {
        $pageManager = new PageManager();
        $pages = $pageManager->getPages();
        return $this->buildNavigation($pages, '', $path);
    }

    private function getGlobalStyles()
    {
        $fontFaces = $this->generateFontFaceCSS();
        $contentManager = new ContentManager();
        $globalStyles = $contentManager->getGlobalStyles();
        return $fontFaces . $globalStyles;
    }

    private function generateFontFaceCSS() {
        // get fonts in assets
        $directoryPath = self::ASSETS_PATH . 'fonts';
        $fontFormats = ['woff', 'woff2', 'ttf'];
        $fonts = '';
        foreach (new DirectoryIterator($directoryPath) as $fileInfo) {
          if ($fileInfo->isFile()) {
            $extension = strtolower($fileInfo->getExtension());
            if (in_array($extension, $fontFormats)) {
              $fontFamily = basename($fileInfo->getBasename(), '.' . $extension);
              $fonts .= "@font-face {
                font-family: '" . $fontFamily . "';
                src: url('assets/fonts/" . $fileInfo->getFilename() . "') format('" . ($extension === 'ttf' ? 'truetype' : $extension) . "');
              }\n";
            }
          }
        }
        return $fonts;
    }

    private function buildNavigation($pages, $root = '', $currentPage = '')
    {
        $nav = '';
        $isSub = $root !== '' ? true : false;
        if (!$isSub) {
            $nav .= '<ul class="bh-nav-first-level">';
        }
        foreach ($pages as $key => $page) {
            if (isset($page['addToNav']) && $page['addToNav']) {

                $path = $root ? $root . '/' . $key : $key;
                $nav .= '<li><a href="' . $path . '"' . $this->activeClass($path, $currentPage) . '>' . $page['pageName'] . '</a>';
                if (isset($page['subPages'])) {
                    $nav .= '<ul class="bh-nav-second-level">';
                    $nav .= $this->buildNavigation($page['subPages'], $key, $currentPage);
                    $nav .= '</ul>';
                }
                $nav .= '</li>';
            }
        }
        if (!$isSub) {
            $nav .= '</ul>';
        }
        return $nav;
    }

    private function activeClass($path, $currentPage)
    {
        if (rtrim($path, '/') === rtrim($currentPage, '/')) {
            return ' class="bh-active"';
        }
        $currentPageSegments = explode('/', $currentPage);
        $pathSegments = explode('/', $path);
        if (count($currentPageSegments) === 2 && count($pathSegments) === 1 && $currentPageSegments[0] === $pathSegments[0]) {
            return ' class="bh-active"';
        }
        return '';
    }

    private function _extractData($content)
    {
        // Get json
        preg_match('/<script type="application\/json">(.*?)<\/script>/', $content, $matches);
        $json = $matches[1];

        // keep only json content with key starting by input or file
        $data = json_decode($json, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            // Handle JSON decoding error, e.g., log an error message or throw an exception
            $errorMessage = json_last_error_msg();
            Logger::log("JSON decoding error: $errorMessage");
        } else {
            // Filter the keys and keep only the ones starting with "input" or "file"
            $filteredKeys = array_filter(array_keys($data), function ($key) {
                return preg_match('/^(input|file|alt_file)\d*$/', $key);
            });

            // Filter keys starting with "file"
            $fileKeys = array_filter(array_keys($filteredKeys), function ($key) {
                return preg_match('/^file\d+$/', $key);
            });

            // Replace values for file keys with an array containing file value and corresponding alt_file value
            foreach ($fileKeys as $fileKey) {
                $filteredKeys[$fileKey] = [$filteredKeys[$fileKey], $filteredKeys['alt_' . $fileKey]];
                unset($filteredKeys['alt_' . $fileKey]);
            }
        }

        // Sort the filtered keys based on the numeric values at the end of the key
        usort($filteredKeys, function ($a, $b) {
            $numberA = intval(preg_replace('/\D/', '', $a));
            $numberB = intval(preg_replace('/\D/', '', $b));
            return $numberA - $numberB;
        });

        // Create the new array with the filtered and sorted values
        $newArray = array_intersect_key($data, array_flip($filteredKeys));
        $newArrayValues = array_map(function ($key) use ($newArray) {
            return $newArray[$key];
        }, $filteredKeys);

        return $newArrayValues ?? [];
    }

    private function splitHtml()
    {
        return explode("<!-- separator -->", $this->html);
    }

    private function extractClassName($block)
    {
        $classPattern = '/"(?:bloc|layout)":"(.*?)"/';
        preg_match($classPattern, $block, $classMatch);
        return isset($classMatch[1]) ? $classMatch[1] : '';
    }

    private function addClassInStyle($content, $className)
    {
        // Extract styles
        $stylePattern = '/<style>(.*?)<\/style>/s';
        preg_match($stylePattern, $content, $styleMatch);

        // Add class name to all style selectors
        if (isset($styleMatch[1])) {
            $styles = $styleMatch[1];
            $updatedStyles = $this->_addClassNameToSelectors($styles, $className);
            $content = str_replace($styles, $updatedStyles, $content);
        }

        // Return the modified content
        return $content;
    }

    private function _addClassNameToSelectors($styles, $className)
    {
        $className = $this->preventClassStartingByNumber($className);
        $matches = [];
        preg_match_all('/([^{}]+)\{/', $styles, $matches);
        $selectors = $matches[1];
        
        // filter media queries
        $selectors = array_filter($selectors, function ($selector) {
            return !str_contains($selector, '@media');
        });
        foreach ($selectors as $selector) {
            $selector = trim($selector);
            if (str_starts_with($selector, '.')) {
                $this->htmlElementsWithCSS['classes'][] = ltrim($selector, '.');
            } elseif (str_starts_with($selector, '#')) {
                $this->htmlElementsWithCSS['ids'][] = ltrim($selector, '#');
            } else {
                $this->htmlElementsWithCSS['tags'][] = $selector;
            }
        }

        // Add class name to all style selectors
        $updatedStyles = preg_replace_callback('/([^{}]+)\{/', function ($matches) use ($className) {
            $selector = trim($matches[1]);
            if (!str_contains($selector, '@media')) {
                return $selector . '.' . $className . ' {';
            } else {
                return $selector . ' {';
            }
        }, $styles);

        return $updatedStyles;
    }

    private function extractBlocStyles($html)
    {
        $style = '';
        $stylePattern = '/<style\b[^>]*>(.*?)<\/style>/s';
        preg_match_all($stylePattern, $html, $styleMatch);
        if (isset($styleMatch[1])) {
            // Remove duplicates
            $styleMatch[1] = $this->removeDuplicateStyles($styleMatch[1]);
            $style = implode(' ', $styleMatch[1]);
        }
        $style = preg_replace('/\s{2,}/', ' ', $style);
        return $style;
    }

    private function removeDuplicateStyles($styles)
    {
        $normalizedStyles = array_map(function ($style) {
            return preg_replace('/\s+/', ' ', trim($style));
        }, $styles);
        $uniqueStyles = array_unique($normalizedStyles);
        return array_values($uniqueStyles);
    }

    private function preventClassStartingByNumber($className)
    {
        if (preg_match('/^[0-9]/', $className)) {
            $className = '_' . $className;
        }
        return $className;
    }

    private function addClassInHtml($content, $className)
    {
        $className = $this->preventClassStartingByNumber($className);
        $content = preg_replace_callback('/<(\w+)(?:\s+id="([^"]*)")?(?:\s+class="([^"]*)")?>/', function ($matches) use ($className) {
            $tag = $matches[1];
            $id = $matches[2];
            $class = $matches[3] ?: ''; // Use an empty string if no class is present

            if (
                ($class !== '' && in_array($class, $this->htmlElementsWithCSS['classes'])) ||
                ($id !== '' && in_array($id, $this->htmlElementsWithCSS['ids'])) ||
                in_array($tag, $this->htmlElementsWithCSS['tags'])
            ) {
                $newAttributes = [];
                if ($id !== '' && !in_array($id, $this->htmlElementsWithCSS['ids'])) {
                    $newAttributes[] = "id=\"{$id}\"";
                }
                if ($class !== '' && !in_array($className, explode(' ', $class))) {
                    $newAttributes[] = 'class="' . $class . ' ' . $className . '"';
                } else if ($class === '' && $className !== '') {
                    $newAttributes[] = 'class="' . $className . '"';
                }
                $attributesString = !empty($newAttributes) ? ' ' . implode(' ', $newAttributes) : '';
                $attributesString = str_replace(' id=""', '', $attributesString);
                return "<{$tag}{$attributesString}>";
            }

            $idAttribute = !empty($matches[2]) ? " id=\"{$matches[2]}\"" : '';
            $classAttribute = !empty($matches[3]) ? " class=\"{$matches[3]}\"" : '';
            return "<{$tag}{$idAttribute}{$classAttribute}>";
        }, $content);

        return $content;
    }
}
