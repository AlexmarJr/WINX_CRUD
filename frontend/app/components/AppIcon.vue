<script setup lang="ts">
import type { IconDefinition } from '@fortawesome/fontawesome-svg-core'
import {
  faArrowLeft,
  faArrowRight,
  faArrowUpRightFromSquare,
  faBoxesStacked,
  faChevronDown,
  faCommentDots,
  faCoins,
  faCopy,
  faEye,
  faExpand,
  faCompress,
  faPaperPlane,
  faGaugeHigh,
  faGear,
  faPenToSquare,
  faPlus,
  faRightFromBracket,
  faSort,
  faSortDown,
  faSortUp,
  faTags,
  faTrash,
  faTriangleExclamation,
  faUser,
  faUserPlus,
  faUsers,
  faXmark
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const icons = {
  arrowLeft: faArrowLeft,
  arrowRight: faArrowRight,
  arrowUpRight: faArrowUpRightFromSquare,
  products: faBoxesStacked,
  chevronDown: faChevronDown,
  message: faCommentDots,
  expand: faExpand,
  compress: faCompress,
  send: faPaperPlane,
  money: faCoins,
  copy: faCopy,
  eye: faEye,
  edit: faPenToSquare,
  dashboard: faGaugeHigh,
  settings: faGear,
  plus: faPlus,
  logout: faRightFromBracket,
  sort: faSort,
  sortDown: faSortDown,
  sortUp: faSortUp,
  categories: faTags,
  trash: faTrash,
  warning: faTriangleExclamation,
  user: faUser,
  users: faUsers,
  invite: faUserPlus,
  close: faXmark
} satisfies Record<string, IconDefinition>

defineProps<{ name: keyof typeof icons }>()
</script>

<template>
  <FontAwesomeIcon :icon="icons[name]" />
</template>
