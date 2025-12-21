<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Gaia Alpha</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #0f172a;
            --surface: #1e293b;
            --text: #f8fafc;
            --gray: #94a3b8;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .install-card {
            background-color: var(--surface);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
        }

        h1 {
            text-align: center;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        p.subtitle {
            text-align: center;
            color: var(--gray);
            margin-bottom: 2rem;
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 0.75rem;
            border-radius: 6px;
            border: 1px solid #334155;
            background-color: #0f172a;
            color: white;
            box-sizing: border-box;
            /* Fix padding issue */
        }

        input:focus {
            outline: 2px solid var(--primary);
            border-color: transparent;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: var(--primary-hover);
        }

        .error {
            color: #ef4444;
            text-align: center;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            display: none;
        }
    </style>
</head>

<body>
    <div class="install-card">
        <h1>Welcome</h1>
        <p class="subtitle">Create your admin account to get started.</p>

        <div id="error-msg" class="error"></div>

        <form id="install-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="admin" required autofocus autocomplete="off">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div style="position: relative; display: block; width: 100%;">
                    <input type="password" id="password" name="password" value="admin" required
                        style="width: 100%; padding-right: 40px; box-sizing: border-box;">
                    <button type="button"
                        onclick="const i = document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password';"
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--gray); padding: 0; display: flex; align-items: center; justify-content: center; width: auto;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            style="pointer-events: none;">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #334155;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="create_app" value="1" checked
                        onchange="document.getElementById('app-slug-group').style.display = this.checked ? 'block' : 'none'"
                        style="width: auto;">
                    Create App Dashboard Page
                </label>

                <div id="app-slug-group" class="form-group" style="margin-top: 1rem;">
                    <label for="app_slug">App URI Path</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="color: var(--gray);">/</span>
                        <input type="text" id="app_slug" name="app_slug" value="app" placeholder="app"
                            pattern="[a-z0-9-_]+" title="Lowercase letters, numbers, and hyphens only">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label>Site Configuration</label>
                    <div style="margin-bottom: 1rem;">
                        <label for="site_title" style="font-size:0.8em; color:var(--gray);">Site Title</label>
                        <input type="text" id="site_title" name="site_title" value="Gaia Alpha"
                            placeholder="e.g. My Company">
                    </div>
                    <div>
                        <label for="site_description" style="font-size:0.8em; color:var(--gray);">Site
                            Description</label>
                        <input type="text" id="site_description" name="site_description"
                            value="The unified open-source operating system." placeholder="Short description for SEO">
                    </div>
                </div>

                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-top: 1rem;">
                    <input type="checkbox" name="demo_data" value="1" checked style="width: auto;">
                    Populate with Demo Data (recommended)
                </label>

                <div class="form-group" style="margin-top: 1.5rem; border-top: 1px solid #334155; padding-top: 1rem;">
                    <label>Plugins</label>
                    <div id="plugins-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <span style="color: var(--gray); font-size: 0.875rem;">Loading plugins...</span>
                    </div>
                </div>
            </div>

            <button type="submit" style="margin-top: 1.5rem;">Create Account</button>
        </form>
    </div>

    <script>
        // Fetch plugins on load
        (async () => {
             try {
                 const res = await fetch('/@/install/plugins');
                 if (res.ok) {
                     const plugins = await res.json();
                     const container = document.getElementById('plugins-list');
                     container.innerHTML = '';
                     
                     plugins.forEach(p => {
                         const isCore = p.type === 'core';
                         const div = document.createElement('div');
                         div.style.display = 'flex';
                         div.style.alignItems = 'center';
                         div.style.gap = '0.5rem';
                         
                         const input = document.createElement('input');
                         input.type = 'checkbox';
                         input.name = 'plugins[]';
                         input.value = p.id;
                         input.style.width = 'auto';
                         input.checked = isCore; // Core checked by default
                         
                         const label = document.createElement('label');
                         label.textContent = p.name;
                         label.style.marginBottom = '0';
                         label.style.cursor = 'pointer';
                         label.style.fontSize = '0.875rem';
                         label.title = p.description;
                         
                         // Link label click to checkbox
                         label.onclick = () => { input.checked = !input.checked; };
                         
                         div.appendChild(input);
                         div.appendChild(label);
                         container.appendChild(div);
                     });
                 }
             } catch (e) {
                 console.error('Failed to load plugins', e);
             }
        })();

        document.getElementById('install-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const errorDiv = document.getElementById('error-msg');

            btn.disabled = true;
            btn.textContent = 'Creating...';
            errorDiv.style.display = 'none';

            const formData = new FormData(e.target);
            // Handle plugins array manually because Object.fromEntries doesn't handle multiple values for same key well
            const data = {};
            formData.forEach((value, key) => {
                if (key === 'plugins[]') {
                    if (!data['plugins']) data['plugins'] = [];
                    data['plugins'].push(value);
                } else {
                    data[key] = value;
                }
            });

            try {
                const res = await fetch('/@/install', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                if (res.ok) {
                    btn.textContent = 'Success! Redirecting...';
                    btn.style.backgroundColor = '#10b981'; // Green
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 500);
                } else {
                    const json = await res.json();
                    throw new Error(json.error || 'Installation failed');
                }
            } catch (err) {
                errorDiv.textContent = err.message;
                errorDiv.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Create Account';
            }
        });
    </script>
</body>

</html>