<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Генерирует PDF из HTML-шаблона счёта.
 *
 * Функция настраивает Dompdf для корректной работы с кириллицей,
 * устанавливает формат A4 и отступы, рендерит PDF и возвращает
 * его содержимое в виде строки.
 *
 * @param  string $html - полный HTML-документ счёта
 * @return string       - бинарное содержимое PDF-файла
 */
function generatePDF(string $html): string
{
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'dejavu sans');
    $options->set('chroot', __DIR__);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
}
