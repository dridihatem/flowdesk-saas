<script setup>
defineProps({
    appName: {
        type: String,
        default: 'Flowdesk',
    },
});
</script>

<template>
    <div
        class="rounded-xl border border-[var(--flow-border)] bg-[var(--flow-surface-muted)] px-4 py-3 text-sm text-[var(--flow-text-muted)]"
    >
        <span class="font-medium text-[var(--flow-text)]">{{ appName }}</span>
        —
        <span>Vue island loaded (widgets &amp; dashboards can mount here).</span>
    </div>
</template>
