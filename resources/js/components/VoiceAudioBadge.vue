<script setup>
import { ref, onUnmounted } from 'vue';
import { Play, Pause, Mic } from 'lucide-vue-next';

const props = defineProps({
  audioUrl: {
    type: String,
    required: true,
  },
  duration: {
    type: String,
    default: '0:02',
  },
});

const audioRef = ref(null);
const isPlaying = ref(false);

function togglePlay() {
  const el = audioRef.value;
  if (!el) return;
  if (isPlaying.value) {
    el.pause();
  } else {
    el.play().catch(() => {
      isPlaying.value = false;
    });
  }
}

onUnmounted(() => {
  if (audioRef.value) {
    audioRef.value.pause();
  }
});
</script>

<template>
  <div class="inline-flex items-center gap-2 px-2.5 py-1.5 mb-1.5 rounded-xl bg-black/20 border border-white/20 text-white">
    <button
      type="button"
      @click="togglePlay"
      class="w-6 h-6 rounded-full bg-white/25 hover:bg-white/35 flex items-center justify-center transition-colors cursor-pointer shrink-0"
      :title="isPlaying ? 'Pausar nota de voz' : 'Reproducir nota de voz'"
    >
      <Pause v-if="isPlaying" class="w-3 h-3 text-white" />
      <Play v-else class="w-3 h-3 text-white ml-0.5" />
    </button>
    <div class="flex items-center gap-1.5 text-[11px] font-semibold">
      <Mic class="w-3 h-3 text-amber-200 shrink-0" />
      <span>Nota de voz</span>
      <span class="opacity-80 font-mono text-[10px]">• {{ duration }}</span>
    </div>
    <audio
      ref="audioRef"
      :src="audioUrl"
      preload="metadata"
      class="hidden"
      @play="isPlaying = true"
      @pause="isPlaying = false"
      @ended="isPlaying = false"
    />
  </div>
</template>
