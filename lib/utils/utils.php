<?php
/**
 * 2015-2016 Copyright (C) Payin7 S.L.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * DISCLAIMER
 *
 * Do not modify this file if you wish to upgrade the Payin7 module automatically in the future.
 *
 * @author    Payin7 S.L. <info@payin7.com>
 * @copyright 2015-2016 Payin7 S.L.
 * @license   http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 */

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