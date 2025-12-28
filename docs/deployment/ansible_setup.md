# Deploying Gaia Alpha with Ansible

This guide details how to automate the provisioning and deployment of Gaia Alpha using Ansible. This method is recommended for maintaining multiple servers or ensuring reproducible production environments.

## Prerequisites

-   **Control Machine**: The computer where you will run Ansible (your local laptop or a CI/CD runner).
-   **Target Server (VPS)**: A fresh Ubuntu 20.04/22.04 server with SSH access.
-   **Ansible**: Installed on your control machine (`brew install ansible` or `pip install ansible`).

## 1. Setup Inventory

Edit the `ansible/inventory` file to define your server connection and variables.

```ini
[gaia_servers]
# Replace with your VPS IP
203.0.113.10 ansible_user=root

[gaia_servers:vars]
# Configuration
repo_url=https://github.com/applh/gaia-alpha.git
domain_name=example.com
acme_email=admin@example.com
# Secrets
gaia_db_pass=secure_db_password
gaia_db_root_pass=secure_root_password
```

> [!WARNING]
> For production, do not commit secrets to Git. Use [Ansible Vault](https://docs.ansible.com/ansible/latest/vault_guide/index.html) or pass sensitive variables via the command line.

## 2. Run the Playbook

Execute the playbook to provision the server and deploy the application:

```bash
ansible-playbook -i ansible/inventory ansible/playbook.yml
```

## What the Playbook Does

1.  **System Provisioning**:
    -   Updates `apt` cache.
    -   Installs Git, Curl, and required dependencies.
    -   Installs the official Docker Engine and Docker Compose plugin.
2.  **Application Deployment**:
    -   Clones the Gaia Alpha repository to `/opt/gaia-alpha`.
    -   Generates the `.env` file from your inventory variables.
    -   Creates the required `proxy` Docker network.
    -   Starts the stack using `docker/deployment/docker-compose.prod.yml`.

## 3. Post-Deployment

After the playbook completes:

1.  Visit `https://example.com` to verify the site is up.
2.  To update the application later, you can simply re-run the playbook. It uses `git force: yes` to pull the latest code on the `main` branch.

## Troubleshooting

-   **SSH Connection**: Ensure you can SSH into the server manually (`ssh root@your-ip`) before running Ansible.
-   **Python Missing**: If the server is extremely minimal, you might need to install Python first:
    ```bash
    ssh root@your-ip "apt update && apt install -y python3"
    ```
