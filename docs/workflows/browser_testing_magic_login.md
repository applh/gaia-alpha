# Workflow: Browser Testing with Magic Login

This workflow describes how to use JSON Web Tokens (JWT) to bypass the login screen in automated browser tests (Selenium, Playwright, Cypress, etc.).

## The Problem
Standard login tests are slow because they require:
1.  Loading the login page.
2.  Typing the username.
3.  Typing the password.
4.  Submitting the form and waiting for the redirect.

## The Solution: Magic Login
The `JwtAuthMiddleware` allows you to "bootstrap" a standard PHP session by passing a valid JWT in the URL query string.

### How it Works
1.  **Generate a Token**: Use the CLI or code to generate a token for the test user.
2.  **Navigate with Token**: specific the `?token=` parameter in your initial navigation.
3.  **Session Created**: The server validates the token and immediately creates a logged-in PHP session.
4.  **Cookie Set**: The server returns the standard `PHPSESSID` cookie.
5.  **Continue Testing**: Subsequent requests use the cookie, just like a normal user.

## Example (Playwright)

```javascript
// tests/browser/admin.spec.js

test('Admin Dashboard loads', async ({ page, request }) => {
    // 1. Generate Token (Backend Step)
    // You might call a helper API or exec a CLI command here
    const token = await generateAdminToken(); 

    // 2. Magic Login
    // Bypass the login form entirely!
    await page.goto(`http://localhost/@/admin?token=${token}`);

    // 3. Verify
    await expect(page).toHaveURL(/.*\/@\/admin\/dashboard/);
    await expect(page.locator('.user-profile')).toContainText('Admin User');
});
```

## Security Note
This feature effectively allows "Magic Links". Ensure your JWT secret is strong and consider disabling the query string check in production if stricter security is required.
