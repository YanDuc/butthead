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
$canDelete = $page !== 'root' && $_SESSION['loggedIn']['admin'];
$canAdd = $page !== 'root' && !$parent && $_SESSION['loggedIn']['admin'];
?>

<input type="hidden" id="page" value="<?= $page ?>">
<input type="hidden" id="parent" value="<?= $parent ?>">

<header id="page-header">
    <h1>
        <?= $parent ? $parent . '/' . $page : $page ?>
    </h1>
    <div class="tabs">
        <ul class="tab-container">
            <li class="tab">
                <a href="#" class="tab-link <?= $_GET['edit'] ? '' : 'active' ?>"
                    onclick="removeURLParams('edit'); return false;">
                    <?= _('Content') ?>
                </a>
            </li>
            <li class="tab">
                <a href="#" class="tab-link <?= $_GET['edit'] ? 'active' : '' ?>"
                    onclick="addURLParams('edit'); return false;">
                    <?= _('SEO') ?>
                </a>
            </li>
        </ul>
        <ul>
            <?php if ($canAdd): ?>
                <li class="button-tab">
                    <a href="#" class="button-link" onclick="updateURL('?page=addPage&parent=<?= $page ?>'); return false;">
                        <?= _('Add page') ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($canDelete): ?>
                <li class="button-tab">
                    <a href="#" class="button-link" onclick="deletePage('<?= $contentFolder ?>')">
                        <?= _('Delete page') ?>
                    </a>
                </li>
            <?php endif; ?>
            <li class="button-tab">
                <div class="button-link" id="preview">
                    <?= _('Preview') ?>
                </div>
            </li>
        </ul>
    </div>
</header>

<div class="page-container">
<?php
if ($_GET['edit']) {
    include 'modules/pageMeta.php';
} else {
    include 'modules/pageContent.php';
}
?>
</div>
<script>
    function removeURLParams(param) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete(param);
        window.location.search = urlParams.toString();
    }
</script>