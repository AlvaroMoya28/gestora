<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const formError = ref('')
const errors = ref<Record<string, string>>({})

async function submit() {
  formError.value = ''
  errors.value = {}

  try {
    await auth.login(email.value.trim(), password.value)
    const redirect = route.query.redirect as string | undefined
    await router.push(redirect || { name: 'dashboard' })
  } catch (error) {
    errors.value = fieldErrors(error)
    formError.value = errorMessage(error, 'No fue posible iniciar sesión.')
  }
}
</script>

<template>
  <div class="login">
    <section class="pitch">
      <div class="brand">
        <span class="mark">G</span>
        <strong>Gestora</strong>
      </div>

      <h1>Toda la operación de su empresa en un solo lugar.</h1>
      <p>
        Clientes, proveedores, inventario, compras, ventas, producción y cuentas por cobrar
        conectados entre sí, con historial de cada movimiento.
      </p>

      <ul>
        <li>Existencias que siempre se pueden explicar</li>
        <li>Saldos por cobrar y por pagar al día</li>
        <li>Registro de quién hizo cada cambio</li>
      </ul>
    </section>

    <section class="panel">
      <form class="card" @submit.prevent="submit">
        <h2>Iniciar sesión</h2>
        <p class="muted intro">Ingrese con las credenciales que le asignó el administrador.</p>

        <p v-if="formError" class="alert" role="alert">{{ formError }}</p>

        <BaseField v-slot="{ id, invalid }" label="Correo electrónico" required :error="errors.email">
          <input
            :id="id"
            v-model="email"
            class="control"
            :class="{ 'is-invalid': invalid }"
            type="email"
            autocomplete="username"
            placeholder="usuario@empresa.com"
            required
          />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Contraseña" required :error="errors.password">
          <div class="password">
            <input
              :id="id"
              v-model="password"
              class="control"
              :class="{ 'is-invalid': invalid }"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              required
            />
            <button type="button" @click="showPassword = !showPassword">
              {{ showPassword ? 'Ocultar' : 'Mostrar' }}
            </button>
          </div>
        </BaseField>

        <BaseButton type="submit" variant="primary" block :loading="auth.loading">
          Entrar
        </BaseButton>
      </form>

      <p class="foot muted">Gestora · plataforma de gestión para pymes</p>
    </section>
  </div>
</template>

<style scoped>
.login {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1.05fr 1fr;
}

/* ------------------------------------------------------------- Lado izquierdo ---- */

.pitch {
  background: linear-gradient(150deg, var(--brand-700), var(--brand-500) 62%, #14a08f);
  color: #fff;
  padding: 56px 60px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.brand {
  display: flex;
  align-items: center;
  gap: 11px;
  margin-bottom: 44px;
}

.mark {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.18);
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 17px;
}

.brand strong {
  font-size: 17px;
  letter-spacing: -0.01em;
}

.pitch h1 {
  font-size: 30px;
  line-height: 1.22;
  font-weight: 600;
  letter-spacing: -0.02em;
  max-width: 15ch;
}

.pitch p {
  margin-top: 16px;
  max-width: 46ch;
  color: rgba(255, 255, 255, 0.84);
  font-size: 14.5px;
}

.pitch ul {
  list-style: none;
  padding: 0;
  margin: 34px 0 0;
  display: flex;
  flex-direction: column;
  gap: 11px;
}

.pitch li {
  position: relative;
  padding-left: 24px;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.9);
}

.pitch li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 7px;
  width: 12px;
  height: 7px;
  border-left: 2px solid rgba(255, 255, 255, 0.85);
  border-bottom: 2px solid rgba(255, 255, 255, 0.85);
  transform: rotate(-45deg);
}

/* -------------------------------------------------------------- Formulario ---- */

.panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 24px;
  gap: 22px;
}

.card {
  width: 100%;
  max-width: 372px;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.card h2 {
  font-size: 21px;
  font-weight: 650;
  letter-spacing: -0.01em;
}

.intro {
  font-size: 13.5px;
  margin-top: -10px;
}

.alert {
  background: var(--danger-100);
  color: var(--danger-600);
  border-radius: var(--radius-sm);
  padding: 9px 12px;
  font-size: 13px;
}

.password {
  position: relative;
}

.password input {
  padding-right: 74px;
}

.password button {
  position: absolute;
  right: 6px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--ink-500);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: 4px;
}

.password button:hover {
  background: var(--ink-100);
}

.foot {
  font-size: 12px;
}

@media (max-width: 880px) {
  .login {
    grid-template-columns: 1fr;
  }

  .pitch {
    padding: 34px 26px;
  }

  .pitch h1 {
    font-size: 23px;
  }

  .pitch ul {
    display: none;
  }
}
</style>
