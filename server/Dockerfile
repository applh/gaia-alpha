FROM php:8.1-cli-alpine

# Install system dependencies and PHP extensions
# socket extension is required for both WebSocket and direct socket manipulation
# pcntl is recommended for signal handling (graceful shutdown)
RUN docker-php-ext-install sockets pcntl

# Create app directory
WORKDIR /app

# Copy application source
# We copy the entire project because the server needs class/ (autoloader) and other components
COPY . /app

# Expose WebSocket port
EXPOSE 8081

# Default Command
CMD ["php", "server/start.php"]
