#!/bin/bash
# Start the Gaia Alpha development server

PORT=${1:-8000}
echo "Starting Gaia Alpha server at http://localhost:$PORT..."
php -S localhost:$PORT -t www www/index.php
