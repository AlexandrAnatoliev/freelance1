<?php

/**
 * Проверяет, существует ли файл изображения на сервере.
 * Если файла нет — возвращает путь к заглушке (placeholder),
 * чтобы не показывать битую иконку в браузере.
 *
 * @param  $path        - путь к проверяемому изображению
 * @param  $placeholder - путь к заглушке (по умолчанию img/placeholder.jpg)
 * @return
 */
function getImagePath(string $path, string $placeholder = 'img/placeholder.jpg'): string
{
    if (!empty($path) && file_exists($path)) {
        return $path;
    }
    return $placeholder;
}
