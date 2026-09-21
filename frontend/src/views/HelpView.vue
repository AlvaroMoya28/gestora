<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import RichText from '@/components/help/RichText.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import { GENERAL_TOPICS, MODULE_TOPICS, findTopic, type HelpTopic } from '@/help/topics'
import { useTours } from '@/help/tours'
import { useAuthStore } from '@/stores/auth'

/**
 * Centro de ayuda: una página por módulo, escrita para quien casi no usa computadora.
 *
 * Solo lista los módulos que el usuario puede abrir, en el mismo orden del menú. Explicar
 * una pantalla que la persona no tiene solo confunde; la ayuda es sobre su sistema, no
 * sobre el catálogo completo de Gestora.
 */
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const { startWelcome, startModule, hasModuleTour } = useTours()

interface TopicGroup {
  name: string
  topics: HelpTopic[]
}

const groups = computed<TopicGroup[]>(() => {
  const result: TopicGroup[] = [{ name: 'Primeros pasos', topics: GENERAL_TOPICS }]
  for (const group of auth.menuGroups) {
    const topics = group.items
      .map((item) => MODULE_TOPICS.find((t) => t.key === item.key))
      .filter((t): t is HelpTopic => !!t)
    if (topics.length) result.push({ name: group.name, topics })
  }
  return result
})

const visibleKeys = computed(() => new Set(groups.value.flatMap((g) => g.topics.map((t) => t.key))))

// ----------------------------------------------------------------- Búsqueda ----

const search = ref('')

/** Sin tildes ni mayúsculas: quien escribe «credito» tiene que encontrar «crédito». */
const normalize = (text: string) =>
  text
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()

function searchableText(topic: HelpTopic) {
  return [
    topic.title,
    topic.summary,
    ...topic.purpose,
    ...topic.tasks.flatMap((t) => [t.title, ...t.steps, t.note ?? '']),
    ...(topic.fields ?? []).flatMap((f) => [f.name, f.description]),
    ...(topic.faq ?? []).flatMap((f) => [f.q, f.a]),
  ].join(' ')
}

const filteredGroups = computed(() => {
  const term = normalize(search.value.trim())
  if (!term) return groups.value
  return groups.value
    .map((g) => ({ ...g, topics: g.topics.filter((t) => normalize(searchableText(t)).includes(term)) }))
    .filter((g) => g.topics.length)
})

// ------------------------------------------------------------ Tema elegido ----

const selectedKey = computed(() => {
  const key = route.params.topic as string | undefined
  return key && visibleKeys.value.has(key) ? key : 'basics'
})

const topic = computed(() => findTopic(selectedKey.value)!)

const related = computed(() =>
  (topic.value.related ?? [])
    .filter((key) => visibleKeys.value.has(key))
    .map((key) => findTopic(key)!)
    .filter(Boolean),
)

const canShowOnScreen = computed(() => hasModuleTour(topic.value.key))

function open(key: string) {
  void router.push({ name: 'help', params: { topic: key } })
}

// Al cambiar de tema se vuelve arriba: el tema nuevo se lee desde el principio.
watch(selectedKey, () => window.scrollTo({ top: 0 }))
</script>

<template>
  <div>
    <PageHeader title="Ayuda" description="Cómo usar cada parte del sistema, explicado paso a paso." />

    <section v-if="!auth.isPlatform" class="welcome">
      <span class="welcome-icon"><AppIcon name="play" :size="22" /></span>
      <div>
        <h2>Recorrido de bienvenida</h2>
        <p class="muted">
          Le mostramos el sistema parte por parte, con la pantalla oscurecida y cada cosa iluminada
          mientras se la explicamos. Toma unos pocos minutos y lo puede dejar cuando quiera.
        </p>
      </div>
      <BaseButton variant="primary" @click="startWelcome(route.fullPath)">Iniciar recorrido</BaseButton>
    </section>

    <div class="layout">
      <aside class="index">
        <label class="search">
          <AppIcon name="search" :size="16" />
          <input v-model="search" type="search" placeholder="Buscar en la ayuda…" aria-label="Buscar en la ayuda" />
        </label>

        <nav aria-label="Temas de ayuda">
          <div v-for="group in filteredGroups" :key="group.name" class="index-group">
            <p class="index-label">{{ group.name }}</p>
            <button
              v-for="item in group.topics"
              :key="item.key"
              type="button"
              class="index-item"
              :class="{ active: item.key === selectedKey }"
              :aria-current="item.key === selectedKey ? 'page' : undefined"
              @click="open(item.key)"
            >
              <AppIcon :name="item.icon" :size="16" />
              <span>{{ item.title }}</span>
            </button>
          </div>
          <p v-if="!filteredGroups.length" class="no-results muted">
            No encontramos nada con «{{ search }}». Pruebe con otra palabra.
          </p>
        </nav>
      </aside>

      <article class="topic">
        <header class="topic-header">
          <span class="topic-icon"><AppIcon :name="topic.icon" :size="22" /></span>
          <div>
            <h2>{{ topic.title }}</h2>
            <p class="summary">{{ topic.summary }}</p>
          </div>
        </header>

        <div v-if="canShowOnScreen" class="show-me">
          <p>¿Prefiere verlo en la pantalla real? Le mostramos cada parte, una por una.</p>
          <BaseButton variant="primary" size="sm" @click="startModule(topic.key)">
            <AppIcon name="play" :size="16" />
            Mostrarme en pantalla
          </BaseButton>
        </div>

        <section>
          <h3>¿Para qué sirve?</h3>
          <div class="prose">
            <p v-for="(paragraph, i) in topic.purpose" :key="i"><RichText :text="paragraph" inline /></p>
          </div>
        </section>

        <section v-if="topic.tasks.length">
          <h3>Paso a paso</h3>
          <div v-for="task in topic.tasks" :key="task.title" class="task">
            <h4>{{ task.title }}</h4>
            <ol>
              <li v-for="(step, i) in task.steps" :key="i"><RichText :text="step" inline /></li>
            </ol>
            <p v-if="task.note" class="note"><RichText :text="task.note" inline /></p>
          </div>
        </section>

        <section v-if="topic.fields?.length">
          <h3>{{ topic.fieldsTitle ?? 'Qué significa cada campo' }}</h3>
          <dl class="terms">
            <template v-for="field in topic.fields" :key="field.name">
              <dt>{{ field.name }}</dt>
              <dd><RichText :text="field.description" inline /></dd>
            </template>
          </dl>
        </section>

        <section v-if="topic.statuses?.length">
          <h3>Estados</h3>
          <dl class="terms">
            <template v-for="status in topic.statuses" :key="status.name">
              <dt>{{ status.name }}</dt>
              <dd><RichText :text="status.description" inline /></dd>
            </template>
          </dl>
        </section>

        <section v-if="topic.cautions?.length" class="cautions">
          <h3>
            <AppIcon name="alert" :size="17" />
            Tenga en cuenta
          </h3>
          <ul>
            <li v-for="(caution, i) in topic.cautions" :key="i"><RichText :text="caution" inline /></li>
          </ul>
        </section>

        <section v-if="topic.faq?.length">
          <h3>Preguntas frecuentes</h3>
          <details v-for="item in topic.faq" :key="item.q" class="faq">
            <summary>{{ item.q }}</summary>
            <p><RichText :text="item.a" inline /></p>
          </details>
        </section>

        <section v-if="related.length">
          <h3>Relacionado</h3>
          <div class="related">
            <button v-for="item in related" :key="item.key" type="button" @click="open(item.key)">
              <AppIcon :name="item.icon" :size="16" />
              {{ item.title }}
              <AppIcon name="chevron-right" :size="14" />
            </button>
          </div>
        </section>
      </article>
    </div>
  </div>
</template>

<style scoped>
/* ------------------------------------------------------------- Bienvenida ---- */

.welcome {
  display: flex;
  align-items: center;
  gap: 16px;
  background: linear-gradient(120deg, var(--brand-50), var(--surface) 70%);
  border: 1px solid var(--brand-100);
  border-radius: var(--radius-lg);
  padding: 18px 20px;
  margin-bottom: 20px;
}

.welcome-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: var(--brand-500);
  color: #fff;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.welcome h2 {
  font-size: 15.5px;
  font-weight: 650;
}

.welcome p {
  font-size: 13.5px;
  margin-top: 2px;
  max-width: 62ch;
}

.welcome > :last-child {
  margin-left: auto;
  flex-shrink: 0;
}

/* ----------------------------------------------------------------- Diseño ---- */

.layout {
  display: grid;
  grid-template-columns: 250px minmax(0, 1fr);
  gap: 20px;
  align-items: start;
}

/* ------------------------------------------------------------------ Índice ---- */

.index {
  position: sticky;
  top: calc(var(--header-height) + 16px);
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius-lg);
  padding: 12px;
  max-height: calc(100vh - var(--header-height) - 32px);
  overflow-y: auto;
}

.search {
  display: flex;
  align-items: center;
  gap: 7px;
  border: 1px solid var(--ink-200);
  border-radius: var(--radius-sm);
  padding: 0 9px;
  color: var(--ink-400);
  margin-bottom: 10px;
}

.search:focus-within {
  border-color: var(--brand-500);
  box-shadow: 0 0 0 3px var(--brand-50);
}

.search input {
  border: none;
  outline: none;
  background: none;
  font: inherit;
  font-size: 13.5px;
  padding: 8px 0;
  width: 100%;
  color: var(--ink-900);
}

.index-group + .index-group {
  margin-top: 10px;
}

.index-label {
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--ink-400);
  padding: 4px 8px;
}

.index-item {
  display: flex;
  align-items: center;
  gap: 9px;
  width: 100%;
  border: none;
  background: none;
  text-align: left;
  font: inherit;
  font-size: 13.5px;
  color: var(--ink-700);
  padding: 7px 8px;
  border-radius: var(--radius-sm);
  cursor: pointer;
}

.index-item:hover {
  background: var(--ink-100);
}

.index-item.active {
  background: var(--brand-50);
  color: var(--brand-700);
  font-weight: 600;
}

.no-results {
  font-size: 13px;
  padding: 8px;
}

/* ------------------------------------------------------------------- Tema ---- */

.topic {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius-lg);
  padding: 26px 30px 30px;
  min-width: 0;
}

.topic-header {
  display: flex;
  gap: 14px;
  align-items: flex-start;
}

.topic-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: var(--brand-50);
  color: var(--brand-600);
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.topic-header h2 {
  font-size: 22px;
  font-weight: 650;
  letter-spacing: -0.01em;
}

.summary {
  font-size: 15px;
  color: var(--ink-700);
  margin-top: 4px;
  max-width: 68ch;
}

.show-me {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  background: var(--ink-100);
  border-radius: var(--radius);
  padding: 11px 14px;
  margin-top: 18px;
  font-size: 13.5px;
}

.topic section {
  margin-top: 28px;
}

.topic h3 {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 16px;
  font-weight: 650;
  color: var(--ink-900);
  padding-bottom: 8px;
  border-bottom: 1px solid var(--ink-200);
  margin-bottom: 12px;
}

.prose p,
.task li,
.terms dd,
.faq p,
.cautions li {
  font-size: 15px;
  line-height: 1.65;
  color: var(--ink-700);
  max-width: 72ch;
}

.prose p + p {
  margin-top: 10px;
}

.topic :deep(strong) {
  color: var(--ink-900);
  font-weight: 650;
}

/* Pasos numerados en círculos grandes: se siguen con el dedo en la pantalla. */
.task + .task {
  margin-top: 22px;
}

.task h4 {
  font-size: 15px;
  font-weight: 650;
  color: var(--ink-900);
  margin-bottom: 10px;
}

.task ol {
  list-style: none;
  counter-reset: step;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 9px;
}

.task li {
  counter-increment: step;
  position: relative;
  padding-left: 38px;
  min-height: 26px;
}

.task li::before {
  content: counter(step);
  position: absolute;
  left: 0;
  top: 0;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: var(--brand-500);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  display: grid;
  place-items: center;
}

.note {
  margin-top: 12px;
  background: var(--brand-50);
  border-left: 3px solid var(--brand-500);
  border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
  padding: 10px 14px;
  font-size: 14px;
  line-height: 1.6;
  color: var(--ink-700);
  max-width: 72ch;
}

.terms {
  display: grid;
  grid-template-columns: minmax(150px, 220px) 1fr;
  gap: 10px 20px;
  margin: 0;
}

.terms dt {
  font-weight: 650;
  font-size: 14.5px;
  color: var(--ink-900);
  line-height: 1.65;
}

.terms dd {
  margin: 0;
}

.cautions {
  background: var(--accent-100);
  border: 1px solid color-mix(in srgb, var(--accent-500) 25%, transparent);
  border-radius: var(--radius);
  padding: 14px 18px 16px;
}

.cautions h3 {
  color: var(--accent-600);
  border-bottom-color: color-mix(in srgb, var(--accent-500) 25%, transparent);
}

.cautions ul {
  margin: 0;
  padding-left: 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.faq {
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  padding: 0 14px;
}

.faq + .faq {
  margin-top: 8px;
}

.faq summary {
  cursor: pointer;
  font-weight: 600;
  font-size: 14.5px;
  color: var(--ink-900);
  padding: 12px 0;
}

.faq p {
  padding-bottom: 12px;
}

.related {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.related button {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  border: 1px solid var(--ink-200);
  background: var(--surface);
  border-radius: 999px;
  padding: 6px 12px;
  font: inherit;
  font-size: 13.5px;
  color: var(--ink-700);
  cursor: pointer;
}

.related button:hover {
  border-color: var(--brand-200);
  background: var(--brand-50);
  color: var(--brand-700);
}

@media (max-width: 900px) {
  .layout {
    grid-template-columns: 1fr;
  }

  .index {
    position: static;
    max-height: none;
  }

  .welcome {
    flex-wrap: wrap;
  }

  .welcome > :last-child {
    margin-left: 0;
  }

  .topic {
    padding: 20px 18px 24px;
  }

  .terms {
    grid-template-columns: 1fr;
    gap: 2px;
  }

  .terms dd {
    margin-bottom: 10px;
  }
}
</style>
