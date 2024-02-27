<div class="left-navigation">
    <ul>
        <h3>
            <?= _('Blocs') ?>
        </h3>
        <?php foreach ($blocksArray as $key => $value) { ?>
            <li id="<?= $key ?>" class="block">
                <?= $value ?></a>
            </li>
        <?php } ?>
        <h3>
            <?= _('Layouts') ?>
        </h3>
        <?php foreach ($layoutsArray as $key => $value) { ?>
            <li id="<?= $key ?>" class="layout">
                <?= $value ?></a>
            </li>
        <?php } ?>
    </ul>
</div>

<!-- <div class="right-content" id="response">
    </div> -->
<div class="right-content" id="page-content">
    <?php
    function generateContentHTML($contentArray, $contents, $layoutID = null)
    {
        global $blocksArray;
        $layout = isset($contentArray['layout']);
        $class = $layout ? 'layout' : 'draggable';
        $draggableAttribute = $layout ? '' : 'draggable="true"';
        $id = $contentArray['id'];
        $isBlocsInsideLayout = isset($contentArray['layout']) && isset($contentArray['blocks']);
        $blocks = $isBlocsInsideLayout ? $contentArray['blocks'] : [];


        // get contents from blocks inside contentArray
        $blocksContents = [];
        foreach ($blocks as $block) {
            $blocksContents[] = $block;
        }

        $html = "<section class=\"$class\" $draggableAttribute id=\"$id\">";
        $html .= "<header class=\"block-header\">";
        $html .= "<h3>" . ($contentArray['block'] ?? $contentArray['layout']) . "</h3>";
        if ($layout) {
            $html .= "<div>";
            $html .= "<label>" . _('Add block') . ":</label>
            <select class=\"layout-blocks\" id=" . $contentArray['id'] . ">
            <option value=\"\"></option>";
            foreach ($blocksArray as $key => $value) {
                $html .= "<option value=\"" . $key . "\">" . $value . "</option>";
            }
            $html .= "</select>";
            $html .= "</div>";
        }
        $html .= "<div class=\"actions\">";
        $html .= "<div class=\"chevron-icons\">";
        $html .= "<span class=\"chevron-up-icon\" id=" . $contentArray['id'] . " data-layout-id=" . $layoutID . ">▲</span>";
        $html .= "<span class=\"chevron-down-icon\" id=" . $contentArray['id'] . " data-layout-id=" . $layoutID . ">▼</span>";
        $html .= "</div>";
        if ($layoutID) {
            $html .= "<button class=\"keep-out-button\" id=" . $contentArray['id'] . " data-layout-id=" . $layoutID . ">" . _('Get out of layout') . "</button>";
        }
        $html .= "<button class=\"delete-button\" id=" . $contentArray['id'] . ">" . _('Delete') . "</button>";
        $html .= "<button class=\"update-button\" id=" . $contentArray['id'] . ">" . _('Update') . "</button>";
        $html .= "</div>";
        $html .= "</header>";
        $html .= "<div class=\"content\">";

        foreach ($contentArray as $key => $value) {
            if (str_contains($key, 'input')) {
                $html .= "<div class=\"text\">$value</div>";
            }
            if (str_contains($key, 'file')) {
                $html .= "<div><img src=\"../assets/img/$value[0].jpeg\" alt=\"$value[1]\"></div>";
            }
            if (str_contains($key, 'link')) {
                $html .= "<div class=\"text\">$value[1] $value[0]</div>";
            }
        }

        // Recursively generate HTML for subparts
        foreach ($blocksContents as $blockContent) {
            $html .= generateContentHTML($blockContent, $contents, $contentArray['id']);
        }

        $html .= "</div>";
        $html .= "</section>";

        return $html;
    }


    // Generate the HTML for each content
    foreach ($contents as $index => $contentArray) {
        echo generateContentHTML($contentArray, $contents);
    }
    ?>
</div>
<script src="js/pageContent.js" type="module"></script>