<?php

namespace App\pdf;

use \Mpdf\Mpdf;

class ChunkedHtmlToPdfConverter extends HtmlToPdfConverter
{
    private const CHUNK_SIZE = 500000; // 每个分块大小（字节）
    private string $tempDir;
    private string $chunksDir;

    public function __construct()
    {
        $this->tempDir = dirname(__DIR__, 2) . '/tmp';
        $this->chunksDir = $this->tempDir . '/chunks';

        // 确保临时目录存在
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
        if (!file_exists($this->chunksDir)) {
            mkdir($this->chunksDir, 0777, true);
        }
    }

    public function convertToPdf(string $htmlFile, string $pdfFile): void
    {
        if (!file_exists($htmlFile)) {
            throw new \Exception('HTML 文件不存在');
        }

        // 读取HTML内容
        $htmlContent = file_get_contents($htmlFile);
        $chunks = $this->splitHtmlContent($htmlContent);
        $tempPdfFiles = [];

        try {
            // 处理每个分块
            foreach ($chunks as $index => $chunk) {
                $tempPdfFile = $this->chunksDir . "/chunk_{$index}.pdf";
                $this->processChunk($chunk, $tempPdfFile);
                $tempPdfFiles[] = $tempPdfFile;
            }

            // 合并所有PDF文件
            $this->mergePdfFiles($tempPdfFiles, $pdfFile);
        } finally {
            // 清理临时文件
            $this->cleanupTempFiles($tempPdfFiles);
        }
    }

    private function splitHtmlContent(string $content): array
    {
        $chunks = [];
        $dom = new \DOMDocument();
        @$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // 提取head内容（包含样式和元数据）
        $head = '';
        $headNode = $dom->getElementsByTagName('head')->item(0);
        if ($headNode) {
            $head = $dom->saveHTML($headNode);
        }

        // 提取style标签内容
        $styles = '';
        $styleNodes = $dom->getElementsByTagName('style');
        foreach ($styleNodes as $styleNode) {
            $styles .= $dom->saveHTML($styleNode);
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return [$content];
        }

        $currentChunk = '';
        $currentSize = 0;
        $tableOpen = false;
        $divOpen = false;

        foreach ($body->childNodes as $node) {
            $nodeHtml = $dom->saveHTML($node);
            $nodeSize = strlen($nodeHtml);

            // 检查是否是表格开始或结束
            if ($node->nodeName === 'table') {
                $tableOpen = !$tableOpen;
            }

            // 检查是否是div开始或结束
            if ($node->nodeName === 'div') {
                $divOpen = !$divOpen;
            }

            // 如果当前块大小超过限制，并且不在表格或div中间
            if ($currentSize + $nodeSize > self::CHUNK_SIZE && !empty($currentChunk) && !$tableOpen && !$divOpen) {
                // 添加完整的HTML结构
                $chunks[] = "<!DOCTYPE html>\n<html>\n{$head}\n{$styles}\n<body>\n{$currentChunk}\n</body>\n</html>";
                $currentChunk = $nodeHtml;
                $currentSize = $nodeSize;
            } else {
                $currentChunk .= $nodeHtml;
                $currentSize += $nodeSize;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = "<!DOCTYPE html>\n<html>\n{$head}\n{$styles}\n<body>\n{$currentChunk}\n</body>\n</html>";
        }

        return $chunks;
    }

    private function processChunk(string $chunk, string $outputFile): void
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9,
            'tempDir' => $this->tempDir
        ]);

        $mpdf->WriteHTML($chunk);
        $mpdf->Output($outputFile, 'F');
    }

    private function mergePdfFiles(array $files, string $outputFile): void
    {
        if (empty($files)) {
            throw new \Exception('没有可合并的PDF文件');
        }

        if (count($files) === 1) {
            copy($files[0], $outputFile);
            return;
        }

        $merger = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $this->tempDir
        ]);

        foreach ($files as $index => $file) {
            if ($index === 0) {
                $pageCount = $merger->setSourceFile($file);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $merger->AddPage();
                    $merger->UseTemplate($merger->importPage($i));
                }
            } else {
                $pageCount = $merger->setSourceFile($file);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $merger->AddPage();
                    $merger->UseTemplate($merger->importPage($i));
                }
            }
        }

        $merger->Output($outputFile, 'F');
    }

    private function cleanupTempFiles(array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}