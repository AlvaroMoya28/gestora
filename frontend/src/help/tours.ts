import { useAuthStore } from '@/stores/auth'
import { useTourStore, type TourStep } from '@/stores/tour'
import { findTopic, TARGET, type ModuleTourStep } from './topics'

/**
 * Arma los recorridos a partir del contenido de la Ayuda y de los permisos del usuario.
 * Nada está escrito dos veces: el recorrido de bienvenida usa el resumen de cada tema
 * y el de cada pantalla usa los pasos que el tema declara.
 */

interface ModuleAccess {
  key: string
  name: string
  canWrite: boolean
}

const READ_ONLY_NOTE = 'Su usuario puede consultar esta parte, pero no modificarla.'

function toStep(step: ModuleTourStep, route: string, canWrite: boolean): TourStep {
  const { write: _write, readOnlyBody, ...rest } = step
  return { ...rest, route, body: !canWrite && readOnlyBody ? readOnlyBody : step.body }
}

/**
 * Recorrido de bienvenida: el menú de arriba abajo, entrando a cada módulo que el
 * usuario puede abrir. En el Panel se detiene a explicar sus partes, porque es la
 * pantalla a la que vuelve siempre. Termina mostrando dónde pedir ayuda después.
 */
export function buildWelcomeTour(firstName: string, modules: ModuleAccess[]): TourStep[] {
  const steps: TourStep[] = [
    {
      route: 'dashboard',
      placement: 'center',
      title: firstName ? `Le damos la bienvenida, ${firstName}` : 'Le damos la bienvenida',
      body:
        'Le vamos a mostrar el sistema, parte por parte. La pantalla se oscurece y queda iluminado lo que le estamos explicando.\n\n' +
        'Avance con **Siguiente**. Puede salir cuando quiera con **Saltar recorrido** y repetirlo después desde **Ayuda**, al final del menú.',
    },
    {
      route: 'dashboard',
      target: TARGET.brand,
      placement: 'right',
      title: 'Su empresa',
      body: 'Aquí arriba siempre aparece el nombre de su empresa. Todo lo que registre queda guardado a nombre de ella.',
    },
    {
      route: 'dashboard',
      target: TARGET.menu,
      placement: 'right',
      title: 'El menú',
      body:
        'Todo el sistema está en este menú, ordenado por grupos. Haga un clic en un nombre para abrir esa parte; la que tiene abierta se marca en verde.\n\n' +
        'Ahora lo vamos a recorrer de arriba abajo.',
    },
  ]

  for (const module of modules) {
    const topic = findTopic(module.key)
    if (!topic) continue

    steps.push({
      route: module.key,
      target: TARGET.menuItem(module.key),
      placement: 'right',
      title: module.name,
      body: module.canWrite ? topic.summary : `${topic.summary}\n\n${READ_ONLY_NOTE}`,
    })

    // El Panel es la pantalla de inicio: vale la pena mostrar sus partes ahí mismo.
    if (module.key === 'dashboard') {
      for (const step of topic.tour ?? []) {
        if (step.target === TARGET.pageTitle) continue
        steps.push(toStep(step, 'dashboard', module.canWrite))
      }
    }
  }

  steps.push(
    {
      route: 'dashboard',
      target: TARGET.pageHelp,
      placement: 'bottom',
      title: '¿Cómo se usa?',
      body: 'Cada pantalla tiene este botón junto al título. Si no sabe cómo usarla, presiónelo: le mostramos sus partes una por una, igual que ahora.',
    },
    {
      route: 'dashboard',
      target: TARGET.help,
      placement: 'right',
      title: 'Ayuda',
      body: 'Aquí está la guía completa: cada parte del sistema explicada paso a paso, con preguntas frecuentes. Desde aquí también puede repetir este recorrido.',
    },
    {
      route: 'dashboard',
      target: TARGET.profile,
      placement: 'bottom',
      title: 'Su cuenta',
      body: 'Haga clic en su nombre para cambiar su contraseña o cerrar sesión. Cierre sesión siempre que termine, sobre todo en una computadora que usan otras personas.',
    },
    {
      route: 'dashboard',
      placement: 'center',
      title: 'Listo',
      body:
        'Eso es todo: ya conoce el sistema.\n\n' +
        'Si en algún momento no sabe cómo hacer algo, use el botón **¿Cómo se usa?** de la pantalla en que esté, o abra **Ayuda** al final del menú.',
    },
  )

  return steps
}

/** Recorrido de una sola pantalla. A quien solo consulta no se le muestran los botones que no tiene. */
export function buildModuleTour(key: string, canWrite: boolean): TourStep[] {
  const topic = findTopic(key)
  if (!topic?.tour?.length) return []

  const steps = topic.tour.filter((step) => canWrite || !step.write).map((step) => toStep(step, key, canWrite))

  if (!canWrite) {
    steps.unshift({
      route: key,
      placement: 'center',
      title: 'Usted puede consultar esta pantalla',
      body: 'Su usuario es de **Consulta**: puede ver todo lo que hay aquí, pero no crear ni modificar. Por eso algunos botones que menciona la Ayuda no le aparecen.',
    })
  }

  return steps
}

/** Punto de entrada para las vistas: arranca recorridos con los permisos de la sesión. */
export function useTours() {
  const auth = useAuthStore()
  const tour = useTourStore()

  function startWelcome(origin: string | null = null) {
    const modules = auth.modules
      .filter((m) => m.available)
      .map((m) => ({ key: m.key, name: m.name, canWrite: m.canWrite }))
    tour.start(buildWelcomeTour(auth.user?.firstName ?? '', modules), 'welcome', origin)
  }

  function startModule(key: string) {
    tour.start(buildModuleTour(key, auth.canWrite(key)), 'module')
  }

  function hasModuleTour(key: string) {
    return auth.canRead(key) && !!findTopic(key)?.tour?.length
  }

  return { startWelcome, startModule, hasModuleTour }
}
