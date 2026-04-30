<?php

function getSelector(): string
{
    $min = 50;
    $max = 1000;
    $step = 50;
    $value = $min;

    $option = '';

    while ($value <= $max) {
        $option .= '
          <option value="' . $value . '">' . $value . ' шт.</option>';
        $value += $step;
    }
    return $option;
}
