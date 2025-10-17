# QuinielaPro - Sistema de Gestión de Quinielas

Sistema web completo para la gestión y administración de quinielas deportivas.

## 🎯 Características

### Panel de Usuario
- ✅ Ver quinielas disponibles
- ✅ Comprar y llenar quinielas
- ✅ Consultar resultados en tiempo real
- ✅ Historial de transacciones y participaciones
- ✅ Dashboard con estadísticas personales

### Panel de Administrador
- ✅ Crear y gestionar quinielas
- ✅ Ingresar resultados de partidos
- ✅ Administrar usuarios
- ✅ Generar reportes y estadísticas
- ✅ Dashboard con métricas del sistema

## 🚀 Estructura del Proyecto

```
quinielapro/
├── index.html              # Página principal de bienvenida
├── user/                   # Panel de usuario
│   ├── index.html         # Dashboard de usuario
│   ├── quinielas-disponibles.html
│   ├── mis-quinielas.html
│   ├── resultados.html
│   └── historial.html
├── admin/                  # Panel de administrador
│   ├── index.html         # Dashboard de administrador
│   ├── crear-quiniela.html
│   ├── gestionar-quinielas.html
│   └── ingresar-resultados.html
├── assets/
│   ├── js/
│   │   ├── user.js        # Lógica del usuario
│   │   └── admin.js       # Lógica del administrador
│   └── css/               # Estilos personalizados (opcional)
└── README.md
```

## 🎨 Tecnologías Utilizadas

- **HTML5**: Estructura semántica
- **Tailwind CSS**: Framework de CSS (via CDN)
- **JavaScript**: Funcionalidad del cliente
- **Font Awesome**: Iconos (via CDN)

## 📦 Instalación

Este es un proyecto frontend estático. Para ejecutarlo:

1. Clona el repositorio:
```bash
git clone <repository-url>
cd quinielapro
```

2. Abre `index.html` en tu navegador web, o usa un servidor local:
```bash
# Con Python 3
python -m http.server 8000

# Con Node.js (http-server)
npx http-server
```

3. Accede a `http://localhost:8000` en tu navegador

## 🔧 Próximos Pasos

Esta es la estructura base con placeholders. Los siguientes servicios necesitan ser desarrollados:

### Backend Services (Por Implementar)
1. **Servicio de Autenticación**
   - Login/Registro de usuarios
   - Gestión de sesiones
   - Roles y permisos

2. **Servicio de Quinielas**
   - CRUD de quinielas
   - Gestión de partidos
   - Lógica de compra

3. **Servicio de Resultados**
   - Ingreso de marcadores
   - Cálculo de ganadores
   - Distribución de premios

4. **Servicio de Usuarios**
   - Gestión de perfiles
   - Historial de transacciones
   - Estadísticas personales

5. **Servicio de Pagos**
   - Integración con pasarelas de pago
   - Gestión de saldo
   - Retiros de premios

6. **Servicio de Notificaciones**
   - Notificaciones en tiempo real
   - Emails/SMS
   - Alertas push

### Base de Datos
Se recomienda diseñar el esquema con las siguientes entidades:
- Usuarios
- Quinielas
- Partidos
- Participaciones
- Transacciones
- Resultados

## 📱 Responsive Design

Todas las páginas están diseñadas con Tailwind CSS para ser completamente responsive y funcionar en:
- 📱 Móviles
- 💻 Tablets
- 🖥️ Desktop

## 🤝 Contribución

Este proyecto está en desarrollo inicial. Para contribuir:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto es privado y propietario.

## 👥 Autores

- Equipo de Desarrollo QuinielaPro

## 📞 Soporte

Para soporte, contacta a: [email de soporte]

---

**Nota**: Esta es la versión esqueleto del proyecto. Las funcionalidades reales se implementarán mediante servicios backend que alimentarán esta interfaz.
