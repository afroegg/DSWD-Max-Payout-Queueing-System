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
}

.display-container {
    text-align: center;
}

.label {
    font-size: 40px;
    letter-spacing: 5px;
    margin-bottom: 20px;
}

.queue-number {
    font-size: 180px;
    font-weight: bold;
    letter-spacing: 10px;
}

.audio-btn {
    position: fixed;
    top: 20px;
    left: 20px;
    padding: 12px;
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

/* 🔥 LOAD VOICES PROPERLY */
function initVoices() {
    const voices = speechSynthesis.getVoices();

    if (!voices.length) return;

    selectedVoice =
        voices.find(v => v.lang === "en-PH") ||
        voices.find(v => v.lang && v.lang.startsWith("en")) ||
        voices[0];
}

/*  VERY IMPORTANT: WAIT FOR VOICES */
speechSynthesis.onvoiceschanged = initVoices;

/* ENABLE AUDIO */
function enableAudio() {
    initVoices();

    const btn = document.getElementById("audioBtn");

    //  hide immediately (reliable)
    btn.style.display = "none";

    const test = new SpeechSynthesisUtterance("Audio enabled");

    if (selectedVoice) test.voice = selectedVoice;

    test.rate = 1;

    // enable audio regardless of event reliability
    audioEnabled = true;

    speechSynthesis.cancel();
    speechSynthesis.speak(test);
}

/* SPEAK */
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

/* FETCH */
async function loadNowServing() {
    try {
        const res = await fetch("../api/live_data.php");
        const data = await res.json();

        const serving = data.queue.find(q => q.status === "serving");
        const el = document.getElementById("queueNumber");

        if (serving) {
            const currentQueue = serving.queue_number;

            el.textContent = currentQueue;

            if (currentQueue !== lastQueue) {
                speakQueue(currentQueue);
                lastQueue = currentQueue;
            }

        } else {
            el.textContent = "---";
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