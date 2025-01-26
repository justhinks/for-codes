<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\pdf\HtmlToPdfConverter;
use App\pdf\LargeHtmlToPdfConverter;
use App\pdf\ChunkedHtmlToPdfConverter;

try {
    $htmlFile = __DIR__ . '/../files/2025_statement_pdf_temp.html';
    $normalPdfFile = __DIR__ . '/../files/2025_statement_pdf_temp.pdf';
    $largePdfFile = __DIR__ . '/../files/2025_statement_pdf_temp_large.pdf';
    $chunkedPdfFile = __DIR__ . '/../files/2025_statement_pdf_temp_chunked.pdf';
    
    // 检查HTML文件是否存在
    if (!file_exists($htmlFile)) {
        throw new \Exception("HTML文件不存在: {$htmlFile}");
    }
    
    // 使用普通转换器
    echo "开始使用普通转换器...\n";
    $normalConverter = new HtmlToPdfConverter();
    $normalConverter->convertToPdf($htmlFile, $normalPdfFile);
    echo "普通PDF文件已成功生成：{$normalPdfFile}\n";
    
    // 使用大文件转换器
    echo "开始使用大文件转换器...\n";
    $largeConverter = new LargeHtmlToPdfConverter();
    $largeConverter->convertToPdf($htmlFile, $largePdfFile);
    echo "优化版PDF文件已成功生成：{$largePdfFile}\n";
    
    // 使用分段式转换器
    echo "开始使用分段式转换器...\n";
    $chunkedConverter = new ChunkedHtmlToPdfConverter();
    $chunkedConverter->convertToPdf($htmlFile, $chunkedPdfFile);
    echo "分段式PDF文件已成功生成：{$chunkedPdfFile}\n";

    // 验证生成的文件
    foreach ([$normalPdfFile, $largePdfFile, $chunkedPdfFile] as $pdfFile) {
        if (!file_exists($pdfFile)) {
            throw new \Exception("PDF文件未成功生成: {$pdfFile}");
        }
        echo "验证文件存在: {$pdfFile}\n";
    }

} catch (\Exception $e) {
    echo "转换过程中出现错误：\n";
    echo "错误信息：{$e->getMessage()}\n";
    echo "错误位置：{$e->getFile()}:{$e->getLine()}\n";
    echo "错误追踪：\n{$e->getTraceAsString()}\n";
    exit(1);
}