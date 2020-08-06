<?php

require_once "Database.php";

class Subtitle {

    private Database $db;
    private int $id;
    private string $language;
    private string $filepath;
    private array $errors;

    public function __construct(int $id) {
        $this->db = new Database();
        $this->id = $id;
        $this->errors = array();

        $tmp = $this->db->preparedQuery("SELECT language, filepath FROM subtitles WHERE sid = ?",
            array([$this->id, SQLITE3_INTEGER]))[0];
        $this->language = $tmp['language'];
        $this->filepath = $tmp['filepath'];
    }

    public function getId(): int {
        return $this->id;
    }

    public function getLanguage(): string {
        return $this->language;
    }

    public function getFilepath(): string {
        return $this->filepath;
    }

    public function isVTT(): bool {
        return strtolower(pathinfo($this->filepath, PATHINFO_EXTENSION)) == "vtt";
    }

    public function convertToVTT(): void {

        // Get file paths
        $srtFile = $this->filepath;
        $webVttFile = dirname($this->filepath) . str_replace(".srt", ".vtt", basename($this->filepath));

        // Read the srt file content into an array of lines
        $fileHandle = fopen($srtFile, 'r');

        if ($fileHandle) {
            // Assume that every line has maximum 8192 length
            // If you don't care about line length then you can omit the 8192 param
            $lines = array();
            while (($line = fgets($fileHandle, 8192)) !== false) $lines[] = $line;

            if (!feof($fileHandle)) exit ("Error: unexpected fgets() fail\n");
            else ($fileHandle);
        }

        // Convert all timestamp lines
        // The first timestamp line is 1
        $length = count($lines);

        for ($index = 1; $index < $length; $index++) {
            // A line is a timestamp line if the second line above it is an empty line
            if ($index === 1 || trim($lines[$index - 2]) === '') {
                $lines[$index] = str_replace(',', '.', $lines[$index]);
            }
        }

        // Insert VTT header and concatenate all lines in the new vtt file
        $header = "WEBVTT\n\n";
        $bytes = file_put_contents($webVttFile, $header . implode('', $lines));

        if($bytes === false) {
            echo "Failed to write to file!";
        } else {
            echo "Created file successfully!" . $bytes . "<br>";
        }

        $this->filepath = $webVttFile;

        try {
            $this->db->preparedUpdate("UPDATE subtitles SET filepath = ? WHERE sid = ?",
                array([$this->filepath, SQLITE3_TEXT], [$this->id, SQLITE3_INTEGER]));
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
        }
    }
}