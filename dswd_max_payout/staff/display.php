<?php
include('../config/db.php');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Now Serving</title>

<style>
body {
    margin: 0;
    height: 100vh;
    background: #0f2f56;
    color: white;
    font-family: Arial, sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.display-container {
    text-align: center;
}

.label {
    font-size: 40px;
    letter-spacing: 5px;
    margin-bottom: 20px;
    opacity: 0.85;
}

.queue-number {
    font-size: 180px;
    font-weight: bold;
    letter-spacing: 10px;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.queue-number.active {
    animation: pulse 1.5s infinite;
}

.audio-btn {
    position: fixed;
    top: 20px;
    left: 20px;
    padding: 12px 16px;
    font-size: 14px;
    border: none;
    background: white;
    color: #0f2f56;
    font-weight: bold;
    cursor: pointer;
}
</style>

</head>

<body>

<button id="audioBtn" class="audio-btn" onclick="enableAudio()">
    Enable Audio
</button>

<div class="display-container">
    <div class="label">NOW SERVING</div>
    <div class="queue-number" id="queueNumber">---</div>
</div>

<script>
let audioEnabled = false;
let lastQueue = null;
let selectedVoice = null;

function initVoices() {
    const voices = speechSynthesis.getVoices();
    if (!voices.length) return;

    selectedVoice =
        voices.find(v => v.lang === "en-PH") ||
        voices.find(v => v.lang && v.lang.startsWith("en")) ||
        voices[0];
}

speechSynthesis.onvoiceschanged = initVoices;

function enableAudio() {
    initVoices();

    const btn = document.getElementById("audioBtn");
    btn.style.display = "none";

    const test = new SpeechSynthesisUtterance("Audio enabled");
    if (selectedVoice) {
        test.voice = selectedVoice;
        test.lang = selectedVoice.lang;
    }

    audioEnabled = true;

    speechSynthesis.cancel();
    speechSynthesis.speak(test);
}

function speakQueue(number) {
    if (!audioEnabled) return;

    const msg = new SpeechSynthesisUtterance(
        `Queue ${number}, please proceed to the counter`
    );

    if (selectedVoice) {
        msg.voice = selectedVoice;
        msg.lang = selectedVoice.lang;
    }

    msg.rate = 0.9;

    speechSynthesis.cancel();
    speechSynthesis.speak(msg);
}

async function loadNowServing() {
    try {
        const res = await fetch("../api/live_data.php");
        const data = await res.json();

        const currentQueue = data.currentServing;
        const el = document.getElementById("queueNumber");

        if (currentQueue) {
            el.textContent = currentQueue;
            el.classList.add("active");

            if (currentQueue !== lastQueue) {
                speakQueue(currentQueue);
                lastQueue = currentQueue;
            }
        } else {
            el.textContent = "---";
            el.classList.remove("active");
        }

    } catch (err) {
        console.error(err);
    }
}

setInterval(loadNowServing, 2000);
loadNowServing();
</script>

</body>
</html>
