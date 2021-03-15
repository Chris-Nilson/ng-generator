<?php

function singular_noun(string $noun) {
    $noun = trim($noun);

    // ex: activities ===> activity
    if(preg_match("/(ies)$/i", $noun)) {
        $noun = preg_replace('/(ies)$/i', 'y', $noun);
        return $noun;
    }

    // users ===> user
    if(preg_match("/(s)$/i", $noun)) {
        $noun = preg_replace('/(s)$/', '', $noun);
        return $noun;
    }

    return $noun;
}

?>