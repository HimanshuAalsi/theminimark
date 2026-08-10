<script setup lang="ts">
/** Soft stationery-themed background — bookmarks, paper arcs, mint glow */
</script>

<template>
  <div class="home-amb" aria-hidden="true">
    <div class="home-amb__mesh" />
    <svg class="home-amb__svg" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
      <path
        class="home-amb__stroke"
        d="M-40 620 Q 280 480 520 560 T 980 520 T 1500 640"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        stroke-dasharray="8 14"
      />
      <g class="home-amb__mark home-amb__mark--a">
        <rect x="0" y="0" width="52" height="78" rx="6" fill="currentColor" opacity="0.07" />
        <path d="M26 58 L26 78 L0 68 Z" fill="currentColor" opacity="0.09" />
      </g>
      <g class="home-amb__mark home-amb__mark--b">
        <rect x="0" y="0" width="38" height="58" rx="5" fill="currentColor" opacity="0.05" />
        <path d="M19 44 L19 58 L0 50 Z" fill="currentColor" opacity="0.07" />
      </g>
      <circle class="home-amb__ring home-amb__ring--a" cx="200" cy="180" r="72" fill="none" stroke="currentColor" stroke-width="1" opacity="0.12" />
      <circle class="home-amb__ring home-amb__ring--b" cx="1280" cy="720" r="96" fill="none" stroke="currentColor" stroke-width="1" opacity="0.08" />
    </svg>
    <div class="home-amb__grain" />
  </div>
</template>

<style scoped>
.home-amb {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
  color: var(--tm-accent, #3a8f7c);
}

.home-amb__mesh {
  position: absolute;
  inset: -20% -10%;
  background:
    radial-gradient(ellipse 55% 45% at 88% 8%, rgba(98, 184, 158, 0.14) 0%, transparent 55%),
    radial-gradient(ellipse 40% 35% at 12% 72%, rgba(42, 107, 95, 0.1) 0%, transparent 50%),
    radial-gradient(ellipse 30% 25% at 50% 40%, rgba(235, 230, 222, 0.45) 0%, transparent 60%);
}

.home-amb__svg {
  position: absolute;
  width: 100%;
  min-width: 900px;
  height: auto;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  opacity: 0.85;
}

.home-amb__mark--a {
  transform: translate(1180px, 120px);
}

.home-amb__mark--b {
  transform: translate(140px, 680px) rotate(-12deg);
}

.home-amb__grain {
  position: absolute;
  inset: 0;
  opacity: 0.035;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  background-size: 180px 180px;
}

@media (prefers-reduced-motion: no-preference) {
  .home-amb__mark--a {
    animation: home-mark-a 14s ease-in-out infinite;
  }

  .home-amb__mark--b {
    animation: home-mark-b 18s ease-in-out infinite;
  }

  .home-amb__stroke {
    animation: home-dash 22s linear infinite;
  }

  .home-amb__ring--a {
    animation: home-ring 8s ease-in-out infinite;
  }

  .home-amb__ring--b {
    animation: home-ring 11s ease-in-out infinite 2s;
  }
}

@keyframes home-mark-a {
  0%,
  100% {
    transform: translate(1180px, 120px) rotate(0deg);
  }
  50% {
    transform: translate(1170px, 140px) rotate(5deg);
  }
}

@keyframes home-mark-b {
  0%,
  100% {
    transform: translate(140px, 680px) rotate(-12deg);
  }
  50% {
    transform: translate(152px, 665px) rotate(-5deg);
  }
}

@keyframes home-dash {
  to {
    stroke-dashoffset: -120;
  }
}

@keyframes home-ring {
  0%,
  100% {
    opacity: 0.12;
  }
  50% {
    opacity: 0.05;
  }
}

@media (prefers-reduced-motion: reduce) {
  .home-amb__mark--a,
  .home-amb__mark--b,
  .home-amb__stroke,
  .home-amb__ring--a,
  .home-amb__ring--b {
    animation: none;
  }
}
</style>
