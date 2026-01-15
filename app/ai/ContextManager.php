<?php
namespace ai; // small letters

class ContextManager
{
    private static $contexts = []; // memory for all sessions

    public static function getContext($sessionId)
    {
        if (!isset(self::$contexts[$sessionId])) {
            self::$contexts[$sessionId] = [];
        }

        if(!isset(self::$contexts[$sessionId])){
            self::$contexts[$sessionId] = [];

        }

        return self::$contexts[$sessionId];
    }

    public static function addMessage($sessionId, $role, $message)
    {
        if (!isset(self::$contexts[$sessionId])) {
            self::$contexts[$sessionId] = [];
        }

        self::$contexts[$sessionId][] = [
            'role' => $role,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Keep max 50 messages
        if (count(self::$contexts[$sessionId]) > 50) {
            array_shift(self::$contexts[$sessionId]);
        }
    }

    public static function clearContext($sessionId)
    {
        unset(self::$contexts[$sessionId]);

        
    }
}

