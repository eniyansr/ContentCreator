<?php
$content = file_get_contents('database/schema.sql');

// Remove MySQL specific headers
$content = preg_replace('/SET SQL_MODE[^;]+;\n/s', '', $content);
$content = preg_replace('/SET AUTOCOMMIT[^;]+;\n/s', '', $content);
$content = preg_replace('/START TRANSACTION;\n/s', '', $content);
$content = preg_replace('/SET time_zone[^;]+;\n/s', '', $content);
$content = preg_replace('/CREATE DATABASE[^;]+;\n/s', '', $content);
$content = preg_replace('/USE `creator_ai`;\n/s', '', $content);

// Fix AUTO_INCREMENT
$content = preg_replace('/`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY/i', '`id` INTEGER PRIMARY KEY AUTOINCREMENT', $content);

// Remove ENGINE=InnoDB...
$content = preg_replace('/\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;/i', ');', $content);

// Fix ON UPDATE CURRENT_TIMESTAMP
$content = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/i', '', $content);

// Fix ENUM types (SQLite doesn't support MySQL's ENUM syntax)
$content = preg_replace('/ENUM\([^\)]+\)/i', 'TEXT', $content);

// SQLite does not support defining multiple INDEX inside CREATE TABLE nicely sometimes, 
// wait, SQLite does NOT support INDEX inside CREATE TABLE.
// It supports UNIQUE(...) but not INDEX `name` (`col`).
// We must extract them and append them.
preg_match_all('/CREATE TABLE IF NOT EXISTS `([^`]+)` \((.*?)\);/is', $content, $matches, PREG_SET_ORDER);

$finalSql = "";

foreach ($matches as $match) {
    $tableName = $match[1];
    $tableBody = $match[2];
    
    // Extract indexes
    $indexes = [];
    $lines = explode("\n", $tableBody);
    $newLines = [];
    foreach ($lines as $line) {
        if (preg_match('/INDEX `([^`]+)` \(`([^`]+)`\)/i', $line, $idxMatch)) {
            $indexes[] = "CREATE INDEX IF NOT EXISTS `{$idxMatch[1]}` ON `{$tableName}` (`{$idxMatch[2]}`);";
            continue; // Skip adding this to table body
        }
        $newLines[] = $line;
    }
    
    // Clean up trailing commas before closing parens
    $newBody = implode("\n", $newLines);
    $newBody = preg_replace('/,\s*$/', '', $newBody); // remove last trailing comma
    
    // Wait, let's just use string replacement on trailing commas
    $lines = explode("\n", trim($newBody));
    // Remove trailing comma from the last element if it exists
    $lastLine = array_pop($lines);
    $lastLine = preg_replace('/,$/', '', $lastLine);
    $lines[] = $lastLine;
    $newBody = implode("\n", $lines);
    
    $finalSql .= "CREATE TABLE IF NOT EXISTS `{$tableName}` (\n{$newBody}\n);\n\n";
    foreach ($indexes as $idx) {
        $finalSql .= $idx . "\n";
    }
    $finalSql .= "\n";
}

$finalSql .= "COMMIT;\n";

file_put_contents('database/schema.sqlite.sql', $finalSql);
echo "Schema converted to database/schema.sqlite.sql";
