<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'tesseractPath' => 'C:/"Program Files"/Tesseract-OCR/tesseract',
    'phpBinFile' => (new \Symfony\Component\Process\PhpExecutableFinder)->find(),
    'yiiConsoleFile' => $_SERVER['DOCUMENT_ROOT'] .'/yii '
];
