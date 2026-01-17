<?php
namespace ai;

class ContextManager
{
    private static array $memory = [];

    public static function addMessage($sessionId, $role, $message)
    {
        self::$memory[$sessionId][] = [
            'role' => $role,
            'content' => $message,
            'time' => time()
        ];
    }

    public static function getContext($sessionId)
    {
        return self::$memory[$sessionId] ?? [];
    }

    public static function clearContext($sessionId)
    {
        unset(self::$memory[$sessionId]);
    }
}
