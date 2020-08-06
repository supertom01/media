<?php

require_once dirname(__DIR__) . '/Exceptions/UploadException.php';

/**
 * The file class takes care of file uploads. It checks also if uploads already exist and etc.
 *
 * @author Tom Meulenkamp
 */
class FileUpload {

    private array $file;
    private string $destinationFolder;
    private string $name;
    private bool $valid;
    private string $error;
    private string $file_type;
    private string $target_file;

    /**
     * Constructs a file which can then be checked and uploaded to the proper location..
     * @param $file array               The file object, which should be uploaded.
     * @param $destinationFolder String The destination for the file to be uploaded.
     */
    public function __construct($file, $destinationFolder) {
        $this->file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $this->file = $file;
        $this->destinationFolder = $destinationFolder;
        $this->valid = true;
        $this->name = uniqid($this->file_type . "-") . "." . $this->file_type;
        $this->target_file = $this->destinationFolder . $this->name;
    }

    /**
     * Checks if the file is a valid image.
     * @param $allowedFileFormats array The allowed file extensions.
     * @return bool                     true if the image is correct, otherwise false.
     */
    public function checkImage($allowedFileFormats):bool {
        if(!$this->checkFile($allowedFileFormats)) {
            return false;
        }

        // Check if the image is an actual image.
        $this->valid = getimagesize($this->file["tmp_name"]) !== false;
        if(!$this->valid) {
            $this->error = "The image " . $this->getFileName() . " is not an actual image!";

            return false;
        }

        return true;
    }

    /**
     * Checks if the file is suited for upload.
     * @param $allowedFileFormats array The allowed file extensions.
     * @return bool                     true if the file is correct, otherwise false.
     */
    public function checkFile($allowedFileFormats):bool {

        // Check if the file already exists.
        $this->valid = !file_exists($this->target_file);
        if(!$this->valid) {
            $this->error = "The file " . $this->getFileName() . " already exists! Please check if there is already such an image, otherwise change the name.";
            return false;
        }

        // Check if the file contains an allowed file format.
        $this->valid = in_array($this->file_type, $allowedFileFormats);
        if(!$this->valid) {
            $this->error = "The image does not have a valid file format!";
            return false;
        }

        return true;
    }

    /**
     * Move the temporarily stored file to its destination.
     * @return bool true if the file was uploaded, otherwise false.
     */
    public function upload():bool {
        if(!move_uploaded_file($this->file["tmp_name"], $this->target_file) || !chmod($this->target_file, 0777)) {
            $this->error = "Something went wrong while uploading " . $this->name;
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get file name from the uploaded file.
     * @return string The file name.
     */
    public function getFileName():string {
        return $this->name;
    }

    /**
     * Get the target file for the uploaded file.
     * @return string The target file.
     */
    public function getTargetFile():string {
        return $this->target_file;
    }

    /**
     * Get the error message.
     * @return string The error message.
     */
    public function getError():string {
        return $this->error;
    }

    /**
     * Generates an image from a base64 encoded string.
     * @param $img  string      The base64 encoded image.
     * @return      string      The filepath of the image.
     * @throws UploadException  Is thrown when the image could not be uploaded.
     */
    public static function generateImage($img):string {

        $folderPath = dirname(__DIR__) . "/pics/";

        $image_parts = explode(";base64,", $img);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $file = $folderPath . uniqid() . '.jpg';

        if(file_put_contents($file, $image_base64) === false) {
            throw new UploadException("Failed to upload image!");
        }

        return $file;
    }
}