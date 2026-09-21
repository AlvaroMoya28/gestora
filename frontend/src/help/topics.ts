import type { TourStep } from '@/stores/tour'

/**
 * Contenido de la Ayuda y de los recorridos "¿Cómo se usa?".
 *
 * Está escrito para alguien que casi no usa computadora: se nombra cada botón y cada
 * campo tal como aparece en pantalla, se dice dónde está y qué pasa al presionarlo.
 * Si cambia el texto de un botón en una vista, hay que cambiarlo también aquí.
 *
 * Los ejemplos son genéricos a propósito: este es el contenido del producto, no el de
 * una empresa en particular, y lo leen negocios de cualquier rubro.
 */

/** Lugares de la pantalla que un recorrido puede iluminar. */
export const TARGET = {
  brand: '[data-tour="brand"]',
  menu: '[data-tour="menu"]',
  menuItem: (key: string) => `[data-tour="menu-${key}"]`,
  help: '[data-tour="menu-help"]',
  profile: '[data-tour="profile"]',
  pageTitle: '[data-tour="page-title"]',
  pageActions: '[data-tour="page-actions"]',
  pageHelp: '[data-tour="page-help"]',
  /** Todas las vistas de listado comparten esta barra de búsqueda y filtros. */
  filters: '.toolbar',
  table: '.table-wrap',
} as const

export interface HelpTask {
  title: string
  steps: string[]
  note?: string
}

export interface HelpTerm {
  name: string
  description: string
}

export interface HelpQuestion {
  q: string
  a: string
}

/** Paso del recorrido de una pantalla. */
export interface ModuleTourStep extends Omit<TourStep, 'route'> {
  /** Solo se muestra a quien puede crear y modificar en el módulo. */
  write?: boolean
  /** Texto alternativo para quien solo puede consultar. */
  readOnlyBody?: string
}

export interface HelpTopic {
  /** Clave del módulo en el catálogo, o un tema general. */
  key: string
  title: string
  icon: string
  /** Una o dos frases. Es lo que dice el recorrido de bienvenida sobre el módulo. */
  summary: string
  purpose: string[]
  tasks: HelpTask[]
  fieldsTitle?: string
  fields?: HelpTerm[]
  statuses?: HelpTerm[]
  cautions?: string[]
  faq?: HelpQuestion[]
  related?: string[]
  tour?: ModuleTourStep[]
}

// ==================================================================== Temas ====

/** Temas que no son un módulo: se muestran a todos, primero en el índice. */
export const GENERAL_TOPICS: HelpTopic[] = [
  {
    key: 'basics',
    title: 'Cómo usar Gestora',
    icon: 'book',
    summary: 'Lo básico para moverse en el sistema: el menú, los botones, los buscadores y los formularios.',
    purpose: [
      'Esta guía es para quien nunca ha usado Gestora o usa poco la computadora. No hace falta saber nada de antes: vamos paso a paso.',
      'Gestora es el sistema donde su empresa anota todo lo que pasa: lo que compra, lo que vende, lo que fabrica, lo que le deben y lo que debe. Todo queda guardado y conectado. Por ejemplo, cuando confirma una venta, el inventario baja y la cuenta por cobrar aparece sola: no hay que anotarlo en tres lugares.',
    ],
    tasks: [
      {
        title: 'Entrar al sistema',
        steps: [
          'Abra el navegador de internet (Chrome, Edge o el que use normalmente) y entre a la dirección de Gestora.',
          'Escriba su **correo electrónico** en el primer espacio.',
          'Escriba su **contraseña** en el segundo espacio. Mientras escribe se ven puntitos en lugar de letras: es normal, es para que nadie la lea. Si quiere revisar lo que escribió, haga clic en **Mostrar**.',
          'Haga clic en el botón verde **Entrar**.',
        ],
        note: 'Si le sale «Correo o contraseña incorrectos», revise que no tenga activadas las mayúsculas (la tecla Bloq Mayús) y vuelva a intentarlo. Después de varios intentos fallidos la cuenta se bloquea unos minutos, por seguridad.',
      },
      {
        title: 'Moverse entre pantallas',
        steps: [
          'A la izquierda está el **menú**. Cada renglón es una parte del sistema: Clientes, Ventas, Inventario y las demás.',
          'Haga un clic sobre el nombre de la parte que quiere abrir. La pantalla cambia y ese renglón queda marcado en verde, para que sepa dónde está.',
          'Arriba, en la barra blanca, siempre aparece el nombre de la pantalla en la que está.',
          'En el celular o en una pantalla pequeña el menú está escondido: toque el botón de tres rayitas (☰), arriba a la izquierda, para abrirlo.',
        ],
      },
      {
        title: 'Buscar algo en una lista',
        steps: [
          'Casi todas las pantallas muestran una lista en forma de tabla. Arriba de la tabla hay un espacio para buscar.',
          'Haga clic en ese espacio y escriba parte de lo que busca: un nombre, un código o un número. No hace falta escribirlo completo ni presionar Enter: la lista se filtra sola mientras escribe.',
          'Al lado del buscador hay listas desplegables, llamadas **filtros**, para ver por ejemplo solo lo activo o solo lo vencido. Haga clic en una y elija la opción.',
          'Para volver a ver todo, borre lo que escribió y deje los filtros en «Todos».',
        ],
      },
      {
        title: 'Ver el resto de una lista larga',
        steps: [
          'Cuando una lista es muy larga, se muestra por partes. Debajo de la tabla dice, por ejemplo, «1–20 de 57»: está viendo los primeros 20 de 57.',
          'Use los botones **Siguiente** y **Anterior**, debajo de la tabla a la derecha, para ver las demás partes.',
        ],
      },
      {
        title: 'Llenar un formulario y guardar',
        steps: [
          'Para registrar algo nuevo, busque el botón verde de arriba a la derecha, por ejemplo **Nuevo cliente**. Se abre una ventana con espacios para llenar.',
          'Los espacios marcados con un asterisco (*) son obligatorios. Los demás puede dejarlos vacíos.',
          'Haga clic en cada espacio y escriba. Para pasar al siguiente también puede usar la tecla Tab.',
          'Cuando termine, haga clic en el botón verde de abajo de la ventana, por ejemplo **Registrar cliente**.',
          'Si falta algo o hay un error, el espacio se marca en rojo con un mensaje que explica qué corregir. Corrija y vuelva a guardar.',
          'Si todo salió bien, abajo a la derecha de la pantalla aparece un aviso de confirmación y la ventana se cierra.',
        ],
        note: 'Si se arrepiente, haga clic en **Cancelar** o en la X de la esquina de la ventana. Se cierra sin guardar nada.',
      },
      {
        title: 'Cambiar su contraseña y cerrar sesión',
        steps: [
          'Arriba a la derecha está su nombre. Haga clic ahí y se abre un menú pequeño.',
          '**Mi cuenta** le muestra sus datos y le permite cambiar la contraseña: escriba la actual, después la nueva dos veces, y haga clic en **Cambiar contraseña**. La nueva debe tener al menos 8 caracteres.',
          '**Cerrar sesión** lo saca del sistema. Hágalo siempre que termine, sobre todo en una computadora que usan otras personas.',
        ],
      },
    ],
    cautions: [
      'Casi nada se borra en Gestora. Lo que tiene que ver con dinero o con inventario queda en el historial; si se equivoca, se corrige con otro registro. Así siempre se puede saber qué pasó y quién lo hizo.',
      'Todo lo importante queda anotado en la Auditoría, con su nombre y la hora. No comparta su contraseña: lo que se haga con su usuario aparece a su nombre.',
    ],
    faq: [
      {
        q: '¿Qué hago si no me aparece un botón del que habla la ayuda?',
        a: 'Probablemente su usuario es de **Consulta**: puede ver todo, pero no crear ni modificar. Si necesita hacerlo, pídale al administrador de su empresa que le cambie el rol.',
      },
      {
        q: '¿Se guarda solo lo que escribo?',
        a: 'No. Lo que escribe en un formulario se guarda cuando hace clic en el botón de guardar. Si cierra la ventana antes, se pierde.',
      },
      {
        q: '¿Puedo usar Gestora desde el celular?',
        a: 'Sí. El menú se abre con el botón ☰ de arriba a la izquierda. Para trabajos largos, como registrar una compra con muchos productos, es más cómodo en una computadora.',
      },
      {
        q: 'Me perdí. ¿Cómo vuelvo al inicio?',
        a: 'Haga clic en **Panel**, el primer renglón del menú. Es la pantalla de inicio.',
      },
    ],
  },
  {
    key: 'glossary',
    title: 'Palabras que usa el sistema',
    icon: 'book',
    summary: 'Qué significa cada palabra que usa Gestora, explicado sin términos de contabilidad.',
    purpose: ['Si una palabra del sistema no le suena, búsquela aquí.'],
    tasks: [],
    fieldsTitle: 'Palabras',
    fields: [
      { name: 'Borrador', description: 'Un documento que se está preparando y todavía no cuenta. Se puede corregir o cancelar sin consecuencias.' },
      { name: 'Confirmar', description: 'Dar por hecho un documento. Al confirmar, el sistema mueve el inventario y crea la cuenta por cobrar o por pagar. No se puede deshacer.' },
      { name: 'Contado', description: 'Se paga el mismo día.' },
      { name: 'Crédito', description: 'Se paga después, en el plazo acordado.' },
      { name: 'Vencimiento', description: 'La fecha límite para pagar. Pasada esa fecha, la cuenta está vencida.' },
      { name: 'Saldo', description: 'Lo que falta por pagar o por cobrar de una cuenta.' },
      { name: 'Cuenta por cobrar', description: 'Dinero que un cliente le debe a su empresa.' },
      { name: 'Cuenta por pagar', description: 'Dinero que su empresa le debe a un proveedor.' },
      { name: 'Existencia', description: 'Cuántas unidades hay de un producto en este momento.' },
      { name: 'Existencia mínima', description: 'La cantidad por debajo de la cual conviene reponer. Cuando un producto llega ahí, el Panel lo avisa.' },
      { name: 'Costo', description: 'Lo que le cuesta a su empresa una unidad: lo que pagó al comprarla o lo que costó fabricarla.' },
      { name: 'Precio', description: 'Lo que su empresa cobra por una unidad al venderla.' },
      { name: 'Materia prima', description: 'Lo que se compra para fabricar, como madera, tela o tornillos.' },
      { name: 'Producto terminado', description: 'Lo que se fabrica o se compra para vender.' },
      { name: 'Servicio', description: 'Algo que se cobra pero no se guarda en bodega, como un trabajo o una instalación. No lleva inventario.' },
      { name: 'Receta', description: 'La lista de materiales que lleva fabricar un producto.' },
      { name: 'Rendimiento', description: 'Cuántas unidades salen con las cantidades de una receta.' },
      { name: 'Movimiento de inventario', description: 'Cada entrada o salida de producto, con su motivo, su fecha y quién la hizo.' },
      { name: 'Ajuste', description: 'Una corrección del inventario cuando lo que hay en bodega no coincide con lo que dice el sistema.' },
      { name: 'Flujo de caja', description: 'La plata que entró menos la plata que salió. No es la ganancia: un mes puede salir más de lo que entra por haber comprado material que se venderá después.' },
      { name: 'Inactivar', description: 'Ocultar algo que ya no se usa sin borrarlo, para que su historial no se pierda. Se puede volver a activar.' },
      { name: 'Rol', description: 'El tipo de permiso de un usuario. **Administrador** puede hacer todo; **Consulta** solo puede ver.' },
      { name: 'Auditoría', description: 'El registro de quién hizo qué y cuándo.' },
      { name: 'CSV', description: 'Un tipo de archivo que se abre con Excel.' },
    ],
  },
]

/** Un tema por módulo. El orden del índice lo da el menú, no esta lista. */
export const MODULE_TOPICS: HelpTopic[] = [
  // ------------------------------------------------------------------ Panel ----
  {
    key: 'dashboard',
    title: 'Panel',
    icon: 'gauge',
    summary: 'La pantalla de inicio. Resume cómo está la empresa hoy: lo que se vendió este mes, lo que le deben, lo que debe y lo que necesita atención.',
    purpose: [
      'El Panel es lo primero que ve al entrar. Aquí no se escribe nada: es una pantalla para mirar. Junta en un solo lugar los números más importantes de su empresa y le avisa de lo que requiere atención.',
      'Los números se calculan en el momento en que abre el Panel. Si alguien registra algo mientras usted lo está mirando, vuelva a abrir el Panel desde el menú para ver el cambio.',
    ],
    tasks: [
      {
        title: 'Revisar cómo va la empresa',
        steps: [
          'Haga clic en **Panel**, el primer renglón del menú.',
          'Mire los cuadros de arriba. Cada uno tiene un nombre, un número grande y, a veces, una nota pequeña debajo que da más detalle.',
          'Si debajo de los cuadros aparece un aviso, léalo: son cosas que necesitan una decisión. Haga clic en **Ver** para ir directo a la pantalla donde se resuelve.',
        ],
      },
      {
        title: 'Saber qué productos hay que reponer',
        steps: [
          'Busque el recuadro **Productos por reponer**. Ahí están los productos que llegaron a su existencia mínima.',
          'Si dice «Todas las existencias están sobre su nivel mínimo», no hay nada que reponer por ahora.',
        ],
      },
      {
        title: 'Ver qué se ha hecho últimamente',
        steps: [
          'El recuadro **Actividad reciente** muestra lo último que se registró en el sistema: quién, qué y cuándo.',
          'Para ver el historial completo, haga clic en **Auditoría**, en ese mismo recuadro.',
        ],
      },
    ],
    fieldsTitle: 'Qué significa cada cuadro',
    fields: [
      { name: 'Ventas del mes', description: 'La suma de las ventas **confirmadas** desde el día 1 del mes. Las ventas en borrador no cuentan.' },
      { name: 'Por cobrar', description: 'Todo lo que los clientes le deben hoy. Si una parte ya venció, la nota de abajo dice cuánto.' },
      { name: 'Por pagar', description: 'Todo lo que su empresa les debe a sus proveedores. La nota avisa cuántos documentos ya vencieron.' },
      { name: 'Flujo de caja del mes', description: 'La plata que entró este mes menos la que salió. **No es la ganancia**: puede salir negativo un mes en que se compró mucho material que todavía no se vende, sin que la empresa esté perdiendo.' },
      { name: 'Valor del inventario', description: 'Cuánto vale todo lo que hay en bodega, calculado con el costo de cada producto.' },
      { name: 'Reparaciones abiertas', description: 'Trabajos de reparación que todavía no se entregan. La nota avisa si alguno pasó la fecha prometida al cliente.' },
      { name: 'Producción en curso', description: 'Órdenes de fabricación planificadas o en proceso.' },
      { name: 'Productos bajo mínimo', description: 'Cuántos productos llegaron a su existencia mínima. La nota avisa si alguno se agotó.' },
    ],
    faq: [
      {
        q: '¿Por qué el flujo de caja sale negativo si vendimos bien?',
        a: 'Porque cuenta la plata que entró y salió, no las ventas. Una venta a crédito todavía no ha traído plata, y una compra que ya se pagó sí la sacó. Cuando los clientes paguen, el número sube.',
      },
      {
        q: '¿Por qué no aparece una venta que acabo de hacer?',
        a: 'Revise que la haya **confirmado**: las ventas en borrador no cuentan. Si ya la confirmó, vuelva a abrir el Panel desde el menú.',
      },
    ],
    related: ['sales', 'receivables', 'payables', 'inventory'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'El Panel',
        body: 'Esta es la pantalla de inicio. Aquí no se registra nada: se mira cómo está la empresa hoy.',
        placement: 'bottom',
      },
      {
        target: '[data-tour="dashboard-metrics"]',
        title: 'Los números del día',
        body: 'Cada cuadro resume una parte de la empresa: ventas del mes, lo que le deben, lo que debe, el flujo de caja, el valor del inventario y el trabajo pendiente.\n\nLa nota pequeña debajo de cada número da el detalle: por ejemplo, cuánto de lo que le deben ya está vencido.',
        placement: 'bottom',
      },
      {
        target: '[data-tour="dashboard-alerts"]',
        title: 'Avisos',
        body: 'Cuando algo necesita atención —una cuenta vencida, una reparación atrasada, un producto agotado— aparece aquí. Haga clic en **Ver** para ir directo a resolverlo.',
        optional: true,
      },
      {
        target: '[data-tour="dashboard-lowstock"]',
        title: 'Productos por reponer',
        body: 'Aquí salen los productos que llegaron a su existencia mínima. Es la lista de lo que conviene comprar o fabricar pronto.',
        optional: true,
      },
      {
        target: '[data-tour="dashboard-activity"]',
        title: 'Actividad reciente',
        body: 'Lo último que se hizo en el sistema, con el nombre de quien lo hizo y la hora.',
        optional: true,
      },
    ],
  },

  // --------------------------------------------------------------- Clientes ----
  {
    key: 'customers',
    title: 'Clientes',
    icon: 'users',
    summary: 'Las empresas y personas a las que su empresa les vende. Cada venta, reparación y cuenta por cobrar se hace a nombre de un cliente de esta lista.',
    purpose: [
      'Aquí se guarda la información de cada cliente: su nombre, cómo contactarlo y en qué condiciones se le vende, de contado o a crédito.',
      'Registrar bien al cliente ahorra trabajo después: cuando haga una venta y lo elija, el sistema propone solo su condición de pago.',
    ],
    tasks: [
      {
        title: 'Registrar un cliente nuevo',
        steps: [
          'Haga clic en el botón verde **Nuevo cliente**, arriba a la derecha.',
          'Escriba el **Nombre o razón social**. Es el único dato obligatorio.',
          'Si quiere, escriba un **Código**. Si lo deja vacío, el sistema le asigna uno solo.',
          'Complete lo que tenga a mano: cédula, teléfono, correo, persona de contacto y dirección.',
          'En **Condición de pago** elija **Contado** si el cliente paga el mismo día, o **Crédito** si le da tiempo para pagar. Si elige crédito, escriba en **Días de crédito** cuántos días le da.',
          'Haga clic en **Registrar cliente**.',
        ],
      },
      {
        title: 'Corregir los datos de un cliente',
        steps: [
          'Busque al cliente: escriba su nombre en el buscador.',
          'Haga clic en **Editar**, en el renglón del cliente.',
          'Cambie lo que necesite y haga clic en **Guardar cambios**.',
        ],
      },
      {
        title: 'Dejar de usar un cliente',
        steps: [
          'Busque al cliente y haga clic en **Inactivar**, en su renglón.',
          'Confirme en la ventana que aparece.',
        ],
        note: 'Un cliente inactivo no se borra: sus ventas y cuentas siguen en el historial. Solo deja de aparecer al hacer ventas nuevas. Para volver a usarlo, elija **Inactivos** en el filtro y haga clic en **Activar**.',
      },
    ],
    fields: [
      { name: 'Código', description: 'Un identificador corto del cliente. Si lo deja vacío, se asigna solo.' },
      { name: 'Cédula jurídica o física', description: 'Opcional. Sirve para facturar y para distinguir a dos clientes con el mismo nombre.' },
      { name: 'Nombre o razón social', description: 'Obligatorio. El nombre legal del cliente.' },
      { name: 'Nombre comercial', description: 'El nombre con que se le conoce, si es distinto del legal.' },
      { name: 'Persona de contacto', description: 'Con quién habla usted en esa empresa.' },
      { name: 'Condición de pago', description: '**Contado** o **Crédito**. Es la condición que se propone al venderle; en cada venta se puede cambiar.' },
      { name: 'Días de crédito', description: 'Cuántos días tiene el cliente para pagar cuando se le vende a crédito.' },
      { name: 'Límite de crédito', description: 'Hasta cuánto se le fía. Es una referencia para usted: el sistema la guarda, pero no impide una venta que la supere.' },
    ],
    statuses: [
      { name: 'Activo', description: 'Aparece al hacer ventas y reparaciones.' },
      { name: 'Inactivo', description: 'Ya no aparece para ventas nuevas, pero su historial se conserva.' },
    ],
    faq: [
      {
        q: '¿Puedo borrar un cliente?',
        a: 'No. Se inactiva, para que no se pierdan sus ventas ni sus cuentas. Un cliente inactivo no estorba: no aparece al hacer ventas nuevas.',
      },
      {
        q: '¿Dónde veo cuánto me debe un cliente?',
        a: 'En **Cuentas por cobrar**: escriba su nombre en el buscador.',
      },
    ],
    related: ['sales', 'receivables', 'repairs'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Clientes',
        body: 'Aquí están todos los clientes de la empresa. Cada venta y cada reparación se hace a nombre de uno de ellos.',
        placement: 'bottom',
      },
      {
        target: TARGET.pageActions,
        title: 'Registrar un cliente',
        body: 'Con el botón **Nuevo cliente** registra uno nuevo. Solo el nombre es obligatorio; lo demás lo puede completar después.',
        placement: 'bottom',
        write: true,
      },
      {
        target: TARGET.filters,
        title: 'Buscar y filtrar',
        body: 'Escriba aquí el nombre, el código o la cédula y la lista se filtra sola. Con la lista de al lado puede ver los clientes activos, los inactivos o todos.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'La lista de clientes',
        body: 'Cada renglón es un cliente. A la derecha de cada uno está **Editar**, para corregir sus datos, e **Inactivar**, para cuando ya no le vende.',
        readOnlyBody: 'Cada renglón es un cliente. Con el botón **Ver** de cada renglón abre todos sus datos.',
      },
    ],
  },

  // ------------------------------------------------------------ Proveedores ----
  {
    key: 'suppliers',
    title: 'Proveedores',
    icon: 'truck',
    summary: 'Quienes le venden a su empresa materiales, insumos y servicios. Cada compra se hace a nombre de un proveedor de esta lista.',
    purpose: [
      'Aquí se guarda la información de cada proveedor: su nombre, cómo contactarlo y en qué condiciones le vende a su empresa.',
      'Al registrar una compra y elegir el proveedor, el sistema propone solo el plazo de pago que tiene anotado aquí.',
    ],
    tasks: [
      {
        title: 'Registrar un proveedor nuevo',
        steps: [
          'Haga clic en **Nuevo proveedor**, arriba a la derecha.',
          'Escriba el **Nombre o razón social**. Es el único dato obligatorio.',
          'Si quiere, escriba un **Código**. Si lo deja vacío, se asigna solo.',
          'Complete cédula, teléfono, correo, persona de contacto y dirección.',
          'En **Condición de pago** elija **Contado** o **Crédito**. Si le da crédito, escriba cuántos **Días de crédito**.',
          'Haga clic en **Registrar proveedor**.',
        ],
      },
      {
        title: 'Corregir los datos de un proveedor',
        steps: [
          'Busque al proveedor por su nombre.',
          'Haga clic en **Editar**, cambie lo necesario y haga clic en **Guardar cambios**.',
        ],
      },
      {
        title: 'Dejar de usar un proveedor',
        steps: ['Busque al proveedor y haga clic en **Inactivar**.', 'Confirme en la ventana que aparece.'],
        note: 'No se borra: sus compras y cuentas quedan en el historial. Para volver a usarlo, elija **Inactivos** en el filtro y haga clic en **Activar**.',
      },
    ],
    fields: [
      { name: 'Código', description: 'Un identificador corto. Si lo deja vacío, se asigna solo.' },
      { name: 'Nombre o razón social', description: 'Obligatorio. El nombre legal del proveedor.' },
      { name: 'Nombre comercial', description: 'El nombre con que se le conoce, si es distinto.' },
      { name: 'Persona de contacto', description: 'Con quién habla usted para hacer pedidos.' },
      { name: 'Condición de pago', description: 'La condición que se propone al comprarle. En cada compra se puede cambiar, porque una compra puntual puede negociarse distinto.' },
      { name: 'Días de crédito', description: 'Cuántos días da el proveedor para pagarle.' },
    ],
    faq: [
      {
        q: '¿Dónde veo cuánto le debo a un proveedor?',
        a: 'En **Cuentas por pagar**: escriba su nombre en el buscador.',
      },
    ],
    related: ['purchases', 'payables'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Proveedores',
        body: 'Aquí están quienes le venden a su empresa. Cada compra se hace a nombre de uno de ellos.',
        placement: 'bottom',
      },
      {
        target: TARGET.pageActions,
        title: 'Registrar un proveedor',
        body: 'Con **Nuevo proveedor** registra uno nuevo. Anote bien su plazo de pago: se propone solo en cada compra.',
        placement: 'bottom',
        write: true,
      },
      {
        target: TARGET.filters,
        title: 'Buscar y filtrar',
        body: 'Busque por nombre, código o cédula. Con la lista de al lado ve los activos, los inactivos o todos.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'La lista de proveedores',
        body: 'Cada renglón es un proveedor, con su condición de pago. Con **Editar** corrige sus datos y con **Inactivar** lo retira sin borrar su historial.',
        readOnlyBody: 'Cada renglón es un proveedor, con su condición de pago. Con **Ver** abre todos sus datos.',
      },
    ],
  },

  // -------------------------------------------------------------- Productos ----
  {
    key: 'products',
    title: 'Productos',
    icon: 'box',
    summary: 'El catálogo de todo lo que maneja la empresa: lo que fabrica, lo que compra para fabricar y lo que vende, con su costo, su precio y cuántas unidades hay.',
    purpose: [
      'Todo lo que se compra, se fabrica o se vende tiene que estar primero en este catálogo. Una compra, una venta o una orden de producción siempre se arma eligiendo productos de aquí.',
      'Hay tres tipos: **Producto terminado** (lo que se vende), **Materia prima** (lo que se usa para fabricar) y **Servicio** (lo que se cobra pero no se guarda en bodega).',
    ],
    tasks: [
      {
        title: 'Registrar un producto nuevo',
        steps: [
          'Haga clic en **Nuevo producto**, arriba a la derecha.',
          'Escriba el **Nombre**. Es obligatorio.',
          'Elija el **Tipo**: producto terminado, materia prima o servicio.',
          'Elija la **Unidad de medida**: unidad, metro, litro, kilo… Es la unidad en que se cuenta, se compra y se vende.',
          'Escriba el **Costo unitario** (lo que le cuesta a usted una unidad) y el **Precio de venta** (lo que cobra por ella).',
          'Si ya tiene unidades en bodega, escriba cuántas en **Existencia inicial**. Este dato solo se pide esta vez; después la existencia cambia sola con las compras, las ventas y la producción.',
          'En **Existencia mínima** escriba a partir de cuántas unidades quiere que el sistema le avise que hay que reponer.',
          'Haga clic en **Registrar producto**.',
        ],
      },
      {
        title: 'Cambiar un precio',
        steps: [
          'Busque el producto y haga clic en **Editar**.',
          'Cambie el **Precio de venta** y haga clic en **Guardar cambios**.',
        ],
        note: 'El precio nuevo se usa en las ventas que haga desde ahora. Las ventas ya confirmadas conservan el precio con que se hicieron.',
      },
      {
        title: 'Dejar de usar un producto',
        steps: ['Busque el producto y haga clic en **Inactivar**.', 'Confirme en la ventana que aparece.'],
        note: 'No se borra: su historial queda. Solo deja de aparecer al armar compras y ventas nuevas.',
      },
    ],
    fields: [
      { name: 'Tipo', description: 'Producto terminado, materia prima o servicio. Los servicios no llevan existencia.' },
      { name: 'Categoría', description: 'Un grupo para ordenar el catálogo. Es opcional.' },
      { name: 'Unidad de medida', description: 'En qué se cuenta el producto. Las cantidades aceptan decimales, por ejemplo 2,5 metros.' },
      { name: 'Existencia mínima', description: 'Cuando la existencia llega a este número, el Panel avisa que hay que reponer.' },
      { name: 'Costo unitario', description: 'Lo que le cuesta una unidad. **Se actualiza solo**: cada compra confirmada lo cambia al costo de esa compra, y cada orden de producción terminada, al costo real de fabricación.' },
      { name: 'Precio de venta', description: 'El precio que se propone al vender. En cada venta se puede ajustar.' },
      { name: 'Impuesto (%)', description: 'El porcentaje de impuesto que se cobra al venderlo, por ejemplo 13.' },
      { name: 'Existencia inicial', description: 'Las unidades que ya tiene en bodega al registrar el producto. Solo se pide al crearlo.' },
      { name: 'Código de barras', description: 'Opcional, si el producto trae uno.' },
    ],
    cautions: [
      'Después de crear el producto, la existencia no se escribe a mano. Para corregirla use **Inventario → Ajuste por conteo**: así queda anotado el motivo y quién lo hizo.',
    ],
    faq: [
      {
        q: '¿Por qué cambió solo el costo de un producto?',
        a: 'Porque se confirmó una compra de ese producto o se terminó una orden de producción. El sistema usa siempre el costo más reciente.',
      },
      {
        q: 'Un servicio, ¿lleva existencia?',
        a: 'No. Los servicios se venden, pero no entran ni salen de bodega.',
      },
    ],
    related: ['inventory', 'purchases', 'sales', 'production'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Productos',
        body: 'El catálogo de todo lo que maneja su empresa. Antes de comprar, vender o fabricar algo, tiene que estar aquí.',
        placement: 'bottom',
      },
      {
        target: TARGET.pageActions,
        title: 'Registrar un producto',
        body: 'Con **Nuevo producto** agrega uno al catálogo, con su tipo, su costo, su precio y las unidades que ya tiene.',
        placement: 'bottom',
        write: true,
      },
      {
        target: TARGET.filters,
        title: 'Buscar y filtrar',
        body: 'Busque por nombre o código. Con los filtros puede ver un solo tipo —terminado, materia prima o servicio— o solo los activos.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'El catálogo',
        body: 'Cada renglón es un producto con su tipo, cuántas unidades hay y su precio. La existencia cambia sola con cada compra, venta y orden de producción.',
      },
    ],
  },

  // ------------------------------------------------------------- Inventario ----
  {
    key: 'inventory',
    title: 'Inventario',
    icon: 'layers',
    summary: 'El historial de cada entrada y salida de producto. Aquí averigua por qué cambió una existencia y la corrige cuando no coincide con la bodega.',
    purpose: [
      'Cada vez que un producto entra o sale, el sistema anota un **movimiento**: la fecha, el producto, el motivo, la cantidad, cuántas unidades quedaron y quién lo hizo.',
      'Casi todos los movimientos se hacen solos: al confirmar una compra entra producto, al confirmar una venta sale, y al terminar una orden de producción sale el material y entra lo fabricado. Esta pantalla sirve sobre todo para **revisar** y para **corregir**.',
    ],
    tasks: [
      {
        title: 'Averiguar por qué cambió una existencia',
        steps: [
          'Elija el producto en el filtro de arriba de la tabla.',
          'La lista muestra sus movimientos, del más reciente al más antiguo, con el motivo y quién lo hizo.',
          'La columna **Existencia** dice cuántas unidades quedaron después de cada movimiento.',
        ],
      },
      {
        title: 'Corregir la existencia después de contar la bodega',
        steps: [
          'Cuente físicamente las unidades del producto.',
          'Haga clic en **Ajuste por conteo**.',
          'Elija el producto. El sistema le muestra la **Existencia registrada**, que es lo que dice el sistema.',
          'Escriba en **Existencia contada** lo que usted contó.',
          'Explique en **Motivo del ajuste** por qué no coincidía, por ejemplo «Merma por humedad» o «Error en el conteo anterior».',
          'Haga clic en **Aplicar ajuste**. El sistema calcula solo la diferencia y la anota.',
        ],
      },
      {
        title: 'Anotar una entrada o salida que no vino de una compra ni de una venta',
        steps: [
          'Haga clic en **Registrar movimiento**.',
          'Elija el **Producto** y el **Motivo**: por ejemplo **Devolución de cliente** si le devolvieron mercadería, o **Devolución a proveedor** si usted la devolvió.',
          'Escriba la **Cantidad** y, si es una entrada, el **Costo unitario**.',
          'Explique el movimiento en **Observaciones** y haga clic en **Registrar**.',
        ],
        note: 'No use este botón para las compras y ventas normales: regístrelas en **Compras** y **Ventas**, que además crean la cuenta por pagar o por cobrar.',
      },
    ],
    fieldsTitle: 'Motivos que verá en la lista',
    fields: [
      { name: 'Compra', description: 'Entró producto al confirmar una compra.' },
      { name: 'Venta', description: 'Salió producto al confirmar una venta.' },
      { name: 'Consumo de producción', description: 'Salió material al terminar una orden de producción.' },
      { name: 'Producción terminada', description: 'Entró el producto fabricado al terminar una orden.' },
      { name: 'Consumo en reparación', description: 'Salió material usado en una reparación.' },
      { name: 'Ajuste', description: 'Corrección hecha con un ajuste por conteo.' },
      { name: 'Devolución de cliente', description: 'Un cliente devolvió producto.' },
      { name: 'Devolución a proveedor', description: 'Se le devolvió producto a un proveedor.' },
      { name: 'Existencia inicial', description: 'Las unidades que se anotaron al crear el producto.' },
    ],
    cautions: [
      'Los movimientos no se editan ni se borran. Si anotó uno mal, corríjalo con un movimiento en sentido contrario o con un ajuste por conteo, explicando el motivo.',
      'El sistema no deja sacar más unidades de las que hay. Si le aparece «Existencia insuficiente», revise primero la existencia del producto.',
    ],
    related: ['products', 'purchases', 'sales', 'production'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Inventario',
        body: 'Aquí se ve cada entrada y salida de producto, con su motivo y quién la hizo.',
        placement: 'bottom',
      },
      {
        target: TARGET.pageActions,
        title: 'Corregir el inventario',
        body: '**Ajuste por conteo** corrige la existencia después de contar la bodega.\n\n**Registrar movimiento** anota una entrada o salida que no vino de una compra ni de una venta, como una devolución.',
        placement: 'bottom',
        write: true,
      },
      {
        target: TARGET.filters,
        title: 'Filtrar por producto',
        body: 'Elija un producto para ver solo su historial.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'Los movimientos',
        body: 'Cada renglón es una entrada o una salida. La columna **Existencia** le dice cuántas unidades quedaron después de ese movimiento, y **Usuario**, quién lo hizo.',
      },
    ],
  },

  // ---------------------------------------------------------------- Compras ----
  {
    key: 'purchases',
    title: 'Compras',
    icon: 'cart',
    summary: 'Donde se anota lo que la empresa les compra a sus proveedores. Al confirmar una compra, el producto entra al inventario y se crea la cuenta por pagar.',
    purpose: [
      'Cada factura que le llega de un proveedor se registra aquí. Una compra tiene dos momentos: primero se prepara como **borrador**, que se puede corregir cuanto quiera, y después se **confirma**: el producto entra a bodega y queda anotada la deuda con el proveedor.',
    ],
    tasks: [
      {
        title: 'Registrar una compra',
        steps: [
          'Haga clic en **Nueva compra**, arriba a la derecha.',
          'Elija el **Proveedor**. El sistema propone el plazo de pago que tiene anotado.',
          'Revise la **Fecha de la compra**: es el día de la factura. Desde esa fecha se cuenta el plazo para pagar.',
          'Si tiene la factura del proveedor, escriba su número en **Número de factura del proveedor**.',
          'En **¿Cuándo se paga?** elija el plazo: **Contado** si se paga el mismo día, un plazo como **30 días** o **2 meses**, o **Fecha específica…** para marcar el día exacto en el calendario. Debajo aparece la fecha en que vence.',
          'Haga clic en **Agregar línea** por cada producto de la factura. En cada línea elija el producto y escriba la cantidad. El costo por unidad se llena solo con el último costo conocido: cámbielo por el que dice la factura. Las cantidades aceptan decimales, por ejemplo 2,5.',
          'Revise el total y haga clic en **Guardar borrador**.',
        ],
      },
      {
        title: 'Confirmar la compra cuando llega la mercadería',
        steps: [
          'Busque la compra en la lista. Su estado dirá **Borrador**.',
          'Haga clic en **Confirmar** y confirme en la ventana.',
          'En ese momento el producto entra al inventario, su costo se actualiza al de esta compra y se crea la cuenta por pagar al proveedor.',
        ],
      },
      {
        title: 'Corregir o cancelar una compra',
        steps: [
          'Mientras esté en **Borrador**, haga clic en **Editar** para cambiarla.',
          'Si ya no va, haga clic en **Cancelar** para anularla.',
        ],
        note: 'Una compra confirmada ya no se puede editar ni cancelar, porque ya movió el inventario y creó la deuda. Si hubo un error, corrija la existencia en **Inventario** y avise a quien administra el sistema.',
      },
    ],
    statuses: [
      { name: 'Borrador', description: 'Se está preparando. No mueve inventario ni crea deuda. Se puede editar o cancelar.' },
      { name: 'Confirmada', description: 'El producto ya entró a bodega y existe la cuenta por pagar. Ya no cambia.' },
      { name: 'Cancelada', description: 'Se anuló estando en borrador. No tuvo ningún efecto.' },
    ],
    cautions: [
      '**Confirmar no se puede deshacer.** Revise productos, cantidades y costos antes de hacerlo.',
      'Aunque la compra sea de **contado**, al confirmarla se crea una cuenta por pagar que vence ese mismo día. Registre el pago en **Cuentas por pagar** para que quede saldada.',
    ],
    faq: [
      {
        q: '¿Dónde anoto que ya le pagué al proveedor?',
        a: 'En **Cuentas por pagar**: busque la cuenta con el número de la compra y haga clic en **Registrar pago**.',
      },
      {
        q: '¿Por qué no subió la existencia del producto?',
        a: 'Porque la compra sigue en borrador. El producto entra solo al confirmarla.',
      },
    ],
    related: ['suppliers', 'payables', 'inventory', 'products'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Compras',
        body: 'Aquí se registra cada factura de un proveedor. Al confirmarla, el producto entra a bodega y queda la cuenta por pagar.',
        placement: 'bottom',
      },
      {
        target: TARGET.pageActions,
        title: 'Registrar una compra',
        body: 'Con **Nueva compra** anota una factura. Primero queda como borrador, para que la revise con calma antes de confirmarla.',
        placement: 'bottom',
        write: true,
      },
      {
        target: TARGET.filters,
        title: 'Buscar y filtrar',
        body: 'Busque por número o por proveedor, y filtre por estado: borrador, confirmada o cancelada.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'Las compras',
        body: 'Cada renglón es una compra con su fecha, su proveedor, cuándo vence y su estado.\n\nEn las que están en **Borrador** aparece **Confirmar**: al presionarlo entra el producto a bodega y se crea la cuenta por pagar. Eso no se puede deshacer.',
        readOnlyBody: 'Cada renglón es una compra con su fecha, su proveedor, cuándo vence y su estado. Con **Ver** abre el detalle de productos y montos.',
      },
    ],
  },

  // ----------------------------------------------------------------- Ventas ----
  {
    key: 'sales',
    title: 'Ventas',
    icon: 'receipt',
    summary: 'Donde se anota lo que la empresa vende. Al confirmar una venta, el producto sale del inventario y se crea la cuenta por cobrar al cliente.',
    purpose: [
      'Cada venta tiene dos momentos: primero se prepara como **borrador**, que se puede corregir cuanto quiera, y después se **confirma**: el producto sale de bodega y queda anotado lo que el cliente debe.',
      'Al confirmar, el sistema también guarda cuánto le costó a usted cada producto vendido. Con eso el reporte de ventas puede decirle cuánto ganó.',
    ],
    tasks: [
      {
        title: 'Registrar una venta',
        steps: [
          'Haga clic en **Nueva venta**, arriba a la derecha.',
          'Elija el **Cliente**. El sistema propone su condición de pago.',
          'Revise la **Fecha de la venta**. Desde esa fecha se cuenta el plazo que tiene el cliente para pagar.',
          'En **¿Cuándo paga el cliente?** elija **Contado**, un plazo como **30 días** o **1 mes**, o **Fecha específica…** para marcar el día exacto. Debajo aparece la fecha en que vence.',
          'Haga clic en **Agregar línea** por cada producto. En cada línea elija el **Producto** y escriba la **Cantidad**. El **Precio** y el impuesto (**Imp. %**) se llenan solos con los del catálogo; puede cambiarlos. En **Desc. %** anote un descuento si lo hay.',
          'Revise el total y haga clic en **Guardar borrador**.',
        ],
      },
      {
        title: 'Confirmar la venta',
        steps: [
          'Busque la venta en la lista. Su estado dirá **Borrador**.',
          'Haga clic en **Confirmar** y confirme en la ventana.',
          'En ese momento el producto sale del inventario y se crea la cuenta por cobrar al cliente.',
        ],
        note: 'Si no hay suficientes unidades de algún producto, el sistema no deja confirmar y le dice cuántas hay. Revise el inventario o termine primero la orden de producción pendiente.',
      },
      {
        title: 'Corregir o cancelar una venta',
        steps: [
          'Mientras esté en **Borrador**, haga clic en **Editar** para cambiarla.',
          'Si ya no va, haga clic en **Cancelar** para anularla.',
        ],
        note: 'Una venta confirmada ya no se puede editar ni cancelar. Si el cliente devuelve producto, anótelo en **Inventario → Registrar movimiento** con el motivo **Devolución de cliente**.',
      },
    ],
    fieldsTitle: 'Las columnas de cada línea',
    fields: [
      { name: 'Producto', description: 'Lo que se vende. Los servicios también se eligen aquí, pero no mueven inventario.' },
      { name: 'Cantidad', description: 'Cuántas unidades. Acepta decimales.' },
      { name: 'Precio', description: 'Precio por unidad. Se propone el del catálogo.' },
      { name: 'Desc. %', description: 'Descuento en porcentaje sobre esa línea.' },
      { name: 'Imp. %', description: 'Impuesto en porcentaje. Se propone el del producto.' },
      { name: 'Subtotal', description: 'Lo que suma esa línea. Se calcula solo.' },
    ],
    statuses: [
      { name: 'Borrador', description: 'Se está preparando. No mueve inventario ni crea la cuenta por cobrar.' },
      { name: 'Confirmada', description: 'El producto ya salió de bodega y existe la cuenta por cobrar. Ya no cambia.' },
      { name: 'Cancelada', description: 'Se anuló estando en borrador. No tuvo ningún efecto.' },
    ],
    cautions: [
      '**Confirmar no se puede deshacer.** Revise cliente, productos, cantidades y precios antes.',
      'Aunque la venta sea de **contado**, al confirmarla se crea una cuenta por cobrar que vence ese mismo día. Cuando el cliente pague, anote el cobro en **Cuentas por cobrar**.',
    ],
    faq: [
      {
        q: '¿Cómo anoto que el cliente ya pagó?',
        a: 'En **Cuentas por cobrar**: busque la cuenta con el número de la venta y haga clic en **Registrar cobro**.',
      },
      {
        q: 'Me dice «Existencia insuficiente». ¿Qué hago?',
        a: 'No hay tantas unidades como quiere vender. Revise la existencia en **Productos** o **Inventario**. Si el producto se está fabricando, termine primero la orden en **Producción**.',
      },
    ],
    related: ['customers', 'receivables', 'products', 'inventory'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Ventas',
        body: 'Aquí se registra cada venta. Al confirmarla, el producto sale de bodega y queda lo que el cliente debe.',
        placement: 'bottom',
      },
      {
        target: TARGET.pageActions,
        title: 'Registrar una venta',
        body: 'Con **Nueva venta** arma la venta: el cliente, cuándo paga y los productos. Queda como borrador hasta que la confirme.',
        placement: 'bottom',
        write: true,
      },
      {
        target: TARGET.filters,
        title: 'Buscar y filtrar',
        body: 'Busque por número o por cliente, y filtre por estado.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'Las ventas',
        body: 'Cada renglón es una venta con su cliente, cuándo vence y su estado.\n\nEn las que están en **Borrador** aparece **Confirmar**: al presionarlo sale el producto de bodega y se crea la cuenta por cobrar. Eso no se puede deshacer.',
        readOnlyBody: 'Cada renglón es una venta con su cliente, cuándo vence y su estado. Con **Ver** abre el detalle.',
      },
    ],
  },

  // ------------------------------------------------------------- Producción ----
  {
    key: 'production',
    title: 'Producción',
    icon: 'factory',
    summary: 'Donde se planifica y se anota lo que la empresa fabrica. Al terminar una orden, sale el material usado y entra el producto fabricado, con su costo real.',
    purpose: [
      'Producción tiene dos partes, cada una en su pestaña: las **Recetas**, que dicen qué materiales lleva fabricar cada producto, y las **Órdenes**, que son cada vez que se manda a fabricar algo.',
      'Una orden pasa por tres estados: **Planificada** (se sabe qué se va a hacer), **En proceso** (se está haciendo) y **Terminada** (se hizo). El inventario solo cambia al terminarla.',
    ],
    tasks: [
      {
        title: 'Crear una receta',
        steps: [
          'Abra la pestaña **Recetas** y haga clic en **Nueva receta**.',
          'Escriba un **Nombre** para la receta y elija el **Producto que resulta**.',
          'En **Rendimiento** escriba cuántas unidades salen con estas cantidades. Normalmente es 1.',
          'Haga clic en **Agregar material** por cada material que lleva: elija la materia prima y escriba la cantidad.',
          'Si quiere que el costo incluya el trabajo, escriba cuánto cuesta la **Mano de obra**.',
          'Haga clic en **Crear receta**.',
        ],
      },
      {
        title: 'Mandar a fabricar',
        steps: [
          'En la pestaña **Órdenes**, haga clic en **Nueva orden**.',
          'Elija el **Producto a fabricar** y escriba la **Cantidad a producir**.',
          'Elija la **Receta**. El sistema calcula solo cuánto material hace falta para esa cantidad. Si el producto no tiene receta, elija **Sin receta (materiales a mano)** y agregue los materiales usted.',
          'Haga clic en **Crear orden**. Queda **Planificada**.',
        ],
      },
      {
        title: 'Iniciar y terminar una orden',
        steps: [
          'Cuando empiecen a trabajar, haga clic en **Iniciar**. La orden queda **En proceso**.',
          'Cuando terminen, haga clic en **Terminar**. Se abre una ventana.',
          'Escriba la **Cantidad producida**, que puede ser distinta de la planeada, y revise cuánto material se usó de verdad. Corrija si se usó más o menos.',
          'Haga clic en **Terminar orden**. En ese momento sale el material de bodega, entra el producto fabricado y se calcula su costo real.',
        ],
      },
    ],
    statuses: [
      { name: 'Planificada', description: 'Se sabe qué se va a fabricar y con qué. Nada se ha movido.' },
      { name: 'En proceso', description: 'Se está fabricando. El material sigue en bodega hasta terminar.' },
      { name: 'Terminada', description: 'Ya salió el material y entró el producto, con su costo real.' },
      { name: 'Cancelada', description: 'Se anuló antes de terminar. No movió nada.' },
    ],
    cautions: [
      'Terminar una orden no se puede deshacer.',
      'Para terminarla tiene que haber suficiente material en bodega. Si falta, el sistema le dice cuál.',
      'El costo del producto fabricado se reemplaza por el costo real de la orden: los materiales usados más la mano de obra, dividido entre las unidades producidas.',
    ],
    faq: [
      {
        q: '¿Por qué no bajó el material al crear la orden?',
        a: 'Porque el material sale al **terminar** la orden, no al crearla ni al iniciarla.',
      },
      {
        q: '¿Qué pasa si se usó más material del planeado?',
        a: 'Al terminar la orden, corrija la cantidad que de verdad se usó. El costo se calcula con lo que se consumió realmente.',
      },
      {
        q: '¿Cómo retiro una receta que ya no uso?',
        a: 'En la pestaña **Recetas**, haga clic en **Retirar**. Se puede reactivar después.',
      },
    ],
    related: ['products', 'inventory'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Producción',
        body: 'Aquí se manda a fabricar y se anota lo fabricado. El inventario cambia solo al terminar cada orden.',
        placement: 'bottom',
      },
      {
        target: '[data-tour="production-tabs"]',
        title: 'Órdenes y recetas',
        body: 'Con estas pestañas pasa de las **Órdenes** (lo que se manda a fabricar) a las **Recetas** (qué materiales lleva cada producto).',
        placement: 'bottom',
      },
      {
        target: TARGET.pageActions,
        title: 'Crear una orden o una receta',
        body: 'El botón cambia según la pestaña: en Órdenes crea una **Nueva orden** y en Recetas, una **Nueva receta**.',
        placement: 'bottom',
        write: true,
      },
      {
        target: TARGET.filters,
        title: 'Filtrar las órdenes',
        body: 'Busque una orden o filtre por estado: planificada, en proceso, terminada o cancelada.',
        placement: 'bottom',
        optional: true,
      },
      {
        target: TARGET.table,
        title: 'Las órdenes',
        body: 'Cada orden avanza con los botones de su renglón: **Iniciar** cuando empiezan a trabajar y **Terminar** cuando está lista. Solo al terminar sale el material y entra el producto.',
        readOnlyBody: 'Cada renglón es una orden con su producto, cantidad, estado y costo. Con **Ver** abre el detalle de materiales.',
        optional: true,
      },
    ],
  },

  // ----------------------------------------------------------- Reparaciones ----
  {
    key: 'repairs',
    title: 'Reparaciones',
    icon: 'wrench',
    summary: 'Los trabajos de reparación: qué se recibió, qué falla tiene, cuándo se prometió y cuánto cuesta. Al entregar, se crea la cuenta por cobrar al cliente.',
    purpose: [
      'Cada artículo que un cliente deja para reparar se registra aquí. Una reparación pasa por **Recibida → En proceso → Lista para entregar → Entregada**.',
      'El material que se usa sale de bodega al **terminar** el trabajo, y el cobro al cliente nace al **entregar**.',
    ],
    tasks: [
      {
        title: 'Recibir un artículo',
        steps: [
          'Haga clic en **Recibir artículo**, arriba a la derecha.',
          'Elija el **Cliente**.',
          'Describa en **Artículo recibido** qué dejó, con detalle suficiente para reconocerlo. Por ejemplo: «Mesa de comedor de roble, con una pata floja».',
          'Anote en **Falla reportada por el cliente** lo que el cliente dice que tiene.',
          'Escriba la **Fecha prometida** de entrega. Si pasa esa fecha sin entregar, el Panel lo avisa.',
          'Si ya lo revisaron, anote el **Diagnóstico del taller**.',
          'Escriba el costo de **Mano de obra** y agregue con **Agregar material** los materiales que se van a usar.',
          'Elija la **Condición de pago** y, si es a crédito, los **Días de crédito**.',
          'Haga clic en **Registrar reparación**. Queda **Recibida**.',
        ],
      },
      {
        title: 'Hacer el trabajo',
        steps: [
          'Cuando empiecen a trabajar, haga clic en **Iniciar**. Queda **En proceso**.',
          'Cuando terminen, haga clic en **Terminar**. En la ventana anote el **Diagnóstico final** y revise los materiales usados.',
          'Haga clic en **Terminar reparación**. Sale el material de bodega y queda **Lista para entregar**.',
        ],
      },
      {
        title: 'Entregar y cobrar',
        steps: [
          'Cuando el cliente retire el artículo, haga clic en **Entregar** y confirme.',
          'Queda **Entregada** y se crea la cuenta por cobrar.',
          'Si el cliente paga en ese momento, anote el cobro en **Cuentas por cobrar**.',
        ],
      },
    ],
    statuses: [
      { name: 'Recibida', description: 'El artículo está en el taller, esperando.' },
      { name: 'En proceso', description: 'Se está trabajando.' },
      { name: 'Lista para entregar', description: 'Terminada. Ya salió el material de bodega.' },
      { name: 'Entregada', description: 'El cliente la retiró y existe la cuenta por cobrar.' },
      { name: 'Cancelada', description: 'Se anuló antes de entregar.' },
    ],
    cautions: [
      'Terminar y entregar no se pueden deshacer: el primero saca el material de bodega y el segundo crea la cuenta por cobrar.',
    ],
    faq: [
      {
        q: '¿Cómo veo las reparaciones atrasadas?',
        a: 'En el filtro de fechas elija **Solo atrasadas**. Aparecen las que pasaron la fecha prometida sin entregarse.',
      },
      {
        q: '¿Por qué no se creó la cuenta por cobrar al terminar?',
        a: 'Porque el cobro nace al **entregar**, no al terminar. Así no se le cobra al cliente algo que todavía no ha retirado.',
      },
    ],
    related: ['customers', 'receivables', 'inventory'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Reparaciones',
        body: 'Aquí se sigue cada trabajo de reparación, desde que se recibe el artículo hasta que se entrega.',
        placement: 'bottom',
      },
      {
        target: TARGET.pageActions,
        title: 'Recibir un artículo',
        body: 'Con **Recibir artículo** anota lo que el cliente dejó, la falla, la fecha prometida y el costo.',
        placement: 'bottom',
        write: true,
      },
      {
        target: TARGET.filters,
        title: 'Buscar y filtrar',
        body: 'Filtre por estado, o elija **Solo atrasadas** para ver los trabajos que pasaron la fecha prometida.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'Los trabajos',
        body: 'Cada reparación avanza con los botones de su renglón: **Iniciar**, **Terminar** y **Entregar**. Al terminar sale el material; al entregar nace el cobro.',
        readOnlyBody: 'Cada renglón es un trabajo con su cliente, el artículo, la fecha prometida y su estado. Con **Ver** abre el detalle.',
      },
    ],
  },

  // ----------------------------------------------------- Cuentas por cobrar ----
  {
    key: 'receivables',
    title: 'Cuentas por cobrar',
    icon: 'arrow-in',
    summary: 'Lo que los clientes le deben a la empresa. Aquí se anota cada pago que hace un cliente, completo o en partes.',
    purpose: [
      'Las cuentas por cobrar **no se crean a mano**: nacen solas al confirmar una venta o al entregar una reparación. Aquí usted les da seguimiento y anota los cobros.',
      'Cada cobro que registra se anota solo como un ingreso en **Ingresos**. No tiene que anotarlo dos veces.',
    ],
    tasks: [
      {
        title: 'Anotar que un cliente pagó',
        steps: [
          'Busque la cuenta: escriba el nombre del cliente o el número del documento.',
          'Haga clic en **Registrar cobro**, en su renglón.',
          'Revise el **Monto**. Viene lleno con lo que falta; si el cliente pagó solo una parte, cámbielo.',
          'Elija el **Medio de cobro**: Efectivo, Transferencia, Sinpe Móvil, Tarjeta o Cheque.',
          'En **Referencia** anote el número de la transferencia, del comprobante o del cheque. Le servirá si hay que revisar después.',
          'Haga clic en **Registrar cobro**, abajo de la ventana.',
        ],
        note: 'Si el cliente paga en partes, anote cada pago cuando llegue. La cuenta queda en **Cobro parcial** hasta que el saldo llegue a cero.',
      },
      {
        title: 'Ver los pagos de una cuenta',
        steps: ['Haga clic en **Ver**, en el renglón de la cuenta.', 'Aparece cada cobro con su fecha, su monto y su medio.'],
      },
      {
        title: 'Ver quién está atrasado',
        steps: ['En el filtro de fechas, elija **Solo vencidas**.', 'Aparecen las cuentas que pasaron su fecha de vencimiento sin pagarse completas.'],
      },
    ],
    fieldsTitle: 'Las columnas',
    fields: [
      { name: 'Documento', description: 'El número de la venta o reparación que originó la cuenta.' },
      { name: 'Origen', description: 'Si viene de una venta o de una reparación.' },
      { name: 'Vence', description: 'La fecha límite de pago.' },
      { name: 'Total', description: 'Lo que costó la venta o reparación.' },
      { name: 'Saldo', description: 'Lo que falta por cobrar.' },
    ],
    statuses: [
      { name: 'Pendiente', description: 'El cliente todavía no ha pagado nada.' },
      { name: 'Cobro parcial', description: 'Pagó una parte; falta el saldo.' },
      { name: 'Cobrada', description: 'Pagó todo. Ya no admite más cobros.' },
    ],
    cautions: [
      'No se puede cobrar más que el saldo. Si el cliente pagó de más, anote solo el saldo y registre la diferencia aparte.',
      'Revise el monto antes de registrar: un cobro queda en el historial de la cuenta.',
    ],
    faq: [
      {
        q: '¿Cómo creo una cuenta por cobrar?',
        a: 'No se crea a mano: aparece sola al confirmar una venta o al entregar una reparación.',
      },
      {
        q: 'La venta fue de contado. ¿Igual tengo que anotar el cobro?',
        a: 'Sí. La venta de contado crea una cuenta que vence el mismo día; al anotar el cobro queda saldada y el dinero aparece en Ingresos.',
      },
    ],
    related: ['sales', 'repairs', 'income', 'customers'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Cuentas por cobrar',
        body: 'Lo que los clientes le deben. Estas cuentas aparecen solas al confirmar ventas y entregar reparaciones.',
        placement: 'bottom',
      },
      {
        target: TARGET.filters,
        title: 'Buscar y filtrar',
        body: 'Busque por cliente o documento. Con los filtros puede ver solo lo pendiente, o elegir **Solo vencidas** para ver quién está atrasado.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'Las cuentas',
        body: 'El **Total** es lo que costó y el **Saldo** es lo que falta cobrar.\n\nCon **Registrar cobro** anota un pago del cliente, y con **Ver** revisa los pagos que ya hizo. Cada cobro aparece solo en Ingresos.',
        readOnlyBody: 'El **Total** es lo que costó y el **Saldo** es lo que falta cobrar. Con **Ver** revisa los pagos que ya hizo el cliente.',
      },
    ],
  },

  // ------------------------------------------------------ Cuentas por pagar ----
  {
    key: 'payables',
    title: 'Cuentas por pagar',
    icon: 'arrow-out',
    summary: 'Lo que la empresa les debe a sus proveedores. Aquí se anota cada pago que se les hace, completo o en partes.',
    purpose: [
      'Las cuentas por pagar **no se crean a mano**: nacen solas al confirmar una compra. Aquí usted les da seguimiento y anota los pagos.',
      'Cada pago que registra se anota solo como un gasto en **Gastos**. No tiene que anotarlo dos veces.',
    ],
    tasks: [
      {
        title: 'Anotar un pago a un proveedor',
        steps: [
          'Busque la cuenta por el nombre del proveedor o el número del documento.',
          'Haga clic en **Registrar pago**, en su renglón.',
          'Revise el **Monto**. Viene lleno con lo que falta; si pagó solo una parte, cámbielo.',
          'Elija el **Método de pago**: Efectivo, Transferencia, Cheque o Tarjeta.',
          'En **Referencia** anote el número de la transferencia o del cheque.',
          'Haga clic en **Registrar pago**, abajo de la ventana.',
        ],
      },
      {
        title: 'Ver qué hay que pagar pronto',
        steps: [
          'Mire la columna **Vence**: dice hasta cuándo tiene para pagar cada cuenta.',
          'En el filtro de fechas elija **Solo vencidas** para ver las que ya pasaron su fecha.',
        ],
      },
    ],
    statuses: [
      { name: 'Pendiente', description: 'No se ha pagado nada.' },
      { name: 'Pago parcial', description: 'Se pagó una parte; falta el saldo.' },
      { name: 'Pagada', description: 'Se pagó todo. Ya no admite más pagos.' },
    ],
    cautions: ['No se puede pagar más que el saldo de la cuenta.'],
    faq: [
      {
        q: '¿Cómo creo una cuenta por pagar?',
        a: 'No se crea a mano: aparece sola al confirmar una compra en **Compras**.',
      },
      {
        q: '¿Dónde anoto la luz, el agua o el alquiler?',
        a: 'Esos gastos no vienen de una compra de productos: anótelos directamente en **Gastos**.',
      },
    ],
    related: ['purchases', 'suppliers', 'expenses'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Cuentas por pagar',
        body: 'Lo que su empresa les debe a los proveedores. Estas cuentas aparecen solas al confirmar compras.',
        placement: 'bottom',
      },
      {
        target: TARGET.filters,
        title: 'Buscar y filtrar',
        body: 'Busque por proveedor o documento. Con **Solo vencidas** ve lo que ya pasó su fecha de pago.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'Las cuentas',
        body: 'La columna **Vence** dice hasta cuándo tiene para pagar y **Saldo**, cuánto falta.\n\nCon **Registrar pago** anota un pago al proveedor; aparece solo en Gastos.',
        readOnlyBody: 'La columna **Vence** dice hasta cuándo hay para pagar y **Saldo**, cuánto falta. Con **Ver** revisa los pagos hechos.',
      },
    ],
  },

  // --------------------------------------------------------------- Ingresos ----
  {
    key: 'income',
    title: 'Ingresos',
    icon: 'plus',
    summary: 'Todo el dinero que entra a la empresa. Los cobros a clientes aparecen solos; aquí anota además cualquier otro ingreso.',
    purpose: [
      'Aquí hay dos clases de movimientos. Los **automáticos** los anota el sistema cuando usted registra un cobro en Cuentas por cobrar. Los **manuales** los anota usted: por ejemplo un alquiler que cobra, los intereses del banco o la venta de algo que no está en el catálogo.',
    ],
    tasks: [
      {
        title: 'Anotar un ingreso',
        steps: [
          'Haga clic en **Registrar ingreso**, arriba a la derecha.',
          'Escriba en **Detalle** qué fue, en pocas palabras.',
          'Elija la **Categoría**. Las categorías se crean en **Configuración**.',
          'Escriba el **Monto** y revise la **Fecha**.',
          'Elija el **Medio de pago** y, si hay, anote la **Referencia** (número de transferencia o comprobante).',
          'Haga clic en **Registrar**.',
        ],
      },
      {
        title: 'Ver los ingresos de un período',
        steps: [
          'Elija las fechas en **Desde** y **Hasta**.',
          'Si quiere, filtre por categoría o elija ver solo los manuales o solo los automáticos.',
        ],
      },
      {
        title: 'Corregir o borrar un ingreso manual',
        steps: ['Busque el ingreso y haga clic en **Editar** o en **Eliminar**.'],
        note: 'Solo los ingresos manuales se pueden editar o eliminar. Los automáticos vienen de un cobro registrado en Cuentas por cobrar y no se tocan desde aquí.',
      },
    ],
    cautions: [
      'No anote aquí los cobros a clientes: aparecen solos al registrarlos en **Cuentas por cobrar**. Si también los anota a mano, quedarían contados dos veces.',
    ],
    related: ['receivables', 'expenses', 'settings', 'reports'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Ingresos',
        body: 'Todo el dinero que entra. Los cobros a clientes aparecen aquí solos.',
        placement: 'bottom',
      },
      {
        target: TARGET.pageActions,
        title: 'Anotar otro ingreso',
        body: 'Con **Registrar ingreso** anota dinero que entra por otras vías, como un alquiler o intereses. No anote aquí los cobros a clientes: ya aparecen solos.',
        placement: 'bottom',
        write: true,
      },
      {
        target: TARGET.filters,
        title: 'Período y filtros',
        body: 'Elija las fechas **Desde** y **Hasta**, una categoría, o si quiere ver solo los manuales o solo los automáticos.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'Los ingresos',
        body: 'Cada renglón es una entrada de dinero. Los manuales se pueden **Editar** o **Eliminar**; los automáticos vienen de un cobro y no se tocan desde aquí.',
        readOnlyBody: 'Cada renglón es una entrada de dinero, con su fecha, categoría, medio y monto.',
      },
    ],
  },

  // ----------------------------------------------------------------- Gastos ----
  {
    key: 'expenses',
    title: 'Gastos',
    icon: 'minus',
    summary: 'Todo el dinero que sale de la empresa. Los pagos a proveedores aparecen solos; aquí anota además los demás gastos: luz, agua, alquiler, planilla.',
    purpose: [
      'Aquí hay dos clases de movimientos. Los **automáticos** los anota el sistema cuando usted registra un pago en Cuentas por pagar. Los **manuales** los anota usted: la luz, el agua, el alquiler, la planilla, el combustible y cualquier gasto que no sea una compra de productos.',
    ],
    tasks: [
      {
        title: 'Anotar un gasto',
        steps: [
          'Haga clic en **Registrar gasto**, arriba a la derecha.',
          'Escriba en **Detalle** qué fue, por ejemplo «Recibo de luz de marzo».',
          'Elija la **Categoría**. Las categorías se crean en **Configuración**.',
          'Escriba el **Monto** y revise la **Fecha**.',
          'Elija el **Medio de pago** y, si hay, anote la **Referencia**.',
          'Haga clic en **Registrar**.',
        ],
      },
      {
        title: 'Ver los gastos de un período',
        steps: ['Elija las fechas en **Desde** y **Hasta**.', 'Si quiere, filtre por categoría o por origen.'],
      },
      {
        title: 'Corregir o borrar un gasto manual',
        steps: ['Busque el gasto y haga clic en **Editar** o en **Eliminar**.'],
        note: 'Solo los gastos manuales se pueden editar o eliminar. Los automáticos vienen de un pago registrado en Cuentas por pagar.',
      },
    ],
    cautions: [
      'No anote aquí los pagos de compras a proveedores: aparecen solos al registrarlos en **Cuentas por pagar**. Si también los anota a mano, quedarían contados dos veces.',
    ],
    related: ['payables', 'income', 'settings', 'reports'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Gastos',
        body: 'Todo el dinero que sale. Los pagos a proveedores aparecen aquí solos.',
        placement: 'bottom',
      },
      {
        target: TARGET.pageActions,
        title: 'Anotar un gasto',
        body: 'Con **Registrar gasto** anota la luz, el agua, el alquiler, la planilla y cualquier gasto que no sea una compra de productos.',
        placement: 'bottom',
        write: true,
      },
      {
        target: TARGET.filters,
        title: 'Período y filtros',
        body: 'Elija las fechas **Desde** y **Hasta**, una categoría, o si quiere ver solo los manuales o solo los automáticos.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'Los gastos',
        body: 'Cada renglón es una salida de dinero. Los manuales se pueden **Editar** o **Eliminar**; los automáticos vienen de un pago y no se tocan desde aquí.',
        readOnlyBody: 'Cada renglón es una salida de dinero, con su fecha, categoría, medio y monto.',
      },
    ],
  },

  // --------------------------------------------------------------- Reportes ----
  {
    key: 'reports',
    title: 'Reportes',
    icon: 'chart',
    summary: 'Resúmenes listos para analizar la empresa: ventas, clientes, compras, inventario, producción, cuentas y dinero. Todos se pueden llevar a Excel.',
    purpose: [
      'Los reportes juntan lo que ya está registrado y lo ordenan para que responda preguntas: qué se vende más, quién compra más, a quién se le debe, en qué se va la plata.',
      'No hay que preparar nada: se elige el reporte y el período, y el sistema lo arma en el momento.',
    ],
    tasks: [
      {
        title: 'Ver un reporte',
        steps: [
          'Haga clic en el nombre del reporte, en la lista de la izquierda.',
          'Si el reporte usa un período, elija las fechas de inicio y fin. El reporte se actualiza solo.',
          'Arriba verá las cifras principales, después un gráfico de barras y abajo la tabla con el detalle.',
        ],
      },
      {
        title: 'Llevar un reporte a Excel',
        steps: [
          'Con el reporte abierto, haga clic en **Descargar CSV**, arriba a la derecha.',
          'El archivo queda en la carpeta de descargas de su computadora.',
          'Ábralo con Excel: las columnas y los montos salen ordenados.',
        ],
      },
    ],
    fieldsTitle: 'Los reportes disponibles',
    fields: [
      { name: 'Ventas por producto', description: 'Qué se vendió, cuánto ingresó y qué utilidad dejó.' },
      { name: 'Ventas por cliente', description: 'Quién compra, cuánto y con qué frecuencia.' },
      { name: 'Compras por proveedor', description: 'En qué y con quién se gastó en el período.' },
      { name: 'Valoración de inventario', description: 'Cuánto vale hoy lo que hay en bodega, al costo.' },
      { name: 'Producción terminada', description: 'Órdenes completadas y su costo real.' },
      { name: 'Antigüedad de cuentas por cobrar', description: 'Cuánto le deben y desde hace cuánto.' },
      { name: 'Antigüedad de cuentas por pagar', description: 'Qué debe su empresa y cuándo vence.' },
      { name: 'Ingresos y gastos', description: 'El dinero que entró y salió en el período, por categoría.' },
    ],
    faq: [
      {
        q: '¿Por qué algunos reportes no piden fechas?',
        a: 'La valoración de inventario y la antigüedad de cuentas muestran cómo está todo **hoy**, así que no usan un período.',
      },
      {
        q: '¿Por qué una venta no aparece en el reporte?',
        a: 'Los reportes de ventas solo cuentan las ventas **confirmadas** dentro del período elegido. Revise las fechas.',
      },
    ],
    related: ['sales', 'purchases', 'receivables', 'income'],
    tour: [
      {
        target: '[data-tour="report-picker"]',
        title: 'La lista de reportes',
        body: 'Haga clic en el nombre de un reporte para abrirlo. Están agrupados por tema: comercial, operación y finanzas.',
        placement: 'right',
      },
      {
        target: '[data-tour="report-period"]',
        title: 'El período',
        body: 'Elija las fechas de inicio y fin. El reporte se vuelve a calcular solo.',
        placement: 'bottom',
        optional: true,
      },
      {
        target: TARGET.pageActions,
        title: 'Llevarlo a Excel',
        body: 'Con **Descargar CSV** guarda el reporte en un archivo que se abre con Excel.',
        placement: 'bottom',
        optional: true,
      },
    ],
  },

  // --------------------------------------------------------------- Usuarios ----
  {
    key: 'users',
    title: 'Usuarios',
    icon: 'shield',
    summary: 'Quién puede entrar al sistema y qué puede hacer. Aquí se crean los usuarios de la empresa y se les da un rol.',
    purpose: [
      'Cada persona que use Gestora necesita su propio usuario: un correo y una contraseña para entrar.',
      'El **rol** decide qué puede hacer. **Administrador** puede ver, crear y modificar todo. **Consulta** puede ver todo, pero no crear ni modificar nada: sirve para quien necesita revisar información sin riesgo de cambiarla.',
    ],
    tasks: [
      {
        title: 'Crear un usuario',
        steps: [
          'Haga clic en **Nuevo usuario**, arriba a la derecha.',
          'Escriba el **Nombre**, los **Apellidos** y el **Correo electrónico**. Con ese correo va a entrar la persona.',
          'Elija el **Rol**: Administrador o Consulta.',
          'Escriba una **Contraseña** de al menos 8 caracteres.',
          'Haga clic en **Crear usuario**.',
        ],
        note: 'Entréguele la contraseña a la persona en privado y pídale que la cambie en **Mi cuenta** la primera vez que entre. La primera vez, el sistema le mostrará solo el recorrido de bienvenida.',
      },
      {
        title: 'Cambiar el rol o la contraseña de alguien',
        steps: [
          'Busque al usuario y haga clic en **Editar**.',
          'Cambie el **Rol** o escriba una **Nueva contraseña**. Si deja la contraseña vacía, no cambia.',
          'Haga clic en **Guardar cambios**.',
        ],
      },
      {
        title: 'Quitarle el acceso a alguien',
        steps: ['Busque al usuario y haga clic en **Desactivar**.', 'Confirme en la ventana que aparece.'],
        note: 'El usuario no se borra, para que su historial siga en la Auditoría. No podrá entrar hasta que lo active de nuevo.',
      },
    ],
    cautions: [
      'Cada persona debe tener su propio usuario. Si varias comparten uno, la Auditoría no puede decir quién hizo qué.',
    ],
    related: ['audit'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Usuarios',
        body: 'Quién entra al sistema y con qué permisos.',
        placement: 'bottom',
      },
      {
        target: TARGET.pageActions,
        title: 'Crear un usuario',
        body: 'Con **Nuevo usuario** le da acceso a una persona. Elija su rol: **Administrador** puede hacer todo; **Consulta** solo puede ver.',
        placement: 'bottom',
        write: true,
      },
      {
        target: TARGET.filters,
        title: 'Buscar y filtrar',
        body: 'Busque por nombre o correo, y filtre los activos o los inactivos.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'Los usuarios',
        body: 'La columna **Último ingreso** dice cuándo entró cada persona por última vez. Con **Desactivar** le quita el acceso sin borrar su historial.',
        readOnlyBody: 'La columna **Último ingreso** dice cuándo entró cada persona por última vez.',
      },
    ],
  },

  // -------------------------------------------------------------- Auditoría ----
  {
    key: 'audit',
    title: 'Auditoría',
    icon: 'history',
    summary: 'El registro de quién hizo qué y cuándo. Cada operación importante queda anotada aquí y no se puede borrar.',
    purpose: [
      'Cada vez que alguien entra al sistema, registra una venta, confirma una compra, cambia un precio o hace cualquier operación importante, queda un renglón aquí con su nombre, la fecha y la hora, y el detalle.',
      'Nadie puede editar ni borrar estos registros, ni siquiera un administrador. Por eso sirven para aclarar cualquier duda sobre lo que pasó.',
    ],
    tasks: [
      {
        title: 'Averiguar qué pasó con algo',
        steps: [
          'Elija el módulo en el filtro, por ejemplo **Clientes** o **Inventario**.',
          'Escriba en el buscador el nombre de la persona, un número de documento o una palabra del detalle.',
          'Lea los renglones: dicen quién hizo qué, cuándo y, en los cambios, cómo estaba antes y cómo quedó.',
        ],
      },
    ],
    related: ['users'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Auditoría',
        body: 'El historial de quién hizo qué y cuándo. Nadie puede editarlo ni borrarlo.',
        placement: 'bottom',
      },
      {
        target: TARGET.filters,
        title: 'Buscar',
        body: 'Elija un módulo o escriba un nombre, un número de documento o una palabra para encontrar lo que busca.',
        placement: 'bottom',
      },
      {
        target: TARGET.table,
        title: 'Los registros',
        body: 'Cada renglón dice la fecha y la hora, quién lo hizo, qué acción fue y el detalle.',
      },
    ],
  },

  // ---------------------------------------------------------- Configuración ----
  {
    key: 'settings',
    title: 'Configuración',
    icon: 'cog',
    summary: 'Los datos de su empresa y las categorías con las que se clasifican los ingresos y los gastos.',
    purpose: [
      'Aquí se ajustan los datos de la empresa —nombre, cédula, teléfono, dirección, moneda— y se administran las categorías que ordenan el dinero en Ingresos, Gastos y los reportes.',
    ],
    tasks: [
      {
        title: 'Actualizar los datos de la empresa',
        steps: [
          'En **Datos de la empresa**, cambie lo que necesite: **Nombre**, **Cédula jurídica**, **Teléfono** o **Dirección**.',
          'En **Moneda** elija en qué moneda se muestran los montos.',
          'En **Impuesto por defecto %** escriba el porcentaje de impuesto que usa normalmente.',
          'Haga clic en **Guardar cambios**.',
        ],
        note: 'Cambiar la moneda cambia el símbolo con que se muestran los montos en todo el sistema. No convierte los montos que ya estaban registrados.',
      },
      {
        title: 'Crear una categoría de ingreso o de gasto',
        steps: [
          'En **Categorías de ingresos y gastos**, haga clic en **Agregar**.',
          'Escriba el **Nombre**, por ejemplo «Servicios públicos» o «Alquiler».',
          'Elija el **Tipo**: **Ingreso** o **Gasto**.',
          'Si quiere, agregue una **Descripción** que explique qué va en esa categoría.',
          'Haga clic en **Crear categoría**.',
        ],
      },
      {
        title: 'Cambiar o retirar una categoría',
        steps: [
          'Busque la categoría en su lista y haga clic en **Editar** para cambiarle el nombre.',
          'Haga clic en **Inactivar** si ya no la usa. No se borra: los movimientos que la usaron la conservan.',
        ],
      },
    ],
    fields: [
      { name: 'Correo de la cuenta', description: 'El correo con que la empresa está registrada en Gestora. No se cambia desde aquí.' },
      { name: 'Moneda', description: 'Colón, dólar o euro. Define cómo se muestran los montos.' },
      { name: 'Impuesto por defecto %', description: 'El porcentaje de impuesto que se propone por defecto.' },
    ],
    cautions: [
      'Las categorías del sistema, como «Cobros a clientes» y «Pagos a proveedores», no se pueden editar ni inactivar: son las que usan los cobros y pagos automáticos.',
    ],
    related: ['income', 'expenses'],
    tour: [
      {
        target: TARGET.pageTitle,
        title: 'Configuración',
        body: 'Los datos de su empresa y las categorías del dinero.',
        placement: 'bottom',
      },
      {
        target: '[data-tour="settings-company"]',
        title: 'Datos de la empresa',
        body: 'Nombre, cédula, teléfono, dirección, moneda e impuesto. Después de cambiar algo, haga clic en **Guardar cambios**.',
        placement: 'right',
      },
      {
        target: '[data-tour="settings-categories"]',
        title: 'Categorías de ingresos y gastos',
        body: 'Ordenan el dinero en Ingresos, Gastos y los reportes. Con **Agregar** crea una nueva, como «Alquiler» o «Servicios públicos».',
        placement: 'top',
      },
    ],
  },
]

const ALL = [...GENERAL_TOPICS, ...MODULE_TOPICS]

export function findTopic(key: string): HelpTopic | undefined {
  return ALL.find((topic) => topic.key === key)
}
