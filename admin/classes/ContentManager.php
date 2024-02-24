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
    const PREVIEW_PATH = __DIR__ . '/../../previews/';
    private $jsonFilePath = __DIR__ . '/../site.json';

    public function addContent($page, $bloc, ...$rest)
    {
        try {
            if ($page === 'bh-header' || $page === 'bh-footer') {
                $blocContent = $page === 'bh-header' ? file_get_contents(self::TEMPLATE_PATH . 'header.html', true) : file_get_contents(self::TEMPLATE_PATH . 'footer.html', true);
                $nameOfBloc = $page === 'bh-header' ? 'header' : 'footer';
                // if folder not exists
                if (!file_exists(self::PREVIEW_PATH . $page)) {
                    mkdir(self::PREVIEW_PATH . $page, 0777);
                }
            } else {
                // Get content from template
                $blocContent = file_get_contents(self::TEMPLATE_PATH . 'blocs/' . $bloc, true);
                $nameOfBloc = explode('.', $bloc)[0];
            }

            // Extract the style tag using regular expressions
            $styleTagPattern = '/<style\b[^>]*>(.*?)<\/style>/s';
            preg_match($styleTagPattern, $blocContent, $matches);
            $styleTag = $matches[0];

            // Remove the style tag from the content
            $contentWithoutStyle = preg_replace($styleTagPattern, '', $blocContent);
            $encapsulatedContent = $contentWithoutStyle . "\n" . $styleTag;
            $blocContent = $encapsulatedContent;

            // Write content to file
            $this->writeContentToFile($page, $nameOfBloc, $blocContent, $rest);

            // Return JSON response
            $json = $this->getJsonFromRequest($rest);
            return json_encode($json);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }



    public function createHeader(...$postValues)
    {
        try {
            $page = 'header';
            // Get content from template
            $blocContent = file_get_contents(self::TEMPLATE_PATH . 'header.html', true);

            // Add encapsulation div
            $nameOfBloc = 'header';

            // Extract the style tag using regular expressions
            $styleTagPattern = '/<style\b[^>]*>(.*?)<\/style>/s';
            preg_match($styleTagPattern, $blocContent, $matches);
            $styleTag = $matches[0];

            // Remove the style tag from the content
            $contentWithoutStyle = preg_replace($styleTagPattern, '', $blocContent);
            $encapsulatedContent = '<div class="' . $nameOfBloc . '">' . $contentWithoutStyle . "</div>\n" . $styleTag;
            $blocContent = $encapsulatedContent;

            // Write content to file
            $this->writeContentToFile($page, $nameOfBloc, $blocContent, $postValues);

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
            $json = json_decode(file_get_contents($this->jsonFilePath), true);
            $target = &$this->getTargetPartOfJson($json, $page);
            return (isset($target['blocs'])) ? $target['blocs'] : [];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getForm(...$postValues)
    {
        try {
            if (isset($postValues['bloc'])) {
                $dynamicContents = $this->getDynamicContent($postValues['bloc']);
                $formBuilder = new FormBuilder($dynamicContents, null);
            } else if ($postValues['page'] && $postValues['id']) {
                $json = json_decode(file_get_contents($this->jsonFilePath), true);
                $target = &$this->getTargetPartOfJson($json, $postValues['page']);
                $blockKey = $this->getBlockIndexById($postValues['id'], $target['blocs']);
                if ($blockKey !== null) {
                    $dynamicInputs = $this->extractDynamicInput($target['blocs'][$blockKey]['html']);
                    $values = $this->extractValues($target['blocs'][$blockKey]);
                    $formBuilder = new FormBuilder($dynamicInputs, $values);
                } else {
                    // find bloc inside layout
                    list('layout' => $layout, 'bloc' => $bloc) = $this->getBlockIndexInsideLayout($postValues['id'], $target['blocs']);
                    if ($layout !== null && $bloc !== null) {
                        $dynamicInputs = $this->extractDynamicInput($target['blocs'][$layout]['blocs'][$bloc]['html']);
                        $values = $this->extractValues($target['blocs'][$layout]['blocs'][$bloc]);
                        $formBuilder = new FormBuilder($dynamicInputs, $values);
                    }
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
            $filePath = self::PREVIEW_PATH . $page . '/index.html';
            return file_get_contents($filePath);
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
        // $filePath = self::PREVIEW_PATH . $page . '/index.html';
        $json = json_decode(file_get_contents($this->jsonFilePath), true);
        $target = &$this->getTargetPartOfJson($json, $page);
        try {
            if (!$layout) {
                $blocKey = $this->getBlockIndexById($id, $target['blocs']);
                if ($blocKey !== null && $blocKey > 0) {
                    $temp = $target['blocs'][$blocKey];
                    $target['blocs'][$blocKey] = $target['blocs'][$blocKey - 1];
                    $target['blocs'][$blocKey - 1] = $temp;
                }
                // Write the updated content back to the file
                file_put_contents($this->jsonFilePath, json_encode($json, JSON_PRETTY_PRINT));
            } else {
                $layoutIndex = $this->getBlockIndexById($layout, $target['blocs']);
                $blockIndex = $this->getBlockIndexById($id, $target['blocs'][$layoutIndex]['blocs']);
                if ($blockIndex !== null && $blockIndex > 0) {
                    $temp = $target['blocs'][$layoutIndex]['blocs'][$blockIndex];
                    $target['blocs'][$layoutIndex]['blocs'][$blockIndex] = $target['blocs'][$layoutIndex]['blocs'][$blockIndex - 1];
                    $target['blocs'][$layoutIndex]['blocs'][$blockIndex - 1] = $temp;
                }
                // Write the updated content back to the file
                file_put_contents($this->jsonFilePath, json_encode($json, JSON_PRETTY_PRINT));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function moveBlockDown($page, $id, $layout)
    {
        $json = json_decode(file_get_contents($this->jsonFilePath), true);
        $target = &$this->getTargetPartOfJson($json, $page);
        try {
            if (!$layout) {
                $blocKey = $this->getBlockIndexById($id, $target['blocs']);
                if ($blocKey !== null && $blocKey < count($target['blocs']) - 1) {
                    $temp = $target['blocs'][$blocKey];
                    $target['blocs'][$blocKey] = $target['blocs'][$blocKey + 1];
                    $target['blocs'][$blocKey + 1] = $temp;
                }

                // Write the updated content back to the file
                file_put_contents($this->jsonFilePath, json_encode($json, JSON_PRETTY_PRINT));
            } else {
                $layoutIndex = $this->getBlockIndexById($layout, $target['blocs']);
                $blockIndex = $this->getBlockIndexById($id, $target['blocs'][$layoutIndex]['blocs']);
                if ($blockIndex !== null && $blockIndex < count($target['blocs'][$layoutIndex]['blocs']) - 1) {
                    $temp = $target['blocs'][$layoutIndex]['blocs'][$blockIndex];
                    $target['blocs'][$layoutIndex]['blocs'][$blockIndex] = $target['blocs'][$layoutIndex]['blocs'][$blockIndex + 1];
                    $target['blocs'][$layoutIndex]['blocs'][$blockIndex + 1] = $temp;
                }
                // Write the updated content back to the file
                file_put_contents($this->jsonFilePath, json_encode($json, JSON_PRETTY_PRINT));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
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
            $blocContent = file_get_contents(self::TEMPLATE_PATH . 'layouts/' . $layout, true);
            $nameOfBloc = explode('.', $layout)[0];

            // Write content to file
            $this->writeContentToFile($page, $nameOfBloc, $blocContent, [], false);

            // Return JSON response
            $json = $this->getJsonFromRequest([]);
            return json_encode($json);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getDynamicContent($bloc)
    {
        try {
            if ($bloc === 'header.html' || $bloc === 'footer.html') {
                $content = file_get_contents(self::TEMPLATE_PATH . $bloc, true);
            } else {
                // get content from template
                $content = file_get_contents(self::TEMPLATE_PATH . 'blocs/' . $bloc, true);
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
        // Remove bloc and id from values
        unset($data['bloc']);
        unset($data['id']);
        unset($data['html']);

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
        // Update content in file
        $this->updateContentInFileById($page, $id, $postValues);
        // Return JSON response
        return ['success' => true];
    }

    public function deleteContent($page, $id)
    {
        $json = json_decode(file_get_contents($this->jsonFilePath), true);
        $target = &$this->getTargetPartOfJson($json, $page);
        $blockIndex = $this->getBlockIndexById($id, $target['blocs']);

        // delete bloc
        if ($blockIndex !== null) {
            unset($target['blocs'][$blockIndex]);
            $target['blocs'] = array_values($target['blocs']);
        }

        // update json
        file_put_contents($this->jsonFilePath, json_encode($json, JSON_PRETTY_PRINT));
        return ['success' => true];
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
            if (isset($content['layout']) && count($content['blocs']) > 0) {
                foreach ($content['blocs'] as $blocKey => $bloc) {
                    if (isset($bloc['id']) && $bloc['id'] === $id) {
                        return ['layout' => $key, 'bloc' => $blocKey];
                    }
                }
            }
        }
        return ['layout' => null, 'bloc' => null];
    }

    public function addBlockToLayout($page, $blockID, $layoutID)
    {
        $json = json_decode(file_get_contents($this->jsonFilePath), true);
        $target = &$this->getTargetPartOfJson($json, $page);
        $blockIndex = $this->getBlockIndexById($blockID, $target['blocs']);
        $layoutIndex = $this->getBlockIndexById($layoutID, $target['blocs']);
        if ($blockIndex !== null && $layoutIndex !== null) {
            $target['blocs'][$layoutIndex]['blocs'][] = $target['blocs'][$blockIndex];
            unset($target['blocs'][$blockIndex]);
            $target['blocs'] = array_values($target['blocs']);

            // Write the updated content back to the file
            file_put_contents($this->jsonFilePath, json_encode($json, JSON_PRETTY_PRINT));
        }
    }

    public function removeBlockFromLayout($page, $id, $layout)
    {
        $json = json_decode(file_get_contents($this->jsonFilePath), true);
        $target = &$this->getTargetPartOfJson($json, $page);
        $layoutIndex = $this->getBlockIndexById($layout, $target['blocs']);
        $blockIndex = $this->getBlockIndexById($id, $target['blocs'][$layoutIndex]['blocs']);
        
        if ($blockIndex !== null && $layoutIndex !== null) {
            // Copy the object to the top-level blocs
            $target['blocs'][] = $target['blocs'][$layoutIndex]['blocs'][$blockIndex];
            // Unset the object from layout blocks
            unset($target['blocs'][$layoutIndex]['blocs'][$blockIndex]);
            // Reindex the layout blocks to keep the same format after unset
            $target['blocs'][$layoutIndex]['blocs'] = array_values($target['blocs'][$layoutIndex]['blocs']);
            // Write the updated content back to the file
            file_put_contents($this->jsonFilePath, json_encode($json, JSON_PRETTY_PRINT));
        }
    }

    private function getJsonFromRequest($postValues)
    {
        $json = null;

        // Loop on all input posts
        foreach ($postValues as $key => $value) {
            $json[$key] = $value;
        }

        // Loop on all files
        foreach ($_FILES as $key => $value) {
            // If key contains file, copy the file to the previews folder
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
            $json['bloc'] = $nameOfBloc;
        } else {
            $json['layout'] = $nameOfBloc;
        }
        $json['id'] = uniqid();
        foreach ($postValues as $key => $value) {
            if (str_contains($key, 'alt_file')) {
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
                $json[$key] = $value;
            }
        }

        // Convert the array to a JSON string
        return json_encode($json);
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

                // if key contains file
                // copy file to previews folder
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
                    } catch (Exception $e) {
                        throw new Exception($e->getMessage());
                    }
                }
            } else if (str_contains($key, 'input')) {
                preg_match('/input(\d+)/', $key, $matches);
                $i = (int) $matches[1];
                $json['input' . $i] = $value;
            }
        }
        return $json;
    }



    private function writeContentToFile($page, $nameOfBloc, $blocContent, $postValues, $isBloc = true)
    {
        $json = json_decode(file_get_contents($this->jsonFilePath), true);

        // Get the part of the JSON to update
        $target = &$this->getTargetPartOfJson($json, $page);

        $blocDatas = $this->createJson($nameOfBloc, $postValues, $isBloc);
        $blocDatas = json_decode($blocDatas, true);

        // add html
        $blocDatas['html'] = preg_replace('/\s{2,}/', ' ', $blocContent);

        // Update the JSON with the new bloc information
        $target['blocs'][] = $blocDatas;

        // Update the JSON file
        file_put_contents($this->jsonFilePath, json_encode($json, JSON_PRETTY_PRINT));
    }

    private function &getTargetPartOfJson(&$json, $page)
    {
        $pathParts = explode('/', $page);
        $target = &$json[$pathParts[0]];
    
        if (count($pathParts) > 1) {
            foreach (array_slice($pathParts, 1) as $part) {
                $target = &$target['subPages'][$part];
            }
        }
        
        return $target;
    }

    public function getBlocContentFromFile($page, $id)
    {
        $json = json_decode(file_get_contents($this->jsonFilePath), true);
        $target = &$this->getTargetPartOfJson($json, $page);

        $blockKey = $this->getBlockIndexById($id, $target['blocs']);
        if ($blockKey !== null) {
            return $target['blocs'][$blockKey]['html'];
        } else {
            // find bloc inside layout
            list('layout' => $layout, 'bloc' => $bloc) = $this->getBlockIndexInsideLayout($id, $target['blocs']);
            if ($layout !== null && $bloc !== null) {
                return $target['blocs'][$layout]['blocs'][$bloc]['html'];
            }
        }
        throw new Exception("Bloc not found");
    }

    private function getBlocContent($fileContent, $index)
    {
        $separatorPositions = $this->getSeparatorPositions($fileContent);
        if ($index < 0 || $index >= count($separatorPositions)) {
            throw new Exception("Invalid index");
        }

        // Get the content between separators at the specified index
        $startPos = $separatorPositions[$index] + strlen('<!-- separator -->');
        $endPos = ($index + 1 < count($separatorPositions)) ? $separatorPositions[$index + 1] : strlen($fileContent);
        return substr($fileContent, $startPos, $endPos - $startPos);
    }

    private function getBlocContentById($fileContent, $id)
    {
        // Find the position of the "id" within the file content
        $idPosition = strpos($fileContent, "\"id\":\"$id\"");
        if ($idPosition === false) {
            throw new Exception("Id not found");
        }

        // Find the positions of the separators
        $separatorPositions = $this->getSeparatorPositions($fileContent);

        // Find the nearest separator positions before and after the "id" position
        $startPos = 0;
        $endPos = strlen($fileContent);
        foreach ($separatorPositions as $position) {
            if ($position < $idPosition) {
                $startPos = $position + strlen('<!-- separator -->');
            } else {
                $endPos = $position;
                break;
            }
        }

        // Use the positions to extract the content between separators
        return substr($fileContent, $startPos, $endPos - $startPos);
    }

    private function updateContentInFileById($page, $id, $postValues)
    {
        $this->page = $page;

        if ($id === 'bh-header' || $id === 'bh-footer') {
            // $blocContent = str_replace('<!-- separator -->', '', $fileContent);
            // $startPos = 0;
            // $endPos = strlen($fileContent);
        } else {
            $json = json_decode(file_get_contents($this->jsonFilePath), true);
            $target = &$this->getTargetPartOfJson($json, $page);

            $blocKey = $this->getBlockIndexById($id, $target['blocs']);
            if ($blocKey === null) {
                list('layout' => $layout, 'bloc' => $bloc) = $this->getBlockIndexInsideLayout($id, $target['blocs']);
                if ($layout !== null && $bloc !== null) {
                    $target['blocs'][$layout]['blocs'][$bloc] = $this->updateJson($target['blocs'][$layout]['blocs'][$bloc], $postValues);
                } else {
                    throw new Exception("Id not found");
                }
            } else {
                $target['blocs'][$blocKey] = $this->updateJson($target['blocs'][$blocKey], $postValues);
            }
        }

        // Write the updated content back to the file
        file_put_contents($this->jsonFilePath, json_encode($json, JSON_PRETTY_PRINT));
    }

    private function deleteContentFromFileById($page, $id)
    {
        $filePath = self::PREVIEW_PATH . $page . '/index.html';
        $fileContent = file_get_contents($filePath);

        // Find the position of the "id" within the file content
        $idPosition = strpos($fileContent, "\"id\":\"$id\"");
        if ($idPosition === false) {
            throw new Exception("Id not found");
        }

        // Find the positions of the separators
        $separatorPositions = $this->getSeparatorPositions($fileContent);

        // Find the nearest separator positions before and after the "id" position
        $startPos = 0;
        $endPos = strlen($fileContent);
        foreach ($separatorPositions as $position) {
            if ($position < $idPosition) {
                $startPos = $position;
            } else {
                $endPos = $position;
                break;
            }
        }
        if ($endPos === false) {
            throw new Exception("Invalid id");
        }
        $content = substr_replace($fileContent, "", $startPos, $endPos - $startPos);

        // Get the bloc content
        $blocContent = substr($fileContent, $startPos, $endPos - $startPos);

        // Find the images and remove
        preg_match('/"file\d+":("([a-z\d]+)")/', $blocContent, $matches);
        for ($i = 0; $i < count($matches); $i++) {
            unlink(self::IMAGES_PATH . $matches[$i] . '.jpeg');
            unlink(self::IMAGES_PATH . $matches[$i] . '_s.jpeg');
            unlink(self::IMAGES_PATH . $matches[$i] . '_m.jpeg');
        }
        // Write the updated content back to the file
        file_put_contents($filePath, $content);
    }

    private function deleteContentFromFile($page, $index)
    {
        $filePath = self::PREVIEW_PATH . $page . '/index.html';
        $fileContent = file_get_contents($filePath);

        $separatorPositions = $this->getSeparatorPositions($fileContent);

        if ($index < 0 || $index >= count($separatorPositions)) {
            throw new Exception("Invalid index");
        }

        // Get the start and end positions of the content to be deleted
        $startPos = $separatorPositions[$index];
        $endPos = ($index + 1 < count($separatorPositions)) ? $separatorPositions[$index + 1] : strlen($fileContent);

        // Remove the content between the separators
        $updatedFileContent = substr_replace($fileContent, '', $startPos, $endPos - $startPos);

        // Write the updated content back to the file
        file_put_contents($filePath, $updatedFileContent);
    }

    private function splitContentBySeparator($fileContent)
    {
        $separatorPositions = $this->getSeparatorPositions($fileContent);
        $blocs = [];
        $start = 0;
        foreach ($separatorPositions as $position) {
            $block = trim(substr($fileContent, $start, $position - $start));
            if ($block !== '') {
                $blocs[] = $block;
            }
            $start = $position + strlen('<!-- separator -->');
        }
        $lastBlock = trim(substr($fileContent, $start));
        if ($lastBlock !== '') {
            $blocs[] = $lastBlock;
        }
        return $blocs;
    }

    private function getSeparatorPositions($fileContent)
    {
        preg_match_all('/<!-- separator -->/', $fileContent, $matches, PREG_OFFSET_CAPTURE);
        return array_column($matches[0], 1);
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
} ?>