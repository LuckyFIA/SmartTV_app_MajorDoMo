<?php
/**
 * Smart TV Control module
 *
 * module for MajorDoMo project
 * @author Fedorov Ivan <4fedorov@gmail.com>
 * @copyright Fedorov I.A.
 * @version 0.1 November 2014
 */



$number = '125';

$digits = preg_split('//', $number, -1, PREG_SPLIT_NO_EMPTY);

print_r($digits);

?>