/**
 * MCP Client for SSE Transport
 * 
 * Usage:
 *   const client = new McpClient();
 *   await client.connect();
 *   const tools = await client.call('tools/list');
 *   console.log(tools);
 */
class McpClient {
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl;
        this.sessionId = null;
        this.eventSource = null;
        this.requestId = 0;
        this.pendingRequests = new Map();
        this.connected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
    }

    /**
     * Connect to MCP server via SSE
     * @returns {Promise<void>}
     */
    async connect() {
        try {
            // Create session
            const response = await fetch(`${this.baseUrl}/@/mcp/session`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            if (!response.ok) {
                throw new Error(`Failed to create session: ${response.statusText}`);
            }

            const data = await response.json();
            this.sessionId = data.session_id;

            console.log('[MCP] Session created:', this.sessionId);

            // Open SSE connection
            this.eventSource = new EventSource(
                `${this.baseUrl}/@/mcp/stream?session_id=${this.sessionId}`
            );

            // Set up event listeners
            this.eventSource.addEventListener('connected', (event) => {
                const data = JSON.parse(event.data);
                console.log('[MCP] Connected to server:', data);
                this.connected = true;
                this.reconnectAttempts = 0;
            });

            this.eventSource.addEventListener('message', (event) => {
                const response = JSON.parse(event.data);
                this.handleResponse(response);
            });

            this.eventSource.addEventListener('ping', (event) => {
                // Heartbeat received
                console.log('[MCP] Heartbeat');
            });

            this.eventSource.addEventListener('close', (event) => {
                const data = JSON.parse(event.data);
                console.log('[MCP] Connection closed:', data.reason);
                this.connected = false;
            });

            this.eventSource.addEventListener('error', (event) => {
                console.error('[MCP] SSE error:', event);
                this.handleError(event);
            });

            this.eventSource.onerror = (event) => {
                console.error('[MCP] Connection error');
                this.handleError(event);
            };

        } catch (error) {
            console.error('[MCP] Connection failed:', error);
            throw error;
        }
    }

    /**
     * Call an MCP method
     * @param {string} method Method name (e.g., 'tools/list')
     * @param {object} params Method parameters
     * @returns {Promise<any>} Method result
     */
    async call(method, params = {}) {
        if (!this.connected) {
            throw new Error('Not connected to MCP server');
        }

        const id = ++this.requestId;
        const request = {
            jsonrpc: '2.0',
            id,
            method,
            params
        };

        // Send request via POST
        try {
            const response = await fetch(`${this.baseUrl}/@/mcp/request`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    request
                })
            });

            if (!response.ok) {
                throw new Error(`Request failed: ${response.statusText}`);
            }

            console.log(`[MCP] Request sent: ${method} (id: ${id})`);

        } catch (error) {
            console.error('[MCP] Request error:', error);
            throw error;
        }

        // Return promise that resolves when response arrives via SSE
        return new Promise((resolve, reject) => {
            this.pendingRequests.set(id, { resolve, reject, method });

            // Timeout after 30 seconds
            setTimeout(() => {
                if (this.pendingRequests.has(id)) {
                    this.pendingRequests.delete(id);
                    reject(new Error(`Request timeout: ${method}`));
                }
            }, 30000);
        });
    }

    /**
     * Handle response from SSE stream
     * @param {object} response JSON-RPC response
     */
    handleResponse(response) {
        const id = response.id;

        if (!this.pendingRequests.has(id)) {
            console.warn('[MCP] Received response for unknown request:', id);
            return;
        }

        const { resolve, reject, method } = this.pendingRequests.get(id);
        this.pendingRequests.delete(id);

        if (response.error) {
            console.error(`[MCP] Error response for ${method}:`, response.error);
            reject(new Error(response.error.message));
        } else {
            console.log(`[MCP] Response received for ${method} (id: ${id})`);
            resolve(response.result);
        }
    }

    /**
     * Handle SSE errors and reconnection
     * @param {Event} event Error event
     */
    handleError(event) {
        this.connected = false;

        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);

            console.log(`[MCP] Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);

            setTimeout(() => {
                this.reconnect();
            }, delay);
        } else {
            console.error('[MCP] Max reconnection attempts reached');
        }
    }

    /**
     * Reconnect to server
     */
    async reconnect() {
        console.log('[MCP] Attempting to reconnect...');

        if (this.eventSource) {
            this.eventSource.close();
        }

        try {
            await this.connect();
        } catch (error) {
            console.error('[MCP] Reconnection failed:', error);
        }
    }

    /**
     * Disconnect from server
     */
    disconnect() {
        console.log('[MCP] Disconnecting...');

        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }

        this.connected = false;
        this.sessionId = null;
        this.pendingRequests.clear();
    }

    /**
     * Check if connected
     * @returns {boolean}
     */
    isConnected() {
        return this.connected;
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = McpClient;
}
