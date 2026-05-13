<?php

namespace App\Contracts;

interface SantaCruzFtpClientInterface
{
    /**
     * @return list<string> nombres de archivo (basename) terminados en .xml
     */
    public function listXmlFiles(): array;

    public function getFileContents(string $basename): string;

    public function moveToProcessed(string $basename): void;
}
