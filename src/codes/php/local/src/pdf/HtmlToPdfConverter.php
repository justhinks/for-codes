<?php

namespace App\pdf;

use \Mpdf\Mpdf;

class HtmlToPdfConverter
{
    public function convertToPdf(string $htmlFile, string $pdfFile): void
    {
        if (!file_exists($htmlFile)) {
            throw new \Exception('HTML 文件不存在');
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9
        ]);

        $htmlContent = file_get_contents($htmlFile);
        $mpdf->WriteHTML($htmlContent);
        $mpdf->Output($pdfFile, 'F');
    }
}