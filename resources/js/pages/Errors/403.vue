<script setup>
import { Link } from '@inertiajs/vue3';
import { ShieldX } from 'lucide-vue-next';
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center p-6">
    <div class="text-center max-w-md">
      <!-- Icon -->
      <div class="flex justify-center mb-6">
        <div class="bg-red-500/10 border border-red-500/30 rounded-full p-6">
          <ShieldX class="w-16 h-16 text-red-400" />
        </div>
      </div>

      <!-- Error Code -->
      <p class="text-red-400 text-sm font-semibold uppercase tracking-widest mb-2">Error 403</p>

      <!-- Title -->
      <h1 class="text-3xl font-bold text-slate-100 mb-3">Acceso No Permitido</h1>

      <!-- Description -->
      <p class="text-slate-400 text-sm mb-8">
        No tienes los permisos necesarios para acceder a esta sección.
        Contacta al administrador si crees que esto es un error.
      </p>

      <!-- Actions -->
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <Link
          href="/dashboard"
          class="px-6 py-2.5 bg-orange-600 hover:bg-orange-500 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Ir al Dashboard
        </Link>
        <Link
          href="/"
          class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-semibold rounded-xl transition-colors"
        >
          Ir al Inicio
        </Link>
      </div>
    </div>
  </div>
</template>
