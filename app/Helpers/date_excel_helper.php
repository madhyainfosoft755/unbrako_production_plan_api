<?php

// use DateTime;

function excelDateToYmd($excelDate): ?string
{
    // Expecting dd-mm-yyyy or dd/mm/yyyy
    $excelDate = trim($excelDate);
    if (!$excelDate) {
        return null;
    }
    $parts = preg_split('/[\/\-]/', $excelDate);
    if (count($parts) !== 3) {
        return null;
    }
    [$d, $m, $y] = $parts;
    if (!checkdate((int) $m, (int) $d, (int) $y)) {
        return null;
    }
    return sprintf('%04d-%02d-%02d', $y, $m, $d);


    // Empty cell ⇒ NULL
    // if ($value === null || trim((string) $value) === '') {
    //     return null;
    // }

    // /* ---------- Excel numeric serial date ---------- */
    // // if (is_numeric($value)) {
    // //     try {
    // //         return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
    // //     } catch (\Throwable $e) {
    // //         return null;                   // invalid serial number
    // //     }
    // // }

    // /* ---------- Try a set of allowed string formats ---------- */
    // $formats = ['!m/d/Y', '!d-m-Y', '!d/m/Y'];   // extend if necessary
    // foreach ($formats as $fmt) {
    //     $dt = DateTime::createFromFormat($fmt, trim($value));
    //     if ($dt) {
    //         $errors = DateTime::getLastErrors();
    //         if ($errors === false               // ← parse was perfect
    //             || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)
    //         ) {
    //             return $dt->format('Y-m-d');
    //         }
    //     }
    // }

    // /* ---------- Everything failed ---------- */
    // return null;
}
