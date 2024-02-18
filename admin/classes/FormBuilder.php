<?php
require_once __DIR__ . '/Logger.php';
class FormBuilder
{
    private $dynamicContent;
    private $form = [];
    public function __construct($dynamicContent, $values)
    {
        $this->dynamicContent = $dynamicContent;
        foreach ($this->dynamicContent as $key => $content) {
            $value = isset($values[$key]) ? $values[$key] : '';
            if (str_contains($content, 'input')) {
                $this->createInput('input' . $key + 1, $content, $value);
            } elseif (str_contains($content, 'textarea')) {
                $this->createTextarea('input' . $key + 1, $content, $value);
            } elseif (str_contains($content, 'img')) {
                $this->createInputFile('file' . $key + 1, $content, $value);
            } elseif (str_contains($content, 'date')) {
                $this->inputDate('input' . $key + 1, $content, $value);
            }
        }
    }

    public function __get($form) {
        return $this->form;
    }

    private function getMin($content)
    {
        // get maxlength and  minlenght content (type string like ' input | 50 | 50 ' or ' input | "myLabel" | 50 | 50 ')
        $dimensions = explode(' | ', $content);
        foreach ($dimensions as $dimension) {
            if (is_numeric(trim($dimension))) {
                return $dimension;
            }
        }
        return '';
    }

    private function getMax($content)
    {
        // get maxlength and  minlenght content (type string like ' input | 50 | 50 ' or ' input | "myLabel" | 50 | 50 ')
        $dimensions = explode('|', $content);
        // get 2 lasts elements of array and trim
        $lastTwoElements = array_slice($dimensions, -2);
        $lastTwoElements = array_map('trim', $lastTwoElements);

        if (is_numeric($lastTwoElements[1]) && is_numeric($lastTwoElements[0])) {
            return $lastTwoElements[1];
        } else {
            return '';
        }
    }

    private function getLabel($content)
    {
        // get label content (type string like ' input | "myLabel" | 50 | 50 ')
        $dimensions = explode('|', $content);
        if (!is_numeric(trim($dimensions[1]))) {
            return $dimensions[1];
        } else {
            if (str_contains($content, 'input')) {
                return 'Input';
            } elseif (str_contains($content, 'textarea')) {
                return 'Textarea';
            } else {
                return 'File';
            }
        }
    }

    private function createTextarea($name, $data, $value = '')
    {
        $label = $this->getLabel($data) ? '<label for="' . $name . '">' . $this->getLabel($data) . '</label>' : '';
        $max = $this->getMax($data);
        $min = $this->getMin($data);
        $textarea = $label . '<textarea name="' . $name . '" id="' . $name . '" cols="30" rows="' . $this->textareaRows($max) . '" maxlength="' . $max . '" minlength="' . $min . '" required>' . $value . '</textarea>';
        $this->form[] = $textarea;
    }

    private function textareaRows($maxlength) {
        if (!$maxlength || !is_numeric($maxlength)) {
            return 5;
        }
        $rows = ceil($maxlength / 100);
        if ($rows < 5) {
            $rows = 5;
        } elseif ($rows > 10) {
            $rows = 10;
        }
        return $rows;
    }
    
    private function createInput($name, $data, $value = '')
    {
        $label = $this->getLabel($data) ? '<label for="' . $name . '">' . $this->getLabel($data) . '</label>' : '';
        $max = $this->getMax($data);
        $min = $this->getMin($data);
        $input = $label . '<input type="text" name="' . $name . '" id="' . $name . '" maxlength="' . $max . '" minlength="' . $min . '" value="' . $value . '" required>';
        $this->form[] = $input;
    }

    private function createInputFile($name, $data, $value = '')
    {
        $label = $this->getLabel($data) ? '<label for="' . $name . '">' . $this->getLabel($data) . '</label>' : '';
        $input = "<input type='file' name='$name' id='$name'>";
        $altInput = "<input type='text' name='alt_$name' maxlength='100' placeholder='Alternative text' id='alt_$name' value='$value[1]'>";
        
        $previewSrc = !empty($value) ? "../previews/assets/img/$value[0].jpeg" : '#';
        $previewImage = "<img src='$previewSrc' height='100' id='preview_$name'>";
        
        $this->form[] = $label . "<div style='display: flex;'><div style='width: 85%; margin-right: 10px;'>" . $input . $altInput . "</div><div style='width: 15%; text-align: center;'>" . $previewImage . "</div></div>";
        if (!empty($value)) {
            $this->form[] = "<input type='hidden' name='previous_$name' id='previous_$name' value='$value[0]'>";
        }
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

    private function inputDate($name, $data, $value = '') 
    {
        $label = $this->getLabel($data) ? '<label for="' . $name . '">' . $this->getLabel($data) . '</label>' : '';
        $this->form[] = $label . "<input type='date' name='$name' id='$name' value='$value'>";
    }
}