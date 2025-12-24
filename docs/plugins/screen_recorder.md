# Screen Recorder Plugin

## Objective
The Screen Recorder plugin enables users to capture their screen activity alongside camera input. It is designed for creating tutorials, demos, and presentations directly within the Gaia Alpha environment without needing external software.

## Features

- **Screen Sharing**: Capture the entire screen, a specific window, or a browser tab.
- **Camera P-i-P (Picture-in-Picture)**: Overlay the user's camera feed onto the screen recording.
- **Microphone Support**: Record audio input along with the video.
- **High-Quality Recording**: Configurable resolution and frame rate.
- **Direct Library Integration**: Recordings are automatically saved to the [Media Library](media_library.md).

## Usage Workflow

1.  **Activation**: Navigate to the "Screen Recorder" tool from the sidebar.
2.  **Permissions**: Grant permissions for camera, microphone, and screen recording when prompted by the browser.
3.  **Setup**:
    - Toggle **Camera** on/off.
    - Select **Audio Input** level.
    - Click **Share Screen** to select the recording source.
4.  **Recording**:
    - Click **Start Recording** to begin. The camera feed will appear as a draggable overlay on the source.
    - Click **Stop Recording** to finish.
5.  **Review & Save**:
    - Preview the recording in the integrated player.
    - Click **Save to Library** to store the video in the Media Library.

## Architecture

### Backend
- **Controller**: `ScreenRecorder\Controller\ScreenRecorderController`
- **Service**: `ScreenRecorder\Service\ScreenRecorderService` (Handles file processing and library integration)
- **Model**: `ScreenRecorder\Model\ScreenRecording` (Stores metadata about recordings)

### Frontend
- **Component**: `plugins/ScreenRecorder/resources/js/ScreenRecorder.js`
- **APIs Used**:
    - `navigator.mediaDevices.getDisplayMedia()`: For screen capture.
    - `navigator.mediaDevices.getUserMedia()`: For camera and microphone.
    - `MediaRecorder API`: For encoding the stream into a video file.
- **Integration**: Registered via `UiManager` and injected into the "Tools" menu group.

## API Endpoints

- `GET /@/screen-recorder/status`: Check if the recording service is active.
- `POST /@/screen-recorder/upload`: Upload a recorded blob to the server.
- `GET /@/screen-recorder/recordings`: List previous recordings metadata.
- `DELETE /@/screen-recorder/recordings/:id`: Delete a recording record.

## Hooks

- `screen_recorder_start`: Triggered when a new recording session begins.
- `screen_recorder_stop`: Triggered when a recording is completed.
- `screen_recorder_upload_after`: Triggered after a recording file is successfully saved to the library.

## Configuration

Configurations can be adjusted in `plugin.json`:
- `RECORDING_MAX_DURATION`: Maximum allowed recording time in seconds (Default: `1800` / 30 mins).
- `DEFAULT_VIDEO_BITRATE`: Target bitrate for the MediaRecorder (Default: `2500000`).

## Dependencies
- **MediaLibrary** (`>=1.0.0`): Required for saving recordings.
