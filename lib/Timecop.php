<?php

/**
 * Class used to easy testing of time dependent functionality
 * Timecop::time() must be called instead of time() for it to work.
 * Even in functions where time argument is optional like date()!
 */
class Timecop
{
    /** 
     * Delta in seconds from actual time() 
     *
     * @var int 
     */
    protected static $delta = 0;

    /** 
     * If time si frozen than frozen timestamp otherwise bool false 
     *
     * @var bool|int
     */
    protected static $frozen = FALSE;

    /**
     * Have we taken over php functions?  
     *
     * @var bool 
     */
    protected static $onMission = FALSE;

    /**
     * Internal php functions replacements
     * '<function>' => array('<arguments>', '<body>')
     *
     * @var array 
     */
    protected static $aliases = array(
        'time' => array(
            '',
            'return Timecop::time();'
        ),
        'date' => array(
            '$format, $timestamp = NULL',
            'return Timecop::date($format, $timestamp);'
        ),
        'getdate' => array(
            '$timestamp = NULL',
            'return Timecop::getdate($timestamp);'
        ),
        'gmdate' => array(
            '$format, $timestamp = NULL',
            'return Timecop::gmdate($format, $timestamp);'
        ),
        'gmmktime' => array(
            '$hour = NULL, $minute = NULL, $second = NULL, $month = NULL, $day = NULL, $year = NULL, $is_dst = -1',
            'return Timecop::gmmktime($hour, $minute, $second, $month, $day, $year, $is_dst);'
        ),
        'gmstrftime' => array(
            '$format, $timestamp = NULL',
            'return Timecop::gmstrftime($format, $timestamp);'
        ),
        'idate' => array(
            '$format, $timestamp = NULL',
            'return Timecop::idate($format, $timestamp);'
        ),
        'localtime' => array(
            '$timestamp = NULL, $isAssociative = FALSE',
            'return Timecop::localtime($timestamp, $isAssociative);'
        ),
        'mktime' => array(
            '$hour = NULL, $minute = NULL, $second = NULL, $month = NULL, $day = NULL, $year = NULL, $is_dst = -1',
            'return Timecop::mktime($hour, $minute, $second, $month, $day, $year, $is_dst);'
        ),
        'strftime' => array(
            '$format, $timestamp = NULL',
            'return Timecop::strftime($format, $timestamp);'
        ),
        'strptime' => array(
            '$date, $format',
            'return Timecop::strptime($date, $format);'
        ),
        'strtotime' => array(
            '$format, $timestamp = NULL',
            'return Timecop::strtotime($format, $timestamp);'
        )
    );


    /**
     * Get eighter function name or its alias name based on actual deploy status
     *
     * @param string $function
     * @return string 
     */
    protected static function getFunctionAlias($function)
    {
        if (self::$onMission) {
            $function = $function . '_alias';
        }
        return $function;
    }


    /**
     * Moves time backwards/forwards
     *
     * @param int $timestamp
     * @return void
     */
    public static function travel($timestamp)
    {
        // calculate delta
        self::$delta = $timestamp - call_user_func(self::getFunctionAlias('time'));

        // move frozen time if already frozen
        if (self::$frozen !== FALSE) {
            self::$frozen = $timestamp;
        }
    }

    /**
     * Unfreezes and returns time back to present
     *
     * @return void
     */
    public static function restore()
    {
        self::$frozen = FALSE;
        self::$delta = 0;
    }

    /**
     * Freezes time
     *
     * @return void
     */
    public static function freeze()
    {
        self::$frozen = self::time();
    }

    /**
     * Unfreezes time
     *
     * @return void
     */
    public static function unfreeze()
    {
        self::$frozen = FALSE;
    }

    /**
     * Returns time as calculated by Timecop
     *
     * @return int
     */
    public static function time()
    {
        if (self::$frozen === FALSE) {
            // calculate Timecop time
            return call_user_func(self::getFunctionAlias('time')) + self::$delta;
        } else {
            // or return frozen time if frozen
            return self::$frozen;
        }
    }

    /**
     * Aliases function
     *
     * @throws RuntimeException
     * @param string $function
     * @param string $params
     * @param string $body
     * @return void
     */
    protected static function aliasFunction($function, $arguments, $body)
    {
        if (!runkit_function_rename($function, $function . '_alias')
        ||  !runkit_function_add($function, $arguments, $body)) {
            throw new RuntimeException("Runkit failed to alias function '$function'");
        }
    }

    /**
     * Unaliases (restores) function
     *
     * @throws RuntimeException
     * @param string $function
     * @return void
     */
    protected static function unaliasFunction($function)
    {
        if (!runkit_function_remove($function)
        ||  !runkit_function_rename($function . '_alias', $function)) {
            throw new RuntimeException("Runkit failed to unalias function '$function'");
        }
    }

    /**
     * Replaces internal php date and time functions
     *
     * @throws RuntimeException
     * @return void
     */
    public static function warpTime()
    {
        if (!extension_loaded('runkit')) {
            throw new RuntimeException('Runkit extension required');
        }

        if (self::$onMission) {
            throw new RuntimeException('Time has already been warped');
        }

        // APC doesn't quite like runkit. Together they might result in a dead process.
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        foreach (self::$aliases as $function => $callback) {
            self::aliasFunction($function, $callback[0], $callback[1]);
        }

        self::$onMission = TRUE;
    }

    /**
     * Restores internal php date and time functions
     *
     * @throws RuntimeException
     * @return void
     */
    public static function unwarpTime()
    {
        if (!self::$onMission) {
            throw new RuntimeException('Time has not been yet warped');
        }

        foreach (self::$aliases as $function => $callback) {
            self::unaliasFunction($function);
        }

        self::$onMission = FALSE;
    }

    /**
     * Helper function to set correct default timestamp in case it's NULL
     *
     * @param null|int $timestamp
     * @return int
     */
    protected static function getTs($timestamp)
    {
        if ($timestamp === NULL) {
            $timestamp = self::time();
        }
        return $timestamp;
    }

    /**
     * @param string $format
     * @param null|int $timestamp
     * @return string
     */
    public static function date($format, $timestamp = NULL)
    {
        return call_user_func(
            self::getFunctionAlias('date'),
            $format, self::getTs($timestamp)
        );
    }

    /**
     * @param null|int $timestamp
     * @return array
     */
    public static function getdate($timestamp = NULL)
    {
        return call_user_func(
            self::getFunctionAlias('getdate'),
            self::getTs($timestamp)
        );
    }

    /**
     * @param string $format
     * @param null|int $timestamp
     * @return string
     */
    public static function gmdate($format, $timestamp = NULL)
    {
        return call_user_func(
            self::getFunctionAlias('gmdate'),
            $format, self::getTs($timestamp)
        );
    }

    /**
     * @param null|int $hour
     * @param null|int $minute
     * @param null|int $second
     * @param null|int $month
     * @param null|int $day
     * @param null|int $year
     * @param int $is_dst
     * @return int
     */
    public static function gmmktime($hour = NULL, $minute = NULL, $second = NULL, $month = NULL,
        $day = NULL, $year = NULL, $is_dst = -1
    ) {
        if ($hour == NULL) $hour = self::date('H');
        if ($minute == NULL) $hour = self::date('i');
        if ($second == NULL) $hour = self::date('s');
        if ($month == NULL) $hour = self::date('n');
        if ($day == NULL) $hour = self::date('j');
        if ($year == NULL) $hour = self::date('Y');

        return call_user_func(
            self::getFunctionAlias('gmmktime'),
            $hour, $minute, $second, $month, $day, $year, $is_dst
        );
    }

    /**
     * @param string $format
     * @param null|int $timestamp
     * @return string
     */
    public static function gmstrftime($format, $timestamp = NULL)
    {
        return call_user_func(
            self::getFunctionAlias('gmstrftime'),
            $format, self::getTs($timestamp)
        );
    }

    /**
     * @param string $format
     * @param null|int $timestamp
     * @return int
     */
    public static function idate($format, $timestamp = NULL)
    {
        return call_user_func(
            self::getFunctionAlias('idate'),
            $format, self::getTs($timestamp)
        );
    }

    /**
     * @param null|int $timestamp
     * @param bool $isAssociative
     * @return array
     */
    public static function localtime($timestamp = NULL, $isAssociative = FALSE)
    {
        return call_user_func(
            self::getFunctionAlias('localtime'),
            self::getTs($timestamp), $isAssociative
        );
    }

    /**
     * @param null|int $hour
     * @param null|int $minute
     * @param null|int $second
     * @param null|int $month
     * @param null|int $day
     * @param null|int $year
     * @param int $is_dst
     * @return int
     */
    public static function mktime($hour = NULL, $minute = NULL, $second = NULL, $month = NULL,
        $day = NULL, $year = NULL, $is_dst = -1
    ) {
        if ($hour == NULL) $hour = self::date('H');
        if ($minute == NULL) $hour = self::date('i');
        if ($second == NULL) $hour = self::date('s');
        if ($month == NULL) $hour = self::date('n');
        if ($day == NULL) $hour = self::date('j');
        if ($year == NULL) $hour = self::date('Y');

        return call_user_func(
            self::getFunctionAlias('mktime'),
            $hour, $minute, $second, $month, $day, $year, $is_dst
        );
    }

    /**
     * @param string $format
     * @param null|int $timestamp
     * @return string
     */
    public static function strftime($format, $timestamp = NULL)
    {
        return call_user_func(
            self::getFunctionAlias('strftime'),
            $format, self::getTs($timestamp)
        );
    }

    /**
     * @param string $date
     * @param string $format
     * @return array
     */
    public static function strptime($date, $format)
    {
        return call_user_func(
            self::getFunctionAlias('strptime'),
            $date, $format
        );
    }

    /**
     * @param string $format
     * @param null|int $timestamp
     * @return int
     */
    public static function strtotime($format, $timestamp = NULL)
    {
        return call_user_func(
            self::getFunctionAlias('strtotime'),
            $format, self::getTs($timestamp)
        );
    }
}
