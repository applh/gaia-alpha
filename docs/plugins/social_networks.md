# Social Networks Plugin

## Objective
The Social Networks plugin allows users to connect their Gaia Alpha instance to various social media platforms (X, LinkedIn, YouTube, TikTok, etc.) to streamline content publishing. It supports sharing text, images, PDFs, and videos directly from the system.

## Features

- **Multi-Platform Support**: Connect to X (formerly Twitter), LinkedIn, YouTube, and TikTok.
- **Rich Media Publishing**: Publish text updates, image galleries, PDF documents, and video content.
- **Unified Media Library Integration**: Pull assets directly from the [Media Library](media_library.md) for publishing.
- **Smart Setup Wizard**: Integrated panel to help users find and enter API keys with platform-specific instructions and direct links.
- **OAuth2 One-Click Connect**: Automatically retrieve access tokens for supported platforms (LinkedIn, YouTube, TikTok) without manual key entry.
- **Scheduling**: (Upcoming) Schedule posts to be published at a later date.
- **Analytics**: Track engagement and performance for published content.

## Usage Workflow

1.  **Platform Connection & Setup**:
    - Navigate to **Social Networks** > **Settings**.
    - Use the **Setup Wizard** to find your API keys. The system provides deep links to platform developer portals (e.g., [X Dev Portal](https://developer.x.com/)).
    - For OAuth2-enabled platforms, simply click **Connect** to authorize Gaia Alpha.
2.  **Compose Content**:
    - Go to the **Social Networks** dashboard.
    - Select the platforms you want to post to.
    - Write your caption and select attachments (images, PDFs, or videos).
3.  **Preview**:
    - Review how the post will appear on each platform.
4.  **Publish**:
    - Click **Publish Now** to send the content immediately.

## Architecture

### Backend
- **Controller**: `SocialNetworks\Controller\SocialNetworksController`
- **Service**: `SocialNetworks\Service\SocialNetworksService` (Handles OAuth flows and API interactions with platforms)
- **Service**: `SocialNetworks\Service\ConfigService` (Manages API keys and secure storage of tokens)
- **Model**: `SocialNetworks\Model\SocialAccount`, `SocialNetworks\Model\SocialPost`
- **Providers**: `SocialNetworks\Service\Providers\` (Platform-specific logic for X, LinkedIn, etc.)

### Frontend
- **Component**: `plugins/SocialNetworks/resources/js/SocialNetworks.js` (Main Dashboard)
- **Component**: `plugins/SocialNetworks/resources/js/components/SetupWizard.js` (API Key Entry & Help)
- **Component**: `plugins/SocialNetworks/resources/js/components/Composer.js` (Content Creation)
- **Integration**: Registered via `UiManager` and injected into the sidebar.

## API Endpoints

- `GET /@/social-networks/accounts`: List connected social media accounts.
- `POST /@/social-networks/publish`: Send content to one or more platforms.
- `GET /@/social-networks/posts`: Retrieve a history of published posts.
- `DELETE /@/social-networks/accounts/:id`: Disconnect a platform account.

## Hooks

- `social_networks_publish_before`: Triggered before a post is sent to external APIs.
- `social_networks_publish_after`: Triggered after a successful publication.
- `social_networks_account_connected`: Triggered when a new OAuth connection is established.

## Configuration

Configurations are stored in `plugin.json` and overridden by `Env` variables:
- `X_API_KEY`: API Key for X integration.
- `LINKEDIN_CLIENT_ID`: Client ID for LinkedIn OAuth.
- `YOUTUBE_CLIENT_ID`: Client ID for YouTube/Google integration.
- `TIKTOK_CLIENT_KEY`: Client Key for TikTok integration.
