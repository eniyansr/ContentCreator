<?php
/**
 * CreatorAI - File Upload Handler
 */

require_once __DIR__ . '/../config/config.php';

class FileHandler {
    private $allowedExtensions = ['txt', 'pdf', 'docx', 'doc'];
    private $allowedMimes = [
        'text/plain',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
    ];
    private $maxSize;
    private $uploadDir;
    
    public function __construct() {
        $this->maxSize = MAX_UPLOAD_SIZE;
        $this->uploadDir = UPLOAD_PATH;
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Upload a file
     */
    public function upload($file, $userId, $purpose = 'general') {
        // Validate file exists
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'No file was uploaded.'];
        }
        
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $this->getUploadError($file['error'])];
        }
        
        // Check size
        if ($file['size'] > $this->maxSize) {
            return ['success' => false, 'error' => 'File size exceeds the maximum limit of ' . ($this->maxSize / 1048576) . 'MB.'];
        }
        
        // Check extension
        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['success' => false, 'error' => 'File type not allowed. Allowed: ' . implode(', ', $this->allowedExtensions)];
        }
        
        // Check MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, $this->allowedMimes)) {
            return ['success' => false, 'error' => 'Invalid file type detected.'];
        }
        
        // Generate safe filename
        $storedName = $userId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $userDir = $this->uploadDir . $userId . DIRECTORY_SEPARATOR;
        
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }
        
        $filePath = $userDir . $storedName;
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'error' => 'Failed to save the file.'];
        }
        
        // Extract text content
        $extractedText = $this->extractText($filePath, $extension);
        
        // Save to database
        try {
            require_once __DIR__ . '/../config/database.php';
            $fileId = db()->insert(
                "INSERT INTO uploaded_files (user_id, original_name, stored_name, file_path, file_type, file_size, mime_type, purpose, extracted_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$userId, $originalName, $storedName, $filePath, $extension, $file['size'], $mimeType, $purpose, $extractedText]
            );
            
            return [
                'success' => true,
                'file_id' => $fileId,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'file_type' => $extension,
                'extracted_text' => $extractedText,
            ];
        } catch (Exception $e) {
            // Clean up file on DB error
            @unlink($filePath);
            logError("File upload DB error", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Failed to save file information.'];
        }
    }
    
    /**
     * Extract text from file
     */
    private function extractText($filePath, $extension) {
        try {
            switch ($extension) {
                case 'txt':
                    return file_get_contents($filePath);
                    
                case 'pdf':
                    // Basic PDF text extraction - for production, use a library
                    return $this->extractPdfText($filePath);
                    
                case 'docx':
                    return $this->extractDocxText($filePath);
                    
                default:
                    return null;
            }
        } catch (Exception $e) {
            logError("Text extraction failed", ['file' => $filePath, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Basic PDF text extraction
     */
    private function extractPdfText($filePath) {
        $content = file_get_contents($filePath);
        // Basic extraction - for production use a proper PDF parser
        $text = '';
        preg_match_all('/\(([^\)]+)\)/', $content, $matches);
        if (!empty($matches[1])) {
            $text = implode(' ', $matches[1]);
        }
        return $text ?: '[PDF content - text extraction requires additional libraries]';
    }
    
    /**
     * Extract text from DOCX
     */
    private function extractDocxText($filePath) {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml) {
                $text = strip_tags($xml);
                return trim(preg_replace('/\s+/', ' ', $text));
            }
        }
        return '[DOCX content - could not extract text]';
    }
    
    /**
     * Get upload error message
     */
    private function getUploadError($code) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension.',
        ];
        return $errors[$code] ?? 'Unknown upload error.';
    }
    
    /**
     * Delete a file
     */
    public function delete($fileId, $userId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $file = db()->fetch("SELECT * FROM uploaded_files WHERE id = ? AND user_id = ?", [$fileId, $userId]);
            if (!$file) return false;
            
            @unlink($file['file_path']);
            db()->delete("DELETE FROM uploaded_files WHERE id = ?", [$fileId]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
