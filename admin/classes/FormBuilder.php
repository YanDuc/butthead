<?php
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Builder.php';
class FormBuilder
{
    private $dynamicContent;
    private $form = [];
    public function __construct($dynamicContent, $values, $insert_at = null)
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
            } elseif (str_contains($content, 'link')) {
                $this->createLink('link' . $key + 1, $content, $value);
            }
        }
        if ($insert_at) {
            $this->form[] = '<input type="radio" id="topChoice" name="position" value="top" />
            <label for="topChoice">' . _('Top') .'</label>
            <input type="radio" id="bottomChoice" name="position" value="bottom" checked/>
            <label for="bottomChoice">' . _("Bottom") . '</label>';
        }
    }

    public function __get($form)
    {
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
        $dimensions = array_map('trim', $dimensions);
        if (isset($dimensions[1]) && !is_numeric($dimensions[1])) {
            if (str_contains($content, 'img') && strtolower($dimensions[1]) === 'resize') {
                return 'File';
            } else {
                return $dimensions[1];
            }
        } else {
            if (str_contains($content, 'input')) {
                return 'Input';
            } elseif (str_contains($content, 'textarea')) {
                return 'Textarea';
            } elseif (str_contains($content, 'link')) {
                return 'Link';
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
        $value = $value ? str_replace('<br>', "\n", strip_tags($value)) : '';
        $textarea = $label . '<textarea name="' . $name . '" id="' . $name . '" cols="30" rows="' . $this->textareaRows($max) . '" maxlength="' . $max . '" minlength="' . $min . '" required>' . $value . '</textarea>';
        $this->form[] = $textarea;
    }

    private function textareaRows($maxlength)
    {
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
        $alt_value = !empty($value[1]) ? $value[1] : '';
        $previous_value = !empty($value[0]) ? $value[0] : '';
        $label = $this->getLabel($data) ? '<label for="' . $name . '">' . $this->getLabel($data) . '</label>' : '';
        $input = "<input type='file' name='$name' id='$name'>";
        $altInput = "<input type='text' name='alt_$name' maxlength='100' placeholder='Alternative text' id='alt_$name' value='$alt_value'>";

        $previewSrc = !empty($value) ? "../assets/img/$value[0].jpeg" : '#';
        $previewImage = "<img src='$previewSrc' height='100' id='preview_$name'>";

        $this->form[] = $label . "<div style='display: flex;'><div style='width: 85%; margin-right: 10px;'>" . $input . $altInput . "</div><div style='width: 15%; text-align: center;'>" . $previewImage . "</div></div>";
        if (!empty($value)) {
            $this->form[] = "<input type='hidden' name='previous_$name' id='previous_$name' value='$previous_value'>";
        }
    }

    private function createLink($name, $data, $value = '')
    {
        $builder = new Builder();
        $flatPages = $builder->getFlatPages();
        $label = $this->getLabel($data) ? '<label for="' . $name . '">' . $this->getLabel($data) . '</label><br>' : '';
        if (isset($flatPages) && !empty($flatPages)) {
            $select = '<select name="url_' . $name . '" id="url_' . $name . '" required>';
            $select .= '<option value="">Select Page</option>';
            foreach ($flatPages as $page) {
                $select .= '<option value="' . $page . '"' . ($value[1] == $page ? 'selected' : '') . '>' . $page . '</option>';
            }
            $select .= '</select>';
        }

        $input = $label . $select . '<input type="text" placeholder="Name" style="margin-top: 10px;" name="' . $name . '" id="' . $name . '" value="' . $value[0] . '" required>';
        $this->form[] = $input;
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