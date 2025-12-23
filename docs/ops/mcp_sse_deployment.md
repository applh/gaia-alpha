# MCP SSE Deployment Guide

## Quick Start

### 1. Access the Test Page

Navigate to: `http://your-domain:8000/plugins/McpServer/test_sse.html`

Or copy the file to `www/` for easier access.

### 2. Test the Connection

1. Click "Connect" button
2. Wait for "Connected" status
3. Click "List Tools" to test functionality

## PHP Configuration

### Required Settings

Add to `php.ini` or `.user.ini`:

```ini
max_execution_time = 300
memory_limit = 128M
session.gc_maxlifetime = 3600
output_buffering = Off
```

### Development Server

```bash
# Start PHP built-in server
php -S localhost:8000 -t www/

# Access test page
open http://localhost:8000/plugins/McpServer/test_sse.html
```

## Nginx Configuration

If using Nginx, add this to your server block:

```nginx
location /@/mcp/stream {
    proxy_pass http://127.0.0.1:9000;
    proxy_buffering off;
    proxy_cache off;
    proxy_read_timeout 300s;
    proxy_connect_timeout 10s;
    proxy_set_header Connection '';
    proxy_http_version 1.1;
    chunked_transfer_encoding off;
}
```

## Apache Configuration

If using Apache with PHP-FPM:

```apache
<Location "/@/mcp/stream">
    ProxyPass http://localhost:9000 timeout=300
    ProxyPassReverse http://localhost:9000
    SetEnv proxy-nokeepalive 1
</Location>
```

## Usage Example

### JavaScript

```javascript
// Create client
const client = new McpClient();

// Connect
await client.connect();

// Call methods
const tools = await client.call('tools/list');
console.log('Available tools:', tools);

const pages = await client.call('tools/call', {
    name: 'list_pages',
    arguments: {site: 'default'}
});
console.log('Pages:', pages);

// Disconnect
client.disconnect();
```

## Troubleshooting

### Connection Fails

1. Check PHP error log
2. Verify session support is enabled
3. Check firewall/proxy settings
4. Ensure output buffering is disabled

### Timeout Issues

1. Increase `max_execution_time` in PHP
2. Adjust proxy timeout settings
3. Check connection is not being buffered

### Memory Issues

1. Increase `memory_limit` in PHP
2. Monitor memory usage during long connections
3. Consider reducing connection duration

## Limitations

> [!WARNING]
> **Proof-of-Concept Only**
> 
> - Maximum 5-10 concurrent connections recommended
> - Each connection uses ~30-50MB RAM
> - Connection timeout: 5 minutes
> - Not suitable for production at scale

## Monitoring

### Check Active Connections

```bash
# Count PHP processes
ps aux | grep "mcp/stream" | wc -l
```

### Monitor Memory Usage

```bash
# Watch PHP memory usage
watch -n 1 'ps aux | grep php | awk "{sum+=\$6} END {print sum/1024 \" MB\"}"'
```

## Security

1. **Authentication**: Add authentication to SSE endpoints
2. **Rate Limiting**: Limit requests per session
3. **Session Validation**: Validate session IDs
4. **CORS**: Configure appropriate CORS headers if needed

## Next Steps

For production use, consider:
- Node.js implementation for better concurrency
- Redis for session storage
- Load balancer with sticky sessions
- WebSocket for bidirectional communication
