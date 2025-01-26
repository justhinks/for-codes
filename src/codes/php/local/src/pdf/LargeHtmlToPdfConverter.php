<?php

namespace App\pdf;

use \Mpdf\Mpdf;

class LargeHtmlToPdfConverter extends HtmlToPdfConverter
{
    public function convertToPdf(string $htmlFile, string $pdfFile): void
    {
        if (!file_exists($htmlFile)) {
            throw new \Exception('HTML 文件不存在');
        }

        // 确保临时目录存在
        $tempDir = dirname(__DIR__, 2) . '/tmp';
        $fontDir = $tempDir . '/fonts';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        if (!file_exists($fontDir)) {
            mkdir($fontDir, 0777, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9,
            // 大文件优化配置
            'memory_limit' => '512M',        // 增加内存限制
            'max_execution_time' => 300,     // 增加执行时间限制
            'allow_output_buffering' => true,// 启用输出缓冲
            'enableImports' => true,         // 启用导入功能
            'tempDir' => $tempDir,           // 设置临时目录
            'fontCache' => $fontDir,         // 设置字体缓存目录
            'useSubstitutions' => true,      // 启用字体替换
            'simpleTables' => true           // 简化表格处理以提高性能
        ]);

        $htmlContent = file_get_contents($htmlFile);
        $mpdf->WriteHTML($htmlContent);
        $mpdf->Output($pdfFile, 'F');
    }
}