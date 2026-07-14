# MODULO_01_LOGIN_Y_ACCESO — Instrucciones de Implementación Enterprise

**Clasificación:** Módulo Genérico de Arquitectura | **Versión:** 2.0 (Zero Trust & Fricción Cero)
**Propósito:** Definir el proceso, To-Do list y checklist de validación para construir un ecosistema de acceso corporativo bajo estándares 2026+ (Autenticación Adaptativa, Passwordless, Accesibilidad y Seguridad Perimetral).

---

## 📋 TO-DO LIST DE IMPLEMENTACIÓN

### Fase 1: Estructuración y Capa de Datos (Identidad y Sesiones)
- [ ] Validar que la entidad de usuarios soporte múltiples métodos de autenticación (Contraseña, Passkeys/WebAuthn, OAuth externo).
- [ ] Definir estructura para la **Gestión de Sesiones** (Session ID, IP, Dispositivo, Estatus, Fecha de Expiración, Nivel de Riesgo).
- [ ] Registrar la estructura de datos en el Codex del proyecto antes de generar cualquier interfaz o lógica.
- [ ] Configurar los estados de acceso permitidos (activo, pendiente, suspendido) y banderas de verificación (email_verificado, mfa_activo).

### Fase 2: Interfaz de Usuario y Accesibilidad (Fricción Cero UX)
- [ ] Diseñar contenedor de acceso bajo arquitectura responsiva (adaptable a móvil/escritorio sin saltos de layout).
- [ ] Implementar marcado semántico avanzado para garantizar el autocompletado del SO y gestores de contraseñas (1Password, Apple Keychain, etc.).
- [ ] Añadir soporte visual de Fricción Cero: Auto-focus inteligente, indicador de carga in-button (evitar doble submit), y advertencia de "Bloqueo de Mayúsculas" activo.
- [ ] Incorporar UX Accesible (a11y): Navegación completa por teclado, etiquetas ARIA para lectores de pantalla, áreas táctiles amplias y contraste WCAG mínimo de 4.5:1.
- [ ] Ocultar información sensible de la pantalla (UI Obfuscation) para evitar fugas visuales en conferencias o pantallas compartidas.

### Fase 3: Autenticación Adaptativa y Reglas de Negocio (Zero Trust)
- [ ] Sanitizar los datos de entrada antes de cualquier procesamiento lógico.
- [ ] Validar contraseñas tradicionales contra el hash almacenado, aplicando prevención de ataques de tiempo (Time-Safe validation).
- [ ] **Adaptive Auth:** Evaluar el riesgo del intento de acceso. Detectar ubicaciones geográficas anómalas, cambios bruscos de red o dispositivos no reconocidos.
- [ ] Solicitar autenticación escalada (Step-up Auth / 2FA) *únicamente* cuando el nivel de riesgo detectado sea elevado (para no añadir fricción a accesos habituales).
- [ ] Verificar que contraseñas nuevas no pertenezcan a listas de credenciales comprometidas (pwned passwords), evaluar entropía y permitir frases de paso largas.

### Fase 4: Gestión de Sesiones y Continuidad Operativa
- [ ] Implementar **Device Binding:** vincular criptográficamente la sesión a la huella del dispositivo confiable, no solo a la cookie.
- [ ] Desarrollar lógica de **Autenticación Continua:** reevaluar el riesgo durante la sesión activa en base a anomalías de comportamiento.
- [ ] Renovar silenciosamente las sesiones activas (Silent Renewal) antes de su expiración para evitar desconexiones abruptas de usuarios trabajando.
- [ ] Habilitar la invalidación remota de sesiones (permitir al usuario cerrar sesión en otros dispositivos) y expirar automáticamente sesiones inactivas.

### Fase 5: Seguridad Perimetral y Protección contra Abuso
- [ ] Aplicar *Rate Limiting Multivectorial* (límites simultáneos por Identificador de Usuario, IP, y Huella del Dispositivo).
- [ ] Activar retrasos progresivos (*Tarpitting*) tras múltiples intentos fallidos para mitigar ataques de fuerza bruta o diccionario automatizado.
- [ ] Uniformar tiempos de respuesta de la API: el sistema debe tardar exactamente lo mismo en responder si el usuario existe o no existe (Anti-User Enumeration).
- [ ] Retornar mensajes de error estandarizados ("Credenciales inválidas") que no expongan el vector del error.

### Fase 6: Recuperación de Cuenta y Privacidad
- [ ] Implementar flujo de recuperación seguro limitando la reutilización de enlaces y estableciendo un TTL (Tiempo de Vida) muy corto (ej. 15 minutos).
- [ ] Invalidar automáticamente enlaces de recuperación antiguos en cuanto se genere uno nuevo o se restablezca el acceso.
- [ ] Enviar notificaciones de seguridad en tiempo real al usuario ante: nuevos dispositivos detectados, cambios de contraseña o desactivación de métodos 2FA.

### Fase 7: Telemetría, Auditoría y Observabilidad
- [ ] Registrar todo intento de acceso (exitoso, fallido, bloqueado) en la bitácora del sistema (ASFL / Log).
- [ ] **NUNCA** registrar contraseñas ni datos personales sensibles en logs.
- [ ] Almacenar metadata no intrusiva: Identificador de sesión (hash), método de autenticación usado, motivo del rechazo, nivel de riesgo evaluado y cierre manual de sesión.

---

## ✅ CHECKLIST DE VALIDACIÓN Y CIERRE (ENTERPRISE GRADE)

Antes de dar por concluido el módulo, el Agente IA debe verificar y declarar el cumplimiento de estos puntos:

| # | Verificación de Calidad y Seguridad | Estatus |
| :--- | :--- | :---: |
| 1 | ¿La estructura de base de datos y variables está correctamente registrada en el Codex? | [x] |
| 2 | ¿El formulario previene la enumeración de usuarios y mitiga ataques de tiempo (Timing Attacks)? | [x] |
| 3 | ¿La interfaz cuenta con Fricción Cero UX (autocompletado, prevención de doble envío, a11y)? | [~] |
| 4 | ¿Se aplica Rate Limiting y retraso progresivo para frenar intentos de acceso automatizados? | [ ] |
| 5 | ¿Se cuenta con Autenticación Adaptativa (evaluación de riesgo IP/Dispositivo previo a acceso)? | [ ] |
| 6 | ¿La gestión de sesiones permite invalidación remota y renovación silenciosa? | [~] |
| 7 | ¿Se notifica al usuario al detectar un acceso desde un dispositivo o IP desconocidos? | [ ] |
| 8 | ¿El flujo de recuperación usa tokens seguros de un solo uso con expiración corta? | [ ] |
| 9 | ¿El evento queda registrado en la bitácora incluyendo el Nivel de Riesgo y el Session ID? | [~] |

**Leyenda:** `[x]` implementado · `[~]` implementado parcialmente (ver nota) · `[ ]` no implementado — requiere infraestructura o decisión de producto adicional, no construir sin autorización explícita (Mandamiento #9 del proyecto consumidor).

### 📝 Notas de Implementación (Genéricas — reutilizables entre proyectos del holding)

> Registradas al aplicar este módulo por primera vez en un proyecto real. Formato: `[patrón]` → estado y razón, sin nombres propios del proyecto/cliente.

- **#1 — Estructura de datos:** la entidad `[TABLA_USUARIOS]` (columnas mínimas: `[CAMPO_ID]`, `[CAMPO_NOMBRE]`, `[CAMPO_USUARIO_O_EMAIL]` UNIQUE, `[CAMPO_PASSWORD_HASH]`, `[CAMPO_ROL]`) debe registrarse en el Codex del proyecto **antes** de escribir cualquier endpoint (Regla de Oro de Base de Datos). No se implementaron aquí `[CAMPO_EMAIL_VERIFICADO]` / `[CAMPO_MFA_ACTIVO]` / soporte multi-método (Passkeys/OAuth) — el sistema base usa un solo método (usuario + contraseña).
- **#2 — Anti-enumeración / timing-safe:** el endpoint de login debe ejecutar SIEMPRE `[FUNCION_VERIFICAR_HASH]` (ej. `password_verify()`), incluso cuando `[CAMPO_USUARIO_O_EMAIL]` no existe — comparar contra un hash "dummy" precalculado en ese caso. Evita que el tiempo de respuesta delate si el usuario existe. Mensaje de error siempre genérico ("Credenciales inválidas"), nunca "usuario no existe" vs. "contraseña incorrecta".
- **#3 — Fricción Cero UX (parcial):** implementado — `autocomplete="username"`/`"current-password"` en los inputs, botón de submit deshabilitado durante la petición (previene doble envío). Pendiente: auto-focus inteligente, aviso visual de "Bloqueo de Mayúsculas", ARIA explícito más allá de `role="alert"` en el mensaje de error.
- **#4-5 — Rate Limiting / Auth Adaptativa:** **no implementado.** Requiere una tabla nueva (ej. `[TABLA_INTENTOS_LOGIN]`: `[CAMPO_IDENTIFICADOR]`, `[CAMPO_IP]`, `[CAMPO_FECHA]`, `[CAMPO_EXITO]`) o un store de conteo (APCu/Redis) — bajo la Regla de Oro de BD, esto exige dictar el `CREATE TABLE` y recibir confirmación del Arquitecto antes de construirse. No se debe inventar esta infraestructura sin esa autorización explícita.
- **#6 — Gestión de sesiones (parcial):** implementado — `session_regenerate_id(true)` en cada login exitoso (mitiga session fixation), guard de acceso por rol (`[FUNCION_CHECK_ACCESS](allowedRoles)`) que responde 401/403 vía el Response Contract del proyecto. Pendiente: invalidación remota multi-dispositivo y renovación silenciosa — ambas requieren una tabla de sesiones activas (`[TABLA_SESIONES]`), no construida aún.
- **#7 — Notificación de dispositivo/IP nuevos:** **no implementado.** Requiere canal de notificación (correo/push) ya operativo en el proyecto consumidor; no asumir que existe.
- **#8 — Recuperación de cuenta:** **no implementado.** Fuera de alcance hasta que el proyecto consumidor tenga flujo de correo transaccional confirmado y una tabla `[TABLA_TOKENS_RECUPERACION]` (`[CAMPO_TOKEN_HASH]`, `[CAMPO_EXPIRA_EN]`, `[CAMPO_USADO]`).
- **#9 — Telemetría (parcial):** implementado — cada intento de login se registra en la bitácora local (`REQUEST`/`RESPONSE`) sin loguear jamás `[CAMPO_PASSWORD_HASH]` ni la contraseña en texto plano; solo entorno de desarrollo, nunca producción. Pendiente: Nivel de Riesgo (requiere #5) y Session ID hasheado en el log.

**Regla de aplicación para el próximo proyecto:** los ítems `[~]`/`[ ]` de este checklist son ampliaciones legítimas de seguridad, pero **cada una que implique una tabla nueva o un cambio de arquitectura de sesión debe pasar primero por la Regla de Oro de Base de Datos y por confirmación explícita del Arquitecto** — este módulo es una guía de nivel enterprise a la que aspirar, no una orden de ejecución automática completa.