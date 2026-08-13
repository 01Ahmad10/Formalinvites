<script setup lang="ts">
import InvitationDocument from './InvitationDocument.vue';
defineProps<{ invitation: any }>();
</script>
<template><InvitationDocument :invitation="invitation" variant="classic" /></template>
