<?php

namespace Tigon\Chimera\Includes;

class Utilities {
    private function __construct()
    {
    }

    /**
     * Accesses an array value at an arbitrary depth using the
     * provided key arguments.
     *
     * @param array $array
     * @param mixed|array ...$keys
     * @return mixed The value at the given location
     */
    public static function &array_access(array &$array, mixed ...$keys) {
        // Spread argument if unspread
        if(count($keys) > 0 && gettype($keys[0]) === 'array') return self::array_access($array, ...$keys[0]);

        // Dequeue next key
        $key = array_shift($keys);

        // Return if $keys was initially empty
        if($key === null) return $array;

        // Recurse
        if(gettype($array[$key]) === 'array' && count($keys)) {
            return Utilities::array_access($array[$key], ...$keys);
        }
        // Return
        else return $array[$key];
    }

    /**
     * Returns the path of the first instance of the target key/value pair in a deeply nested array
     *
     * @param array $array Nested array to search
     * @param mixed $key Targey key
     * @param mixed $value Target value
     * @param array $entry_point (Optional) Path from which to begin search. Defaults to top level.
     * @return array|boolean Returns the path to the target as an array, or false if target was not found.
     */
    public static function array_deepfind(array $array, mixed $key, mixed $value, array $entry_point=[]): array|bool {
        $head = Utilities::array_access($array, ...$entry_point);
    
        // Check if we've found the target
        if(isset($head[$key]) && $head[$key] === $value) return $entry_point;
    
        if(gettype($head) === 'array' ) {
            foreach($head as $item_key => $item) {
                $dive = Utilities::array_deepfind($array, $key, $value, [...$entry_point, $item_key]);
                if(gettype($dive) === 'array') return $dive;
            }
        }
        
        return false;
    }
}