<?php

class Attachments {
    private $uploadDir = 'uploads/';
    private $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf'
    ];
    private $maxFileSize = 5242880; // 5MB

    public function __construct() {
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function uploadFile($file) {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $fileInfo = $this->getFileInfo($file);
            $uniqueFilename = $this->generateUniqueFilename($fileInfo['filename'], $fileInfo['extension']);
            $fullPath = $this->uploadDir . $uniqueFilename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new Exception('Failed to move uploaded file.');
            }

            $entryBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $result = DB::query(
                "INSERT INTO attachments (entry_by, attachment_name, attachment_path, attachment_type) VALUES (?, ?, ?, ?)",
                [$entryBy, $file['name'], $uniqueFilename, $file['type']]
            );

            if ($result === false) {
                throw new Exception('Database error: ' . DB::getLastError());
            }

            return DB::fetchColumn("SELECT LAST_INSERT_ID()");

        } catch (Exception $e) {
            throw new Exception('Upload failed: ' . $e->getMessage());
        }
    }

    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $this->getUploadError($file['error']));
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File is too large. Maximum size is ' . ($this->maxFileSize / 1024 / 1024) . 'MB');
        }

        // Check file type
        if (!isset($this->allowedTypes[$file['type']])) {
            throw new Exception('File type not allowed');
        }

        // Additional security check for file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($this->allowedTypes[$mimeType])) {
            throw new Exception('Invalid file type detected');
        }
    }

    private function getFileInfo($file) {
        $originalName = basename($file['name']);
        $extension = $this->allowedTypes[$file['type']];
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize filename
        $filename = preg_replace("/[^a-zA-Z0-9]/", "_", $filename);
        $filename = strtolower($filename);

        return [
            'filename' => $filename,
            'extension' => $extension
        ];
    }

    private function generateUniqueFilename($filename, $extension) {
        $base = $filename;
        $counter = 1;
        $finalFilename = $filename . '.' . $extension;

        while (file_exists($this->uploadDir . $finalFilename)) {
            $finalFilename = $base . '_' . $counter . '.' . $extension;
            $counter++;
        }

        return $finalFilename;
    }

    private function getUploadError($error) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive in form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    public function getAttachment($id) {
        return DB::fetchOne("SELECT * FROM attachments WHERE id = ?", [$id]);
    }

    public function deleteAttachment($id) {
        // Get attachment info
        $attachment = $this->getAttachment($id);
        if (!$attachment) {
            return false;
        }

        // Delete file
        $filePath = $this->uploadDir . $attachment['attachment_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete database record
        return DB::query("DELETE FROM attachments WHERE id = ?", [$id]);
    }

    public function getAttachmentUrl($id) {
        $attachment = $this->getAttachment($id);
        if (!$attachment) {
            return null;
        }
        return $this->uploadDir . $attachment['attachment_path'];
    }
}

// example

// $attachments = new Attachments();
// $attachmentId = $attachments->uploadFile($_FILES['attachment']);
// if ($attachmentId) {
//     echo 'Attachment uploaded successfully';
// } else {
//     echo 'Failed to upload attachment';
// }

// $attachmentUrl = $attachments->getAttachmentUrl(1);
// if ($attachmentUrl) {
//     echo '<a href="' . $attachmentUrl . '">Download attachment</a>';
// } else {
//     echo 'Attachment not found';
// }

// $attachments->deleteAttachment(1);

?>