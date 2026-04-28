<?php

require_once 'utils/session.php';

// ------------------------------------------------------------------
// Генерация CAPTCHA
// ------------------------------------------------------------------
/**
 * Генерирует простую математическую капчу.
 * Сохраняет правильный ответ в сессии и возвращает вопрос для отображения.
 *
 * @return array  ['question' => string, 'answer' => int]
 */
function generateCaptcha(): array
{
    $num1 = random_int(1, 10);
    $num2 = random_int(1, 10);
    $operators = ['+', '-'];
    $operator = $operators[array_rand($operators)];

    // Для вычитания убеждаемся, что результат положительный
    if ($operator === '-' && $num1 < $num2) {
        [$num1, $num2] = [$num2, $num1];
    }

    $num1word = convertNumToWord($num1);
    $question = "$num1word $operator $num2";
    $answer = $operator === '+' ? $num1 + $num2 : $num1 - $num2;

    $_SESSION['captcha_answer'] = $answer;
    $_SESSION['captcha_generated_at'] = time();

    return [
        'question' => $question,
        'answer' => $answer,
    ];
}

/**
 * Преобразует число в слово прописью.
 *
 * @param  $num - число, которое нужно преобразовать.
 * @return      - число прописью
 */
function convertNumToWord(int $num): string
{
    $word = ['ноль', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять', 'десять'];
    return $word[$num];
}
