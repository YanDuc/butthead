<?php
include_once('classes/PageManager.php');
$pageManager = new PageManager();
$pages = $pageManager->getPages();
?>

<aside>
  <h2>
    <?= _('Pages') ?>
  </h2>
  <a class="button-link" href="?page=addPage">
    <?= _('Add page') ?>
  </a>
  <ul id="sortable">
    <?php foreach ($pages as $id => $data): ?>
      <?php if (!isset($data['pageName']) || str_starts_with($id, 'bh-')) { continue; } ?>
      <?php $unauthorizedUsers = !empty($data['unauthorizedUsers']) ? $data['unauthorizedUsers'] : null; ?>
      <?php $allowChange = !empty($unauthorizedUsers) && in_array($_SESSION['loggedIn']['email'], $unauthorizedUsers) ? false : true ?>
      <div class="drop-zone-nav" id="<?= $id ?>"></div>
      <li id="<?= $id ?>" <?php if ($id !== 'root') { ?>draggable="true" ondragstart="handleDragStart(event, '<?= $id ?>')"<?php } ?>>
        <?php if ($allowChange): ?>
          <a href="#" onclick="changePage('<?= $id ?>', null, '<?= $unauthorizedUsers ?>'); return false;">
            <?= $id; ?>
          </a>
        <?php endif; ?>
        <?php if (!$allowChange): ?>
          <div class="disabled">
            <?= $id; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($data['subPages'])): ?>
          <ul>
            <?php
            foreach ($data['subPages'] as $subId => $subData):
              $pagePath = $id . '/' . $subId;
              ?>
              <?php $allowChangeSub = $subData['unauthorizedUsers'] && in_array($_SESSION['loggedIn']['email'], $subData['unauthorizedUsers']) ? false : true ?>
              <div class="drop-zone-nav sub-page" id="<?= $pagePath ?>"></div>
              <li id="<?= $pagePath ?>" class="subpage" draggable="true"
                ondragstart="handleSubPageDragStart(event, '<?= $pagePath ?>')">
                <?php if ($allowChangeSub): ?>
                  <a href="#"
                    onclick="changePage('<?= $subId ?>', '<?= $id ?>', '<?= $unauthorizedUsers ?>'); return false;">
                    <?= $subId; ?>
                  </a>
                <?php endif; ?>
                <?php if (!$allowChangeSub): ?>
                  <div class="disabled">
                    <?= $subId; ?>
                  </div>
                <?php endif; ?>
              </li>
              <?php if (end($data['subPages']) === $subData): ?>
                <div class="drop-zone-nav"></div>
              <?php endif; ?>
            <?php endforeach; ?>
          </ul>
        <?php endif ?>
      </li>
      <?php if (end($pages) === $data): ?>
        <div class="drop-zone-nav"></div>
      <?php endif; ?>
    <?php endforeach; ?>
  </ul>

  <h2>
    <?= _('Elements') ?>
  </h2>
  <ul id="page-elements">
    <li><a href="?page=header">
        <?= _('Header') ?>
      </a></li>
    <li><a href="?page=footer">
        <?= _('Footer') ?>
      </a></li>
  </ul>
</aside>

<script type="module">
  import { getResponse } from "./js/ajax_handler.js";

  async function changeOrder(pagePath, belowPagePath, topPagePath) {
    const changeOrderForm = new FormData();
    changeOrderForm.append("pagePath", pagePath);
    changeOrderForm.append("belowPagePath", belowPagePath);
    changeOrderForm.append("topPagePath", topPagePath);
    try {
      await getResponse("PageManager", "changeOrder", changeOrderForm);
      location.reload();
    } catch (error) {
      console.error("error:", error);
    }
  }
  window.changeOrder = changeOrder;
</script>

<script>
  function changePage(page, parent = null, unauthorized) {
    if (parent) {
      location.assign('?page=' + page + '&parent=' + parent);
    } else {
      location.assign('?page=' + page);
    }
  }

  // Get all drop zones
  const dropZones = document.querySelectorAll('.drop-zone-nav');

  // Function to update drop zone height
  function updateDropZoneHeight(dropZone, height) {
    dropZone.style.height = height + 'px';
  }

  // Function to handle drag start
  function handleDragStart(e, id) {
    e.dataTransfer.setData('text/plain', id);
  }

  // For the subpage items
  function handleSubPageDragStart(e, pagePath) {
    e.stopPropagation();
    e.dataTransfer.setData('text/plain', pagePath);
  }


  // Add event listeners for "dragover" on drop zones
  dropZones.forEach(dropZone => {
    dropZone.addEventListener('dragover', function (e) {
      e.preventDefault(); // Allow drop
      updateDropZoneHeight(dropZone, 50); // Update drop zone height with transition
      dropZone.style.backgroundColor = 'rgba(0, 0, 0, 0.1)';
    });
  });

  // Add event listeners for "dragleave" on drop zones to reset height
  dropZones.forEach(dropZone => {
    dropZone.addEventListener('dragleave', function () {
      updateDropZoneHeight(dropZone, 10); // Reset drop zone height with transition
      dropZone.style.backgroundColor = 'rgba(0, 0, 0, 0)';
    });
  });

  // Function to handle drop event
  async function handleDrop(e) {
    e.preventDefault();
    const draggedPath = e.dataTransfer.getData('text/plain');

    if (!draggedPath || draggedPath === 'root') {
      return;
    }
    const droppedOnId = e.target.id;
    const previousSibblingId = e.target.previousElementSibling?.id
    const isDraggedSubPage = draggedPath.includes('/')
    const isDroppedOnSubPage = droppedOnId.includes('/');

    if (isDraggedSubPage) {
      if (e.target.classList.contains('drop-zone-nav')) {
        if (e.target.id === draggedPath) {
          return; // Do not reorder if dropping on the same element
        }

        // Move dragged element to the new position
        try {
          await changeOrder(draggedPath, droppedOnId, previousSibblingId);
        } catch (err) {
          console.error('change order', err)
        }
      }
    } else {
      if (e.target.classList.contains('drop-zone-nav')) {
        if (e.target.id === draggedPath) {
          return; // Do not reorder if dropping on the same element
        }

        // Move dragged element to the new position
        try {
          await changeOrder(draggedPath, droppedOnId, previousSibblingId);
        } catch (err) {
          console.error('change order', err)
        }
      }
    }
  }


  // Add event listeners for "drop" on drop zones
  dropZones.forEach(dropZone => {
    dropZone.addEventListener('drop', handleDrop);
  });
</script>