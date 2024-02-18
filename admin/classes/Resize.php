<?php

/**
 * [Resize description]
 */
class Resize
{

  /**
   * Allowed image extensions
   * @var array
   */
  public $extensions = ['jpg', 'jpeg', 'gif', 'png', 'JPG', 'JPEG', 'GIF', 'PNG'];

  /**
   * Path to the image folder
   * @var string
   */
  public $folderImg = __DIR__ . '/../../previews/assets/img';

  /**
   * Temporary file name
   * @var string
   */
  public $fileTmpName;

  /**
   * Destination path for the image
   * @var string
   */
  public $destination;

  /**
   * Image quality
   * @var int
   */
  public $quality;

  /**
   * Destination path for the copied image
   * @var string
   */
  public $copyDestination;

  /**
   * Name of the file
   * @var string
   */
  public $fileName;

  /**
   * Image extension
   * @var string
   */
  public $extension;

  /**
   * Width of the image
   * @var int
   */
  public $width;

  /**
   * Height of the image
   * @var int
   */
  public $height;

  public $error = '';

  public function __construct($file, $width, $height)
  {
    try {
      $this->TestImage($file);
      $this->moveImage();
      $this->newImage($width, $height);
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Test and process an image file.
   *
   * @param array $file The image file to be tested and processed.
   */
  public function testImage($file)
  {
    $infosfichier = pathinfo($file['name']);
    if (empty($file['tmp_name'])) {
      throw new Exception("Aucun fichier");
    }
    if ($file['error'] != 0) {
      throw new Exception("Problème lors du transfert : erreur de type " . $file['error']);
    }
    if ($file['size'] >= 5000000) {
      throw new Exception("Fichier trop volumineux");
    }
    if ($infosfichier = pathinfo($file['name'])) {
      $extension_upload = $infosfichier['extension'];
      if (!in_array($extension_upload, $this->extensions)) {
        throw new Exception("Fichier non autorisé");
      }
    }
    $this->fileName = uniqid();
    $this->extension = $extension_upload;
    $this->fileTmpName = $file['tmp_name'];
    $this->destination = $this->folderImg . '/' . $this->fileName . '.' . $this->extension;
    $this->copyDestination = $this->folderImg . '/' . $this->fileName . '.jpeg';
    $this->quality = '80';
  }

  /**
   * [CopyDestination description]
   * @param [type] $cd2 [description]
   */
  public function CopyDestination($cd2 = null)
  {
    if (is_null($cd2)) {
      $this->copyDestination = $this->folderImg . '/' . $this->fileName . '.jpeg';
    } else {
      $this->copyDestination = $this->folderImg . '/' . $this->fileName . $cd2 . '.jpeg';
    }
  }

  /**
   * Moves an image to a destination
   * @param string|null $fileTmpName The temporary file name
   * @param string|null $destination The destination path
   */
  public function moveImage($fileTmpName = null, $destination = null)
  {
    $fileTmpName = $fileTmpName ?? $this->fileTmpName;
    $destination = $destination ?? $this->destination;
    // create folder if it doesn't exist
    if (!file_exists($this->folderImg)) {
      mkdir($this->folderImg, 0777, true);
    }

    move_uploaded_file($fileTmpName, $destination);
  }

  /**
   * Create a new image with specified dimensions and optional text overlay
   *
   * @param int $width The width of the new image
   * @param int $height The height of the new image
   * @param string|null $text Optional text to overlay on the image
   * @param string|null $source Path to the source image file
   * @param string|null $destination Path to save the new image
   * @param int|null $quality Image quality (0-100)
   * @param string|null $extension Image extension (e.g. "jpg", "png")
   * @param string|null $fileName Name for the new image file
   * @param string|null $copyDestination Path to save a copy of the new image
   */
  public function newImage($width, $height, $text = null, $source = null, $destination = null, $quality = null, $extension = null, $fileName = null, $copyDestination = null)
  {
    $source = $source ?? $this->destination;
    $destination = $destination ?? $this->destination;
    $quality = $quality ?? $this->quality;
    $extension = $extension ?? $this->extension;
    $fileName = $fileName ?? $this->fileName;
    $copyDestination = $copyDestination ?? $this->copyDestination;
    $this->width = $width;
    $this->height = $height;

    $imageResource = imageCreateFromString(file_get_contents($source));
    $imageFinal = imagecreatetruecolor($width, $height);

    list($Owidth, $Oheight) = getimagesize($source);

    // Calculate new dimensions and crop points
    $percentFinal = $width / $height;
    $percentOriginal = $Owidth / $Oheight;

    if ($percentFinal > $percentOriginal) {
      $NewHeight = $width / ($Owidth / $Oheight);
      $NewWidth = $width;
      $coupeX = 0;
      $coupeY = ($Oheight - (($height / $width) * $Owidth)) / 2;
    } else {
      $NewHeight = $height;
      $NewWidth = $height / ($Oheight / $Owidth);
      $coupeX = ($Owidth - (($width / $height) * $Oheight)) / 2;
      $coupeY = 0;
    }

    // Resample the image
    imagecopyresampled($imageFinal, $imageResource, 0, 0, $coupeX, $coupeY, $NewWidth, $NewHeight, $Owidth, $Oheight);

    // Add text if provided
    if (!is_null($text)) {
      $couleur = imagecolorallocate($imageResource, 0, 0, 0);
      imagestring($imageFinal, 8, 15, 15, $text, $couleur);
    }

    // Save the final image
    imagejpeg($imageFinal, $copyDestination, $quality);
  }

  public function getHeight($newWidth)
  {
    if ($this->width && $this->height) {
      $ratio = $this->width > $this->height ? $this->height / $this->width : $this->width / $this->height;
      return round($this->height * $ratio);
    } else {
      throw new Exception('No width or height specified');
    }
  }

  public function copyImage($postfix, $width)
  {
    try {
      $height = $this->getHeight($this->width);
      $this->copyDestination = $this->folderImg . '/' . $this->fileName . $postfix . '.jpeg';
      $this->newImage($width, $height);
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function OnlyResize($maxWidth, $maxHeight, $text = null, $source = null, $dst = null, $quality = null, $extension_upload = null, $fileName = null, $copyDestination = null)
  {
    $source = $source ?? $this->destination;
    $dst = $dst ?? $this->destination;
    $quality = $quality ?? $this->quality;
    $extension_upload = $extension_upload ?? $this->extension;
    $fileName = $fileName ?? $this->fileName;
    $copyDestination = $copyDestination ?? $this->copyDestination;

    $imageRessource = imageCreateFromstring(file_get_contents($source));

    $imageSize = getimagesizefromstring(file_get_contents($source));

    $Owidth = $imageSize[0]; 
    $Oheight = $imageSize[1];

    $OaspectRatio = $Owidth / $Oheight;

    if (($maxWidth / $maxHeight) > $OaspectRatio) {
      $maxWidth = floor($maxHeight * $OaspectRatio);
    } else {
      $maxHeight = floor($maxWidth / $OaspectRatio);
    }

    $imageFinal = imagecreatetruecolor($maxWidth, $maxHeight);

    $final = imagecopyresampled($imageFinal, $imageRessource, 0, 0, 0, 0, $maxWidth, $maxHeight, $Owidth, $Oheight);
    if ($final) {
      if (!is_null($text)) {
        $couleur = imagecolorallocate($imageRessource, 0, 0, 0);
        imagestring($imageFinal, '8', '15', '15', $text, $couleur);
      }
      imagejpeg($imageFinal, $copyDestination, $quality);
    } else {
      throw new Exception("Erreur lors du download " . $maxWidth . ' ' . $imageRessource . ' ' . $imageFinal);
    }
  }

  public function cropperJSNewImage($width, $height, $X, $Y, $newWidth, $newHeight, $text = null, $source = null, $dst = null, $quality = null, $extension_upload = null, $fileName = null, $copyDestination = null)
  {
    $source = $source ?? $this->destination;
    $dst = $dst ?? $this->destination;
    $quality = $quality ?? $this->quality;
    $extension_upload = $extension_upload ?? $this->extension;
    $fileName = $fileName ?? $this->fileName;
    $copyDestination = $copyDestination ?? $this->copyDestination;
    $this->width = $width;
    $this->height = $height;

    $imageRessource = imageCreateFromString(file_get_contents($source));

    $imageFinal = imagecreatetruecolor($width, $height);

    imagecopyresampled($imageFinal, $imageRessource, 0, 0, $X, $Y, $width, $height, $newWidth, $newHeight);

    if (!is_null($text)) {
      $couleur = imagecolorallocate($imageFinal, 0, 0, 0);
      imagestring($imageFinal, 8, 15, 15, $text, $couleur);
    }
    imagejpeg($imageFinal, $copyDestination, $quality) or die("Erreur lors du download");
  }
}
