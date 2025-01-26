<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\HtmlToPdfConverter;

class HtmlToPdfConverterTest extends TestCase
{
    private string $testHtmlFile;
    private string $testPdfFile;

    protected function setUp(): void
    {
        $this->testHtmlFile = __DIR__ . '/test.html';
        $this->testPdfFile = __DIR__ . '/test.pdf';

        // 创建测试用的 HTML 文件
        file_put_contents($this->testHtmlFile, '<h1>测试 HTML 文件</h1><p>这是一个测试内容。</p>');
    }

    protected function tearDown(): void
    {
        // 清理测试文件
        if (file_exists($this->testHtmlFile)) {
            unlink($this->testHtmlFile);
        }
        if (file_exists($this->testPdfFile)) {
            unlink($this->testPdfFile);
        }
    }

    public function testConvertToPdf()
    {
        $converter = new HtmlToPdfConverter();
        $converter->convertToPdf($this->testHtmlFile, $this->testPdfFile);

        $this->assertFileExists($this->testPdfFile);
        $this->assertGreaterThan(0, filesize($this->testPdfFile));
    }

    public function testThrowsExceptionForNonExistentFile()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('HTML 文件不存在');

        $converter = new HtmlToPdfConverter();
        $converter->convertToPdf('non_existent.html', $this->testPdfFile);
    }
}