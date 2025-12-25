<?php
// scripts/test_server.php

$port = 8001;
$docroot = realpath(__DIR__ . '/../');

echo "\n";
echo "-------------------------------------------------------\n";
echo "  Gaia Alpha Test Server\n";
echo "-------------------------------------------------------\n";
echo "  e.g. Frontend Tests: http://localhost:$port/tests/js/index.html\n";
echo "-------------------------------------------------------\n";
echo "  Document Root: $docroot\n";
echo "  Address:       http://localhost:$port\n";
echo "-------------------------------------------------------\n\n";

passthru("php -S localhost:$port -t " . escapeshellarg($docroot));
