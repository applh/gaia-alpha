---
description: How to perform browser testing in Gaia Alpha
---

To verify changes in the browser:

1. **Start the PHP server**:
   // turbo
   `php -S localhost:8000 -t www`
   
2. **Ensure the server is running**:
   Check the command status or browse to `http://localhost:8000`.

3. **Run your browser tests**:
   Use the `browser_subagent` tool and target `http://localhost:8000`.

4. **Shutdown the server**:
   Terminate the background process once testing is complete.