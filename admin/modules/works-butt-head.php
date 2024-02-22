<?php
include_once('classes/ContentManager.php');
$contentManager = new ContentManager();
$contentFolder = $parent ? $parent . '/' . $page : $page;

// return 404 if page doesn't exist
if (!$page && is_dir('../previews/root')) {
    $contentFolder = 'root';
} else if (!$page || !is_dir('../previews/' . $contentFolder)) {
    include_once('modules/404.php');
    exit;
}

// list of blocs from templates folder
$files = scandir('../templates/blocs');
$blocsArray = [];
foreach ($files as $key => $value) {
    if ($value != '.' && $value != '..') {
        // key value array with key as name and value as path
        $name = explode('.', $value)[0];
        $blocsArray[$value] = $name;
    }
}
// list of layouts from templates folder
$layoutsFiles = scandir('../templates/layouts');
$layoutsArray = [];
foreach ($layoutsFiles as $key => $value) {
    if ($value != '.' && $value != '..') {
        // key value array with key as name and value as path
        $name = explode('.', $value)[0];
        $layoutsArray[$value] = $name;
    }
}
try {
    $contents = $contentManager->getFormatedPageContent($contentFolder);
} catch (Exception $e) {
    $contents = [];
}
?>

<input type="hidden" id="page" value="<?= $page ?>">
<input type="hidden" id="parent" value="<?= $parent ?>">

<header id="page-header">
    <h1>
        <?php if ($parent) {
            echo $parent . ' / ';
        } ?>
        <?= $page; ?>
    </h1>
    <ul>
        <?php if (!$parent && $page != 'root'): ?>
            <li><a href="#" class="button-link" onclick="updateURL('?page=addPage&parent=<?= $page ?>'); return false;"><?= _('Add page') ?></a></li>
        <?php endif; ?>
        <?php if ($page != 'root'): ?>
            <li><a href="#" class="button-link" onclick="deletePage('<?= $contentFolder ?>')"><?= _('Delete page') ?></a></li>
        <?php endif; ?>
        <li><a href="#" class="button-link" onclick="addURLParams('edit'); return false;"><?= _('Params') ?></a></li>
        <li><div  class="button-link" id="preview"><?= _('Preview') ?></div></li>
    </ul>
</header>

<div class="container">
    <div id="modal">
        <div id="modal-content"></div>
        <div class="modal-footer">
            <button id="modal-close" class="modal-button"><?= _('Cancel') ?></button>
            <button id="modal-save" class="modal-button"><?= _('Confirm') ?></button>
        </div>
    </div>

    <div class="left-navigation">
        <ul>
            <h3><?= _('Blocs') ?></h3>
            <?php foreach ($blocsArray as $key => $value) { ?>
                <li id="<?= $key ?>" class="bloc">
                    <?= $value ?></a>
                </li>
            <?php } ?>
            <h3><?= _('Layouts') ?></h3>
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
            $layout = isset($contentArray['layout']);
            $class = $layout ? 'layout' : 'draggable';
            $draggableAttribute = $layout ? '' : 'draggable="true"';
            $id = $contentArray['id'];
            $isBlocsInsideLayout = isset($contentArray['layout']) && isset($contentArray['blocs']);
            $blocs = $isBlocsInsideLayout ? $contentArray['blocs'] : [];


            // get contents from blocs inside contentArray
            $blocsContents = [];
            foreach ($blocs as $bloc) {
                $blocsContents[] = $bloc;
            }

            $html = "<section class=\"$class\" $draggableAttribute id=\"$id\">";
            $html .= "<header class=\"bloc-header\">";
            $html .= "<h3>" . ($contentArray['bloc'] ?? $contentArray['layout']) . "</h3>";
            $html .= "<div class=\"actions\">";
            $html .= "<div class=\"chevron-icons\">";
            $html .= "<span class=\"chevron-up-icon\" id=" . $contentArray['id'] . " data-layout-id=" . $layoutID . ">▲</span>";
            $html .= "<span class=\"chevron-down-icon\" id=" . $contentArray['id'] . " data-layout-id=" . $layoutID . ">▼</span>";
            $html .= "</div>";
            if ($layoutID) {
                $html .= "<button class=\"keep-out-button\" id=" . $contentArray['id'] . " data-layout-id=" . $layoutID . ">" . _('Get out of layout')  . "</button>";
            }
            $html .= "<button class=\"delete-button\" id=" . $contentArray['id'] . ">" . _('Delete')  . "</button>";
            $html .= "<button class=\"update-button\" id=" . $contentArray['id'] . ">" . _('Update') . "</button>";
            $html .= "</div>";
            $html .= "</header>";
            if ($layout) {
                $html .= "<div class=\"drop-zone drop-container\"></div>";
            }
            $html .= "<div class=\"content\">";

            foreach ($contentArray as $key => $value) {
                if (str_contains($key, 'input')) {
                    $html .= "<div class=\"text\">$value</div>";
                }
                if (str_contains($key, 'file')) {
                    $html .= "<div><img src=\"../previews/assets/img/$value[0].jpeg\" alt=\"$value[1]\"></div>";
                }
            }

            // Recursively generate HTML for subparts
            foreach ($blocsContents as $blocContent) {
                $html .= generateContentHTML($blocContent, $contents, $contentArray['id']);
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
</div>


<script src="js/works-butt-head.js" type="module"></script>

<script>
    function updateURL(url) {
        location.assign(url);
    }
    function addURLParams(param) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set(param, true);
        window.location.search = urlParams.toString();
    }
    function deletePage(page) {
        // open modal
        const modal = document.getElementById('modal');
        modal.style.display = 'block';
        const modalContent = document.getElementById('modal-content');
        modalContent.innerHTML = `Are you sure you want to delete ${page} ?`;
        document.getElementById('modal-close').addEventListener('click', () => {
            modal.style.display = 'none';
        })
        document.getElementById('modal-save').addEventListener('click', () => {
            confirmDelete(page);
        })
    }
</script>
<style>
    .drop-zone {
        border: 2px dashed #ccc;
        padding: 10px;
        text-align: center;
        height: 50px;
    }

    .chevron-up-icon,
    .chevron-down-icon {
        /* Add custom styling for the chevron icons */
        font-size: 20px;
        /* Adjust the size */
        color: #555;
        cursor: pointer;
    }
    .actions {
        display: flex;
    }
</style>