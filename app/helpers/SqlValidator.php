<?php

class SqlValidator {
    
    // Forbidden SQL commands to prevent mutations
    private static $forbiddenKeywords = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 
        'REPLACE', 'TRUNCATE', 'RENAME', 'GRANT', 'REVOKE', 
        'LOAD DATA', 'EXECUTE', 'SYSTEM', 'LOAD_FILE'
    ];

    /**
     * Validate an SQL query for safety.
     * Returns true if query is safe (read-only and does not query sensitive credentials).
     * Throws an exception with a detailed error message if query is unsafe.
     */
    public static function validate(string $sql): bool {
        $cleanSql = trim($sql);
        
        if (empty($cleanSql)) {
            throw new Exception("SQL query is empty.");
        }

        // 1. Must start with SELECT (case-insensitive)
        if (!preg_match('/^SELECT\b/i', $cleanSql)) {
            throw new Exception("Only SELECT queries are allowed for database safety.");
        }

        // 2. Reject multi-queries (detect semicolons)
        if (str_contains($cleanSql, ';')) {
            // Check if it's not just a trailing semicolon
            $stripped = rtrim($cleanSql, ';');
            if (str_contains($stripped, ';')) {
                throw new Exception("Multiple query execution (semicolon query chaining) is blocked.");
            }
        }

        // 3. Scan for forbidden mutation keywords
        foreach (self::$forbiddenKeywords as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $cleanSql)) {
                throw new Exception("Security Alert: Use of forbidden keyword '{$keyword}' detected.");
            }
        }

        // 4. Block access to sensitive tables/columns (like users passwords)
        if (preg_match('/\bpassword\b/i', $cleanSql)) {
            throw new Exception("Access to credential fields ('password') is blocked.");
        }

        return true;
    }
}
