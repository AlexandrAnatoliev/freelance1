<?php

declare(strict_types=1);

/**
 * Формирует селектор
 *
 * @param  $min   минимальное значение селектора
 * @param  $max   максимальное
 * @param  $step  шаг изменения
 * @return html код селектора
 */
function getSelector(int $min, int $max, int $step): string
{
    $value = $min;
    $option = '';

    while ($value <= $max) {
        $option .= '
          <option value="' . $value . '">' . $value . ' шт.</option>';
        $value += $step;
    }
    return $option;
}
