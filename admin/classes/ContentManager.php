<?php
require_once __DIR__ . '/Resize.php';
require_once __DIR__ . '/FormBuilder.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/../config/config.php';

class ContentManager
{
    private $page = null;
    const TEMPLATE_PATH = __DIR__ . '/../../templates/';
    const IMAGES_PATH = __DIR__ . '/../../assets/img/';
    private $jsonFilePath = __DIR__ . '/../site.json';
    private $json;
    private $target;
    private $targetDestination; // using when moving block to another layout


    public function __construct()
    {
        $this->json = json_decode(file_get_contents($this->jsonFilePath), true);
    }

    public function addContent($page, $block, ...$rest)
    {
        try {
            if ($page === 'bh-header' || $page === 'bh-footer') {
                $blockContent = $page === 'bh-header' ? file_get_contents(self::TEMPLATE_PATH . 'header.html', true) : file_get_contents(self::TEMPLATE_PATH . 'footer.html', true);
                $nameOfBloc = $page === 'bh-header' ? 'header' : 'footer';
            } else {
                // Get content from template
                $blockContent = file_get_contents(self::TEMPLATE_PATH . 'blocks/' . $block, true);
                $nameOfBloc = explode('.', $block)[0];
            }

            // Write content to file
            $blockDatas = $this->writeContentToFile($page, $nameOfBloc, $blockContent, $rest);
            return $blockDatas;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }



    public function createHeader(...$postValues)
    {
        try {
            $page = 'header';
            // Get content from template
            $blockContent = file_get_contents(self::TEMPLATE_PATH . 'header.html', true);

            // Add encapsulation div
            $nameOfBloc = 'header';

            // Extract the style tag using regular expressions
            $styleTagPattern = '/<style\b[^>]*>(.*?)<\/style>/s';
            preg_match($styleTagPattern, $blockContent, $matches);
            $styleTag = $matches[0];

            // Remove the style tag from the content
            $contentWithoutStyle = preg_replace($styleTagPattern, '', $blockContent);
            $encapsulatedContent = '<div class="' . $nameOfBloc . '">' . $contentWithoutStyle . "</div>\n" . $styleTag;
            $blockContent = $encapsulatedContent;

            // Write content to file
            $this->writeContentToFile($page, $nameOfBloc, $blockContent, $postValues);

            // Return JSON response
            $json = $this->getJsonFromRequest($postValues);
            return json_encode($json);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getFormatedPageContent($page)
    {
        try {
            $this->updateTarget($this->json, $page);
            return (isset($this->target['blocks'])) ? $this->target['blocks'] : [];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getForm(...$postValues)
    {
        try {
            if (isset($postValues['block'])) {
                $dynamicContents = $this->getDynamicContent($postValues['block']);
                $formBuilder = new FormBuilder($dynamicContents, null);
            } else if ($postValues['page'] && $postValues['id']) {
                $blockIndex = $this->updateTargetAndGetBlockIndex($postValues['page'], $postValues['id']);
                if ($blockIndex !== null) {
                    $dynamicInputs = $this->extractDynamicInput($this->target[$blockIndex]['html']);
                    $values = $this->extractValues($this->target[$blockIndex]);
                    $formBuilder = new FormBuilder($dynamicInputs, $values);
                }
            }
            return $formBuilder->form;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getPageContent($page)
    {
        try {
            $this->updateTarget($this->json, $page);
            return $this->target;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getArrayContent($pageContent)
    {
        try {
            $separator = "<!-- separator -->";
            $parts = explode($separator, $pageContent);

            // Extract flat content into an array
            $flatContentArray = [];
            foreach ($parts as $part) {
                if (!empty($part)) {
                    $jsonStartPos = strpos($part, '<script type="application/json">');
                    $jsonEndPos = strpos($part, '</script>', $jsonStartPos);

                    if ($jsonStartPos !== false && $jsonEndPos !== false) {
                        $jsonData = substr($part, $jsonStartPos + strlen('<script type="application/json">'), $jsonEndPos - $jsonStartPos - strlen('<script type="application/json">'));
                        $contentData = json_decode($jsonData, true);

                        if ($contentData !== null) {
                            $flatContentArray[] = $contentData;
                        }
                    }
                }
            }
            return $flatContentArray;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function moveBlockUp($page, $id, $layout)
    {
        $blockIndex = $this->updateTargetAndGetBlockIndex($page, $id);
        if ($blockIndex !== null && $blockIndex > 0) {
            $temp = $this->target[$blockIndex];
            $this->target[$blockIndex] = $this->target[$blockIndex - 1];
            $this->target[$blockIndex - 1] = $temp;
            file_put_contents($this->jsonFilePath, json_encode($this->json, JSON_PRETTY_PRINT));
        }
    }

    public function moveBlockDown($page, $id, $layout)
    {
        $blockIndex = $this->updateTargetAndGetBlockIndex($page, $id);
        if ($blockIndex !== null && $blockIndex < count($this->target) - 1) {
            $temp = $this->target[$blockIndex];
            $this->target[$blockIndex] = $this->target[$blockIndex + 1];
            $this->target[$blockIndex + 1] = $temp;
            file_put_contents($this->jsonFilePath, json_encode($this->json, JSON_PRETTY_PRINT));
        }
    }

    public function getGlobalStyles()
    {
        $path = self::TEMPLATE_PATH . 'styles';
        $styles = '';
        foreach (glob($path . '/*.css') as $file) {
            // minify css remove \n and double spaces
            $content = file_get_contents($file);
            $styles .= preg_replace('/\s{2,}/', ' ', $content);
        }
        return $styles;
    }

    public function addLayout($page, $layout)
    {
        try {
            // Get content from template
            $blockContent = file_get_contents(self::TEMPLATE_PATH . 'layouts/' . $layout, true);
            $nameOfBloc = explode('.', $layout)[0];

            // Write content to file
            $this->writeContentToFile($page, $nameOfBloc, $blockContent, [], false);

            // Return JSON response
            $json = $this->getJsonFromRequest([]);
            return json_encode($json);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getDynamicContent($block)
    {
        try {
            if ($block === 'header.html' || $block === 'footer.html') {
                $content = file_get_contents(self::TEMPLATE_PATH . $block, true);
            } else {
                // get content from template
                $content = file_get_contents(self::TEMPLATE_PATH . 'blocks/' . $block, true);
            }

            return $this->extractDynamicInput($content);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function extractDynamicInput($blockContent)
    {
        $regexp = '/\{\{(.+?)\}\}/';
        preg_match_all($regexp, $blockContent, $matches);
        return $matches[1];
    }

    private function extractValues($data)
    {
        // Remove block and id from values
        $data = array_intersect_key($data, array_flip(preg_grep('/^(?:' . implode('|', HTMLConfig::DATAS_KEYS) . ')/', array_keys($data))));

        // Sort values by key
        // Custom sorting function based on the number at the end of the key
        uksort($data, function ($a, $b) {
            $getIntFromKey = function ($str) {
                preg_match('/(\d+)$/', $str, $matches);
                return intval($matches[1]);
            };
            return $getIntFromKey($a) - $getIntFromKey($b);
        });
        // return only values
        return array_values($data);
    }

    private function getDynamicContentFromFile($page, $id)
    {
        try {
            $fileContent = $this->getBlocContentFromFile($page, $id);
            // content inside brackets
            $regexp = '/\{\{(.+?)\}\}/';
            preg_match_all($regexp, $fileContent, $matches);
            return $matches[1];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    public function updateContent($page, $id, ...$postValues)
    {   
        $blockIndex = $this->updateTargetAndGetBlockIndex($page, $id);
        if ($blockIndex !== null) {
            $this->target[$blockIndex] = $this->updateJson($this->target[$blockIndex], $postValues);
            file_put_contents($this->jsonFilePath, json_encode($this->json, JSON_PRETTY_PRINT));
            return ['success' => true];
        } else {
            throw new Exception("Bloc not found");
        }
    }

    public function deleteContent($page, $id)
    {
        $blockIndex = $this->updateTargetAndGetBlockIndex($page, $id);
        if ($blockIndex !== null) {
            unset($this->target[$blockIndex]);
            file_put_contents($this->jsonFilePath, json_encode($this->json, JSON_PRETTY_PRINT));
            return ['success' => true];
        }
    }

    private function &updateTargetAndGetBlockIndex($page, $id, $targetDestination = false)
    {
        $this->page = $page;
        $target = $targetDestination ? 'targetDestination' : 'target';

        $this->updateTarget($this->json, $page, $targetDestination);
        $blockIndex = $this->getBlockIndexById($id, $this->{$target}['blocks']);
        if ($blockIndex !== null) {
            $this->{$target} = &$this->{$target}['blocks'];
            return $blockIndex;
        } else {
            list('layout' => $layout, 'block' => $block) = $this->getBlockIndexInsideLayout($id, $this->{$target}['blocks']);
            if ($layout !== null && $block !== null) {
                $this->{$target} = &$this->{$target}['blocks'][$layout]['blocks'];
                return $block;
            }
        }
        return null;
    }


    private function getBlockIndexById($id, $contents)
    {
        foreach ($contents as $key => $content) {
            if (isset($content['id']) && $content['id'] === $id) {
                return $key;
            }
        }
        return null; // Return null if content with the specified id is not found
    }

    private function getBlockIndexInsideLayout($id, $contents)
    {
        foreach ($contents as $key => $content) {
            if (isset($content['layout']) && isset($content['blocks']) && count($content['blocks']) > 0) {
                foreach ($content['blocks'] as $blockKey => $block) {
                    if (isset($block['id']) && $block['id'] === $id) {
                        return ['layout' => $key, 'block' => $blockKey];
                    }
                }
            }
        }
        return ['layout' => null, 'block' => null];
    }

    public function addBlockToLayout($page, $blockID, $layoutID)
    {
        $blockIndex = $this->updateTargetAndGetBlockIndex($page, $blockID);
        $layoutIndex = $this->updateTargetAndGetBlockIndex($page, $layoutID, true);

        if ($blockIndex !== null && $layoutIndex !== null) {
            $this->targetDestination[$layoutIndex]['blocks'][] = $this->target[$blockIndex];
            unset($this->target[$blockIndex]);

            // Write the updated content back to the file
            file_put_contents($this->jsonFilePath, json_encode($this->json, JSON_PRETTY_PRINT));
        }
    }

    public function removeBlockFromLayout($page, $id, $layout)
    {

        $this->updateTarget($this->json, $page);
        $layoutIndex = $this->getBlockIndexById($layout, $this->target['blocks']);
        $blockIndex = $this->getBlockIndexById($id, $this->target['blocks'][$layoutIndex]['blocks']);

        if ($blockIndex !== null && $layoutIndex !== null) {
            // Copy the object to the top-level blocks
            $this->target['blocks'][] = $this->target['blocks'][$layoutIndex]['blocks'][$blockIndex];
            // Unset the object from layout blocks
            unset($this->target['blocks'][$layoutIndex]['blocks'][$blockIndex]);
            // Reindex the layout blocks to keep the same format after unset
            $this->target['blocks'][$layoutIndex]['blocks'] = array_values($this->target['blocks'][$layoutIndex]['blocks']);
            // Write the updated content back to the file
            file_put_contents($this->jsonFilePath, json_encode($this->json, JSON_PRETTY_PRINT));
        }
    }

    private function getJsonFromRequest($postValues)
    {
        $json = null;
        foreach ($postValues as $key => $value) {
            $json[$key] = $value;
        }

        // Loop on all files
        foreach ($_FILES as $key => $value) {
            $file = $value;
            $ext = explode('.', $file['name'])[1];
            $pathDestination = self::IMAGES_PATH . uniqid() . '.' . $ext;

            // Copy file and create folders if they don't exist
            if (!file_exists(dirname($pathDestination))) {
                mkdir(dirname($pathDestination), 0777, true);
            }
            copy($file['tmp_name'], $pathDestination);
            $json[$key] = $pathDestination;
        }
        return $json;
    }

    private function createJson($nameOfBloc, $postValues, $isBloc = true)
    {
        $json = [];
        if ($isBloc) {
            $json['block'] = $nameOfBloc;
        } else {
            $json['layout'] = $nameOfBloc;
        }
        $json['id'] = uniqid();
        foreach ($postValues as $key => $value) {
            if (str_contains($key, 'alt_file') || str_contains($key, 'url_link')) {
                continue;
            } else if (str_contains($key, 'file')) {
                preg_match('/file(\d+)/', $key, $matches);
                $i = (int) $matches[1];
                try {
                    $dynamicContents = $this->getDynamicContent($nameOfBloc . '.html');
                    $fileName = $this->createImage($value, $dynamicContents[$i - 1]);
                    $json[$key] = [$fileName, $postValues['alt_file' . $i]];
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
            } else if (str_contains($key, 'input')) {
                $i++;
                $json[$key] = $this->sanitizeInput($value);
            } else if (str_contains($key, 'link')) {
                $i++;
                $json[$key] = [$this->sanitizeInput($value, true), $postValues['url_link' . $i]];
            }
        }

        // Convert the array to a JSON string
        return json_encode($json);
    }

    private function sanitizeInput($input, $removeLineBreaks = false) {
        $trimmedInput = trim($input);
        $escapedInput = htmlentities($trimmedInput); // Escape injections using htmlentities
        if ($removeLineBreaks) {
            $sanitizedInput = preg_replace('/\n/', '', $escapedInput);
        } else {
            $sanitizedInput = str_replace("\n", "<br>", $escapedInput);
        }
        return $sanitizedInput;
    }

    private function createImage($file, $content)
    {
        try {
            $maxWidth = null;
            $maxHeight = null;

            // get max width and height inside content (type string like ' img | 50 | 50 ')
            $dimensions = explode(' | ', $content);
            $dimensions = array_map('trim', $dimensions);
            $maxWidth = $dimensions[1];
            $maxHeight = $dimensions[2];

            $image = new Resize($file, $maxWidth, $maxHeight);
            if ($maxWidth > HTMLConfig::BREAKPOINTS['m']) {
                $image->copyImage('_m', HTMLConfig::BREAKPOINTS['m']);
            }
            if ($maxWidth > HTMLConfig::BREAKPOINTS['s']) {
                $image->copyImage('_s', HTMLConfig::BREAKPOINTS['s']);
            }
            unlink($image->destination);
            return $image->fileName;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function updateJson($json, $postValues)
    {
        if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON format');
        }
        foreach ($postValues as $key => $value) {
            if (str_contains($key, 'file')) {
                preg_match('/file(\d+)/', $key, $matches);
                $i = (int) $matches[1];

                if (!$value || empty($value) || empty($value['name']) || $value === '{}') {
                    // get post value with key including previous_file
                    $fileKey = 'previous_file' . $i;
                    $previousFile = $postValues[$fileKey];
                    if ($previousFile) {
                        $json['file' . $i] = [$previousFile, $postValues['alt_file' . $i]];
                    }
                } else {
                    try {
                        $dynamicContents = $this->getDynamicContentFromFile($this->page, $json['id']);
                        $fileName = $this->createImage($value, $dynamicContents[$i - 1]);
                        $json[$key] = [$fileName, $postValues['alt_file' . $i]];

                        $fileKey = 'previous_file' . $i;
                        $previousFile = $postValues[$fileKey];
                        if ($previousFile) {
                            $this->deleteImage($previousFile);
                        }
                    } catch (Exception $e) {
                        throw new Exception($e->getMessage());
                    }
                }
            } else if (str_contains($key, 'input')) {
                preg_match('/input(\d+)/', $key, $matches);
                $i = (int) $matches[1];
                $json['input' . $i] = $this->sanitizeInput($value);
            } else if (str_contains($key, 'link')) {
                preg_match('/link(\d+)/', $key, $matches);
                $i = (int) $matches[1];
                $json['link' . $i] = [$this->sanitizeInput($value, true), $postValues['url_link' . $i]];
            }
        }
        return $json;
    }

    private function deleteImage($fileName)
    {
        unlink(self::IMAGES_PATH . $fileName . '.jpeg');
        unlink(self::IMAGES_PATH . $fileName . '_s.jpeg');
        unlink(self::IMAGES_PATH . $fileName . '_m.jpeg');
    }


    private function writeContentToFile($page, $nameOfBloc, $blockContent, $postValues, $isBloc = true)
    {
        $this->updateTarget($this->json, $page);
        $blockDatas = $this->createJson($nameOfBloc, $postValues, $isBloc);
        $blockDatas = json_decode($blockDatas, true);

        // add html
        $blockDatas['html'] = preg_replace('/\s{2,}/', ' ', $blockContent);
        if ($page === 'bh-header' || $page === 'bh-footer') {
            $blockDatas['id'] = 'bh-' . $nameOfBloc;
        }

        // Update the JSON with the new block information
        $this->target['blocks'][] = $blockDatas;

        // Update the JSON file
        file_put_contents($this->jsonFilePath, json_encode($this->json, JSON_PRETTY_PRINT));
        return $blockDatas;
    }

    private function updateTarget(&$json, $page, $targetDestination = false)
    {
        $targetParam = $targetDestination ? 'targetDestination' : 'target';
        $pathParts = explode('/', $page);
        $this->{$targetParam} = &$json[$pathParts[0]];

        if (count($pathParts) > 1) {
            foreach (array_slice($pathParts, 1) as $part) {
                $this->{$targetParam} = &$this->{$targetParam}['subPages'][$part];
            }
        }
    }

    public function getBlocContentFromFile($page, $id, $returnHtml = true)
    {
        $blockIndex = $this->updateTargetAndGetBlockIndex($page, $id);
        if ($blockIndex !== null) {
            if ($returnHtml) {
                return $this->target[$blockIndex]['html'];
            } else {
                return $this->target[$blockIndex];
            }
        } else {
            throw new Exception("Bloc not found");
        }
    }
}