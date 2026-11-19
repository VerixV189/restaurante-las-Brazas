import { ref, computed, onUnmounted } from 'vue';
import axios from 'axios';

const PREFERRED_MIME_TYPES = [
  'audio/webm;codecs=opus',
  'audio/webm',
  'audio/ogg;codecs=opus',
  'audio/ogg',
  'audio/mp4',
];

function pickSupportedMimeType() {
  if (typeof MediaRecorder === 'undefined' || typeof MediaRecorder.isTypeSupported !== 'function') {
    return '';
  }
  for (const candidate of PREFERRED_MIME_TYPES) {
    if (MediaRecorder.isTypeSupported(candidate)) {
      return candidate;
    }
  }
  return '';
}

function extensionForMime(mime) {
  const lower = (mime || '').toLowerCase();
  if (lower.includes('ogg')) return 'ogg';
  if (lower.includes('mp4') || lower.includes('m4a')) return 'm4a';
  if (lower.includes('wav')) return 'wav';
  if (lower.includes('mpeg') || lower.includes('mp3')) return 'mp3';
  return 'webm';
}

function formatSeconds(totalSec) {
  const safe = Math.max(0, Math.floor(totalSec || 0));
  const mins = Math.floor(safe / 60);
  const secs = safe % 60;
  return `${mins}:${secs.toString().padStart(2, '0')}`;
}

/**
 * Composable universal de grabación de micrófono + transcripción IA (Gemini Multimodal / Whisper).
 * Funciona en Brave, Chrome, Edge, Opera, Firefox, Safari, Android e iOS sin depender
 * de Web Speech API bloqueada por privacidad.
 */
export function useVoiceRecorder({
  endpoint = '/ai/transcribe',
  context = 'general',
  maxDurationSec = 60,
  onTranscribed = null,
  onError = null,
} = {}) {
  const isRecording = ref(false);
  const isTranscribing = ref(false);
  const durationSec = ref(0);
  const audioLevels = ref([22, 35, 48, 30, 42, 28, 20]);
  const voiceError = ref('');

  const formattedDuration = computed(() => formatSeconds(durationSec.value));

  let mediaRecorder = null;
  let mediaStream = null;
  let audioChunks = [];
  let timerInterval = null;
  let audioContext = null;
  let analyser = null;
  let rafId = null;
  let startedAtMs = 0;
  let pendingMode = 'send';
  let wasCancelled = false;
  let errorTimeout = null;
  const createdObjectUrls = [];

  function setError(message) {
    voiceError.value = message;
    if (errorTimeout) clearTimeout(errorTimeout);
    if (message) {
      errorTimeout = setTimeout(() => {
        voiceError.value = '';
      }, 7500);
      if (typeof onError === 'function') {
        onError(message);
      }
    }
  }

  function clearError() {
    voiceError.value = '';
    if (errorTimeout) {
      clearTimeout(errorTimeout);
      errorTimeout = null;
    }
  }

  function releaseHardware() {
    if (timerInterval) {
      clearInterval(timerInterval);
      timerInterval = null;
    }
    if (rafId) {
      cancelAnimationFrame(rafId);
      rafId = null;
    }
    if (audioContext) {
      audioContext.close().catch(() => {});
      audioContext = null;
      analyser = null;
    }
    if (mediaStream) {
      mediaStream.getTracks().forEach((track) => {
        try {
          track.stop();
        } catch (_) {}
      });
      mediaStream = null;
    }
    mediaRecorder = null;
    isRecording.value = false;
    audioLevels.value = [22, 35, 48, 30, 42, 28, 20];
  }

  function startLevelMeter(stream) {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;

      audioContext = new AudioCtx();
      const source = audioContext.createMediaStreamSource(stream);
      analyser = audioContext.createAnalyser();
      analyser.fftSize = 64;
      analyser.smoothingTimeConstant = 0.72;
      source.connect(analyser);

      const freqData = new Uint8Array(analyser.frequencyBinCount);
      const bucketIndices = [1, 3, 5, 7, 9, 11, 13];

      const tick = () => {
        if (!isRecording.value || !analyser) return;
        analyser.getByteFrequencyData(freqData);
        audioLevels.value = bucketIndices.map((idx) => {
          const raw = freqData[idx] || 0;
          return Math.max(18, Math.min(100, Math.round((raw / 255) * 100) + 14));
        });
        rafId = requestAnimationFrame(tick);
      };
      rafId = requestAnimationFrame(tick);
    } catch (_) {
      // Si el navegador restringe AudioContext, la grabación sigue funcionando normalmente
    }
  }

  async function startRecording() {
    if (isRecording.value || isTranscribing.value) return;
    clearError();

    if (typeof window !== 'undefined' && !window.isSecureContext) {
      setError('El micrófono requiere conexión segura (HTTPS o localhost).');
      return;
    }

    if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function' || typeof MediaRecorder === 'undefined') {
      setError('Tu navegador no permite grabar audio desde el micrófono. Prueba en Chrome, Brave, Edge, Firefox o Safari actualizado.');
      return;
    }

    try {
      mediaStream = await navigator.mediaDevices.getUserMedia({
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true,
        },
      });
    } catch (err) {
      const name = err?.name || '';
      if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
        setError('Permiso de micrófono denegado. Habilita el micrófono en el ícono de candado del navegador y vuelve a intentar.');
      } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
        setError('No se detectó ningún micrófono conectado en este dispositivo.');
      } else if (name === 'NotReadableError' || name === 'TrackStartError') {
        setError('El micrófono está siendo usado por otra aplicación. Ciérrala e intenta nuevamente.');
      } else {
        setError('No fue posible acceder al micrófono. Verifica los permisos del dispositivo.');
      }
      return;
    }

    const chosenMime = pickSupportedMimeType();
    audioChunks = [];
    wasCancelled = false;
    pendingMode = 'send';
    durationSec.value = 0;
    startedAtMs = Date.now();

    try {
      const options = chosenMime
        ? { mimeType: chosenMime, audioBitsPerSecond: 32000 }
        : { audioBitsPerSecond: 32000 };
      mediaRecorder = new MediaRecorder(mediaStream, options);
    } catch (_) {
      mediaRecorder = new MediaRecorder(mediaStream);
    }

    mediaRecorder.ondataavailable = (event) => {
      if (event.data && event.data.size > 0) {
        audioChunks.push(event.data);
      }
    };

    mediaRecorder.onerror = () => {
      releaseHardware();
      setError('Ocurrió un error al grabar audio. Intenta nuevamente.');
    };

    mediaRecorder.onstop = async () => {
      const elapsedMs = Date.now() - startedAtMs;
      const recordedSeconds = Math.max(1, Math.round(elapsedMs / 1000));
      const finalDurationLabel = formatSeconds(recordedSeconds);
      const effectiveMime = mediaRecorder?.mimeType || chosenMime || 'audio/webm';
      const chunksSnapshot = [...audioChunks];
      const modeSnapshot = pendingMode;
      const cancelledSnapshot = wasCancelled;

      audioChunks = [];
      releaseHardware();

      if (cancelledSnapshot) {
        return;
      }

      const blob = new Blob(chunksSnapshot, { type: effectiveMime });
      if (elapsedMs < 450 || blob.size < 250) {
        setError('Grabación muy corta. Habla al menos un segundo antes de enviar el audio.');
        return;
      }

      await uploadAndTranscribe(blob, effectiveMime, modeSnapshot, finalDurationLabel, recordedSeconds);
    };

    isRecording.value = true;
    startLevelMeter(mediaStream);

    timerInterval = setInterval(() => {
      durationSec.value = Math.floor((Date.now() - startedAtMs) / 1000);
      if (durationSec.value >= maxDurationSec) {
        stopAndTranscribe('send');
      }
    }, 250);

    mediaRecorder.start(250);
  }

  async function uploadAndTranscribe(blob, mimeType, mode, durationLabel, seconds) {
    isTranscribing.value = true;
    clearError();

    const ext = extensionForMime(mimeType);
    const formData = new FormData();
    formData.append('audio', blob, `voz.${ext}`);
    formData.append('mime_type', mimeType);
    formData.append('context', context);

    try {
      const res = await axios.post(endpoint, formData);
      const transcript = (res.data?.transcript || '').trim();

      if (!transcript) {
        setError('No se detectó texto hablado en el audio. Intenta hablar un poco más fuerte.');
        return;
      }

      let audioUrl = null;
      if (mode === 'send') {
        audioUrl = URL.createObjectURL(blob);
        createdObjectUrls.push(audioUrl);
      }

      if (typeof onTranscribed === 'function') {
        onTranscribed({
          transcript,
          mode,
          audioUrl,
          durationLabel,
          durationSec: seconds,
          engine: res.data?.engine || 'ai',
        });
      }
    } catch (err) {
      const status = err.response?.status;
      const serverMsg = err.response?.data?.message;
      if (status === 429) {
        setError('Enviaste varios audios muy seguido. Espera unos segundos e intenta nuevamente.');
      } else if (serverMsg) {
        setError(serverMsg);
      } else {
        setError('No se pudo procesar el audio. Verifica tu conexión a internet e intenta de nuevo.');
      }
    } finally {
      isTranscribing.value = false;
    }
  }

  function stopAndTranscribe(mode = 'send') {
    if (!isRecording.value || !mediaRecorder) return;
    pendingMode = mode;
    wasCancelled = false;
    try {
      if (mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
      }
    } catch (_) {
      releaseHardware();
    }
  }

  function cancelRecording() {
    if (!isRecording.value && !mediaRecorder) return;
    wasCancelled = true;
    try {
      if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
      } else {
        releaseHardware();
      }
    } catch (_) {
      releaseHardware();
    }
  }

  function toggleRecording(defaultMode = 'send') {
    if (isRecording.value) {
      stopAndTranscribe(defaultMode);
    } else if (!isTranscribing.value) {
      startRecording();
    }
  }

  function cleanup() {
    if (errorTimeout) {
      clearTimeout(errorTimeout);
      errorTimeout = null;
    }
    wasCancelled = true;
    try {
      if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
      }
    } catch (_) {}
    releaseHardware();
    while (createdObjectUrls.length > 0) {
      const url = createdObjectUrls.pop();
      try {
        URL.revokeObjectURL(url);
      } catch (_) {}
    }
  }

  onUnmounted(() => {
    cleanup();
  });

  return {
    isRecording,
    isTranscribing,
    durationSec,
    formattedDuration,
    audioLevels,
    voiceError,
    startRecording,
    stopAndTranscribe,
    cancelRecording,
    toggleRecording,
    clearError,
    cleanup,
  };
}
