<?php
$html = file_get_contents('path/to/your/file.html');

$sections = explode('<!-- separator -->', $html);

foreach ($sections as $section) {
    preg_match('/<!-- json (.*?) -->/', $section, $matches);
    $json = json_decode($matches[1], true);

    // Access the content and JSON data for each section
    $content = strip_tags($section);
    unset($json['content']); // Replace 'content' with the appropriate key in your JSON

    // Use the $content and $json data as needed
    // e.g., echo $content; var_dump($json);
}
?>

<!-- EXEMPLE CONTENT -->
<div>
    <div>content1</div>
</div>
<!-- json { "content1": "blabla" } -->

<!-- separator -->

<div>
    <div>content2</div>
</div>
<!-- json { "content2": "hello" } -->

<!-- separator -->

<div>
    <div>content3</div>
</div>
<!-- json { "content3": "world" } -->