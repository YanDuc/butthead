<?php
$jsonFilePath = './site.json';

if (file_exists($jsonFilePath)) {
  $jsonData = json_decode(file_get_contents($jsonFilePath), true);

  if (!isset($jsonData['root'])) {
    include_once('classes/PageManager.php');
    $pageManager = new PageManager();
    $pageManager->add('root', '', null);
    $jsonData['root'] = [
      "description" => "",
      "pageName" => "Home",
      "order" => 0,
      "addToNav" => false
    ];
  }

  // Sort the data based on the "order" value in ascending order
  uasort($jsonData, function ($a, $b) {
    return $a['order'] <=> $b['order'];
  });
} else {
  $jsonData = [];
}
?>

<aside>
  <h2>
    <?= _('Pages') ?>
  </h2>
  <a class="button-link" href="?page=addPage">
    <?= _('Add page') ?>
  </a>
  <ul id="sortable">
    <?php foreach ($jsonData as $id => $data): ?>
      <?php if (!isset($data['pageName'])) { continue; } ?>
      <?php $allowChange = $data['unauthorizedUsers'] && in_array($_SESSION['loggedIn']['email'], $data['unauthorizedUsers']) ? false : true ?>
      <div class="drop-zone-nav" id="<?= $id ?>"></div>
      <li id="<?= $id ?>" draggable="true" ondragstart="handleDragStart(event, '<?= $id ?>')">
        <?php if ($allowChange): ?>
          <a href="#" onclick="changePage('<?= $id ?>', null, '<?= $data['unauthorizedUsers'] ?>'); return false;">
            <?= $data['pageName']; ?>
          </a>
        <?php endif; ?>
        <?php if (!$allowChange): ?>
          <div class="disabled">
            <?= $data['pageName']; ?>
          </div>
        <?php endif; ?>

        <?php if ($data['subPages']): ?>
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
                    onclick="changePage('<?= $subId ?>', '<?= $id ?>', '<?= $data['unauthorizedUsers'] ?>'); return false;">
                    <?= $subData['pageName'] ?>
                  </a>
                <?php endif; ?>
                <?php if (!$allowChangeSub): ?>
                  <div class="disabled">
                    <?= $subData['pageName']; ?>
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
      <?php if (end($jsonData) === $data): ?>
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

  async function changeOrder(pagePath, belowPagePath) {
    const changeOrderForm = new FormData();
    changeOrderForm.append("pagePath", pagePath);
    changeOrderForm.append("belowPagePath", belowPagePath);
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
      location.assign('/admin/?page=' + page + '&parent=' + parent);
    } else {
      location.assign('/admin/?page=' + page);
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
    const droppedOnId = e.target.id;
    const isDraggedSubPage = draggedPath.includes('/')
    const isDroppedOnSubPage = droppedOnId.includes('/')

    if (isDraggedSubPage) {
      if (e.target.classList.contains('drop-zone-nav')) {
        if (e.target.id === draggedPath) {
          return; // Do not reorder if dropping on the same element
        }

        // Move dragged element to the new position
        try {
          await changeOrder(draggedPath, droppedOnId);
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
          await changeOrder(draggedPath, droppedOnId);
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

<style>
  .drop-zone-nav {
    transition: height 0.3s ease;
    height: 3px;
    margin: 0 5px;
    /* Add transition for height change */
  }

  #page-elements,
  #sortable {
    list-style-type: none;
    padding: 0;
    margin: 0;
  }

  #page-elements li,
  #sortable li {
    margin: 0 0 3px 0;
    padding: 10px;
    background-color: #f5f5f5;
    border: 1px solid #ddd;
    transition: transform 0.3s ease;
  }

  #sortable li.subpage {
    background-color: #fafafa;
  }

  #sortable li.subpage ul {
    margin: 5px 0 0 20px;
  }

  #sortable li.subpage li {
    background-color: #f5f5f5;
    border: none;
    padding: 5px;
  }

  #sortable li.dragover {
    background-color: #f8f8f8;
    border-color: #999;
    transform: scale(1.05);
  }
</style>