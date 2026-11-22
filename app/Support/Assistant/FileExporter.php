<?php

namespace App\Support\Assistant;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Membuat file PDF / Excel dari dataset asisten. Disimpan privat, diunduh lewat route yang mengecek pemilik. */
class FileExporter
{
    public const DIR = 'chat-exports';

    /** @return array{path: string, name: string, mime: string, size: int} */
    public static function make(string $title, array $columns, array $rows, string $format, User $user): array
    {
        $slug = Str::slug(Str::limit($title, 50, '')) ?: 'data';
        $name = $slug . '-' . now()->format('Ymd-His') . '.' . $format;
        $path = self::DIR . '/' . $user->id . '/' . Str::uuid() . '.' . $format;

        $content = $format === 'pdf' ? self::pdf($title, $columns, $rows, $user) : self::xlsx($title, $columns, $rows, $user);
        Storage::disk('local')->put($path, $content);

        return [
            'path' => $path,
            'name' => $name,
            'mime' => $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size' => strlen($content),
        ];
    }

    protected static function xlsx(string $title, array $columns, array $rows, User $user): string
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle(Str::limit(preg_replace('/[\\\\\/?*\[\]:]/', '', $title), 28, ''));
        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', 'Dibuat ' . now()->locale('id')->isoFormat('D MMM YYYY HH:mm') . ' oleh ' . $user->name . ' · AMS IT OPS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('6B7280');

        $keys = array_keys($columns);
        foreach (array_values($columns) as $i => $label) {
            $sheet->setCellValueByColumnAndRow($i + 1, 4, $label);
        }
        $last = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($keys));
        $sheet->getStyle("A4:{$last}4")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A4:{$last}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F3A5F');

        foreach ($rows as $r => $row) {
            foreach ($keys as $c => $k) {
                $v = $row[$k] ?? '';
                // Teks panjang berupa angka (NIK, SN, no. aset) tetap sebagai teks.
                if (is_string($v) && preg_match('/^\d{8,}$/', $v)) {
                    $sheet->setCellValueExplicitByColumnAndRow($c + 1, $r + 5, $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValueByColumnAndRow($c + 1, $r + 5, $v);
                }
            }
        }
        foreach (range(1, count($keys)) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }
        $sheet->setAutoFilter("A4:{$last}" . (count($rows) + 4));
        $sheet->freezePane('A5');

        ob_start();
        (new Xlsx($book))->save('php://output');

        return (string) ob_get_clean();
    }

    protected static function pdf(string $title, array $columns, array $rows, User $user): string
    {
        return Pdf::loadView('pdf.chat-export', compact('title', 'columns', 'rows', 'user'))
            ->setPaper('A4', count($columns) > 5 ? 'landscape' : 'portrait')
            ->output();
    }
}
