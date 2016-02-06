<?php

function t($str)
{
    return Mage::helper("core")->__($str);
}

function e($param, $return = false)
{
    if (!$return) {
        var_dump($param);
        return null;
    }

    if (!is_string($param)) {
        $param =
            '<pre>' .
            print_r($param, true) .
            '</pre>';
    }

    if ($return) {
        return $param;
    }

    echo $param;
    return null;
}

function ee($param, $return = false)
{
    $param =
        '<pre>' .
        print_r($param, true) .
        '</pre>';

    if ($return) {
        return $param;
    }

    echo $param;
    return null;
}

function randomString($length, $readable = false)
{
    mt_srand((double)microtime() * 1000000);

    $string = '';

    if ($readable) {
        $possible_charactors = "abcdefghmnprstuvwz23457ABCDEFGHMNPRSTUVWYZ";
    } else {
        $possible_charactors = "abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    }


    for ($i = 0; $i < $length; $i++) {
        if ($readable) {
            $string .= substr($possible_charactors, mt_rand(0, 41), 1);
        } else {
            $string .= substr($possible_charactors, mt_rand(0, 61), 1);
        }
    }

    return $string;
}

function shortenString($string, $max_len)
{
    $str = (strlen($string) > $max_len) ? lcUnicode::substr($string, 0, $max_len) : $string;
    return $str;
}

function round_up($number, $precision = 2)
{
    $fig = (int)str_pad('1', $precision, '0');
    return (ceil($number * $fig) / $fig);
}

function round_down($number, $precision = 2)
{
    $fig = (int)str_pad('1', $precision, '0');
    return (floor($number * $fig) / $fig);
}