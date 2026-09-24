<script setup lang="ts">
import type { IconDefinition } from '@fortawesome/fontawesome-svg-core'
import {
  faArrowLeft,
  faArrowRight,
  faArrowUpRightFromSquare,
  faBoxesStacked,
  faChevronDown,
  faCoins,
  faEye,
  faGaugeHigh,
  faGear,
  faPlus,
  faRightFromBracket,
  faTags,
  faTrash,
  faTriangleExclamation,
  faUser,
  faUserPlus,
  faXmark
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const icons = {
  arrowLeft: faArrowLeft,
  arrowRight: faArrowRight,
  arrowUpRight: faArrowUpRightFromSquare,
  products: faBoxesStacked,
  chevronDown: faChevronDown,
  money: faCoins,
  eye: faEye,
  dashboard: faGaugeHigh,
  settings: faGear,
  plus: faPlus,
  logout: faRightFromBracket,
  categories: faTags,
  trash: faTrash,
  warning: faTriangleExclamation,
  user: faUser,
  invite: faUserPlus,
  close: faXmark
} satisfies Record<string, IconDefinition>

defineProps<{ name: keyof typeof icons }>()
</script>

<template>
  <FontAwesomeIcon :icon="icons[name]" />
</template>
