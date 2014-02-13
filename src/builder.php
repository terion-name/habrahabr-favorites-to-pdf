<?php

$phar = new Phar('../fav2pdf.phar');
$phar->startBuffering();
$phar->buildFromDirectory('../src');
$defaultStub = $phar->createDefaultStub('fav2pdf.php');
$phar->offsetUnset('builder.php');
$stub = "#!/usr/bin/php \n" . $defaultStub;
$phar->setStub($stub);
$phar->stopBuffering();
chmod("../fav2pdf.phar", 0755);
if (file_exists("../fav2pdf")) {
    unlink("../fav2pdf");
}
rename("../fav2pdf.phar", "../fav2pdf");